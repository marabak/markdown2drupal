<?php
parse_str($argv[1], $args);
$key = $args['k'];
$host = $args['h'];
$file = $args['f'];

# transform gfm markdown content to html:
exec('docker run --rm --volume "`pwd`:/data" --user `id -u`:`id -g` pandoc/latex --from=gfm '.$file.' -t html', $html);
$html = implode("\n", $html);

# get the drupal node id from comment tag in the markdown file:
preg_match('<!-- Nid: ([[:digit:]]*) -->', $html, $id_matches);

# if the drupal node id is found:
if(1 < count($id_matches)){
  $id = $id_matches[1];

  # get the node type:
  exec('curl --location --request GET "https://'.$host.'/node/'.$id.'?_format=json" --header "api-key: '.$key.'"', $json);
  $data = json_decode($json[0]);
  $type = $data->type[0]->target_id;

  # replace all image tags with base64 encoded urls:
  preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $html, $matches);
  foreach ($matches[1] as $match) {
    $html = preg_replace('|'.$match.'|', 'data:image/png;base64,' . base64_encode(file_get_contents(dirname($file) . '/' . $match)), $html);
  }

  $data = [
    "type" => $type,
    "field_body" => [
      [
        "value" => $html,
        "format" => "full_html",
      ]
    ],
  ];

  # get the title from content and remove it from the field_body:
  preg_match('|<h1.*\s*.*?>(.*\s*.*?)</h1>|i', $html, $title_matches);
  if(1 < count($title_matches)) {
    $title = $title_matches[1];
    $html = preg_replace('|<h1.*\s*.*?>(.*\s*.*?)<\/h1>|i', '', $html);
    $data['field_body'][0]['value'] = $html;
    $title = preg_replace('|\s|i', ' ', strip_tags($title));
    $data['title'] = [['value' => $title]];
  }

  # update the corresponding drupal node:
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, "https://$host/node/$id?_format=json");
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Accept: application/vnd.api+json',
    'Content-Type: application/json',
    'cache-control: no-cache',
    'api-key: '.$key,
  ]);
  $response = curl_exec($curl);
  curl_close($curl);
}
