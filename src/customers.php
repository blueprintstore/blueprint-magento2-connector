<?php

function syncCustomers(
    $magento_options,
    $blueprint_options,
    $sync_options
) {
    $client = get_client($magento_options);
    doSync(
        $magento_options,
        $blueprint_options,
        $sync_options,
        'customers/search', 
        'map_customer', 
        MAGENTO_PAGE_SIZE
    );
}


function map_customer($client, $data, $magento_options) {

    if (@$data->store_id !== $magento_options['filter_store_id']) return null;

    $customer = (object) [
        'externalId' => $data->id,
        'firstName'=>   $data->firstname,
        'lastName'=>    $data->lastname,
        'email'=>       $data->email,
        'externalCreatedAt' => $data->created_at,
        'externalUpdatedAt' => $data->updated_at,
        'smsMarketingConsent' => 'NO_PREFERENCE',
    ];

    foreach($data->addresses as $a) {
        if ($a->default_billing) {
            $customer->defaultAddress = map_address($a);
            if ($a->telephone) {
                $customer->phone = $a->telephone;
            }
        }
    }

    return ['customer'=>$customer];
}
