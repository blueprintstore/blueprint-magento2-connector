<?php
use Garden\Cli\Cli;

require 'vendor/autoload.php';
require './src/state.php';
require './src/magento.php';
require './src/blueprint.php';
require './src/sync.php';
require './src/customers.php';
require './src/products.php';
require './src/orders.php';

$cli = new Cli();

$cli->description('Sync Magento2 API to blueprint.')
    ->opt('magentoBaseUrl:m', 'Magento base URL.', true)
    ->opt('magentoApiToken:t', 'Magento api access token', true)
    ->opt('magentoStoreId:u', 'Magento numeric store ID.', true, 'integer')
    ->opt('blueprintApiKey:k', 'Blueprint API key.', true)
    ->opt('blueprintApiUrl:d', 'Blueprint API endpoint.', false)
    ->opt('stateFile:s', 'State file name to use (Defaults to state.json)', false);

// Parse and return cli args.
$args = $cli->parse($argv, true);

$magento_base = rtrim($args->getOpt('magentoBaseUrl'), '/');
$magento_options = [
    'api_base_url'=> $magento_base.'/index.php/rest/V1/',
    'image_base_url'=> $magento_base.'/media/catalog/product/',
    'api_access_token'=>$args->getOpt('magentoApiToken'),
    'filter_store_id'=>$args->getOpt('magentoStoreId')
];

$blueprint_options = [
    'api_access_token' => $args->getOpt('blueprintApiKey'),
    'api_base_url' => $args->getOpt('blueprintApiUrl', 'https://prod.blueprint-api.com/data/')
];

$stateFile = $args->getOpt('stateFile', 'state.json');
$import_from_datetime = load_state($stateFile);
$import_to_datetime = date('c'); // now
$sync_options = [
    'updated_from' => $import_from_datetime,
    'updated_to' => $import_to_datetime,
];

echo "Starting sync. Timestamps: $import_from_datetime -> $import_to_datetime\n";

echo "Syncing customers...\n";
syncCustomers(
    $magento_options,
    $blueprint_options,
    $sync_options,
);

echo "Syncing products...\n";
syncProducts(
    $magento_options,
    $blueprint_options,
    $sync_options,
);

echo "Syncing orders...\n";
syncOrders(
    $magento_options,
    $blueprint_options,
    $sync_options,
);


save_state($stateFile, $import_to_datetime);