# Markdown2drupal

## Requirements

### 1 - Install and configure RESTful Web Services module.
This module is included with the Drupal 9 installation.
You will have to enable and configure it to accept PATCH calls on existing nodes. 

### 1 - Create an api-key to authenticate the REST call to your drupal site.
You must download and install the [Key auth](https://www.drupal.org/project/key_auth) module
and create and api-key token for a user with edit permissions on the node you want to update.

## Usage

This script only updates an existing node in drupal.
The creation of new nodes is out of scope.
You must first create an empty node in your drupal site and get its id.
You must then add this tag in your markdown file you want to send :

`<!-- Nid: [node_id] -->`

where **node_id** is the id of the node you want to update.

Then you can call the markdown2drupal script this way:

`./markdown2drupal -k [api-key] -h [drupal host] -f [md file to send]`
