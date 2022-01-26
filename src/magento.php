<?php
use GuzzleHttp\Client;

define('MAGENTO_PAGE_SIZE', 10);

function get_client($magento_options){
    return new Client([
        'base_uri' => $magento_options['api_base_url'],
        'timeout'  => 60.0,
        'headers'  => [
            'Authorization' => 'Bearer '.$magento_options['api_access_token']
        ]
    ]);
}

function fetch_single_product($client, $product_id) {
    $filter_groups[] = [
        'filters' => [
            [
                'field' => 'entity_id',
                'value'=>$product_id,
                'condition_type'=>'eq'
            ]
        ]
    ];
    $search_criteria = [
        'currentPage'=> 1,
        'pageSize' => 1,
        'filterGroups'=>$filter_groups
    ];

    $response = $client->request('GET', 'products', [
        'query'=> ['searchCriteria'=>$search_criteria]
    ]);
    $data = json_decode($response->getBody(), false);
    return @$data->items[0];
}


function load_items(
    $client, 
    $path, 
    $update_at_from, 
    $update_at_to, 
    $page_size, 
    $page_number
) {
    $filter_groups = [];

    if ($update_at_from) {
        $filter_groups[] = [
            'filters' => [
                [
                    'field' => 'updated_at',
                    'value'=>$update_at_from,
                    'condition_type'=>'gt'
                ]
            ]
        ];
    }
    if ($update_at_to) {
        $filter_groups[] = [
            'filters' => [
                [
                    'field' => 'updated_at',
                    'value'=>$update_at_to,
                    'condition_type'=>'lt'
                ]
            ]
        ];
    }

    $search_criteria = [
        'currentPage'=> $page_number,
        'pageSize' => $page_size,
        'filterGroups'=>$filter_groups
    ];

    $response = $client->request('GET', $path, [
        'query'=> ['searchCriteria'=>$search_criteria]
    ]);
    $data = json_decode($response->getBody(), false);
    return $data;
}