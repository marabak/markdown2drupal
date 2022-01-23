<?php
parse_str($argv[1], $args);
$key = $args['k'];
$host = $args['h'];
$file = $args['f'];

exec('docker run --rm --volume "`pwd`:/data" --user `id -u`:`id -g` pandoc/latex --from=gfm '.$file.' -t html', $html);
$html = implode("\n", $html);
preg_match('<!-- Nid: ([[:digit:]]*) -->', $html, $id_matches);
if(1 < count($id_matches)){
  $id = $id_matches[1];
  exec('curl --location --request GET "https://'.$host.'/node/'.$id.'?_format=json" --header "api-key: '.$key.'"', $json);
  $data = json_decode($json[0]);
  $type = $data->type[0]->target_id;

  $data = [
    "type" => $type,
    "field_body" => [
      [
        "value" => $html,
        "format" => "full_html",
      ]
    ],
  ];

  preg_match('/<h1.*?>(.*?)<\/h1>/', $html, $title_matches);
  if(1 < count($title_matches)) {
    $title = $title_matches[1];
    $html = preg_replace('/<h1.*?>.*?<\/h1>/', '', $html);
    $data['field_body'][0]['value'] = $html;
    $data['title'] = [['value' => $title]];
  }

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
