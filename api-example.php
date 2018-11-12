<?php
require_once 'bt_api.class.php';
$api = new Bt_Api();

// Your API KEY
$api->setApiKey('d62a2b9e-9602-484c-85dc-b257224eacad');

// Path of your private key
$api->setPrivKeyFile('/home/ubuntu/btc-exchange.com/btc-exchange-api.pem');

// call method
print_r($api->getMe());
