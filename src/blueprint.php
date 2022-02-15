<?php
use GuzzleHttp\Client;

function send_batch($blueprint_options, $batch, $is_historic) {

    if (count($batch)===0) return true;

    $client =  new Client([
        'base_uri' => $blueprint_options['api_base_url'],
        'timeout'  => 60.0,
        'headers'  => [
            'Authorization' => $blueprint_options['api_access_token'],
            'x-blueprint-batch' => $is_historic ? '1' : '0'
        ],
        'http_errors' => false
    ]);

    $response = $client->request('POST', 'bulk', [
        'json' => $batch
    ]);

    if ($response->getStatusCode() === 201 || $response->getStatusCode() === 200) {
        return true;
    } else {
        echo $response->getBody(),"\n";
        return false;
    }
}