<?php

function syncProducts(
    $magento_options,
    $blueprint_options,
    $sync_options
){
    doSync(
        $magento_options,
        $blueprint_options,
        $sync_options,
        'products', 
        'map_product', 
        MAGENTO_PAGE_SIZE
    );
}

function map_variant($data, $image_base_url) {
    if ($data->status!==1) return null;
    
    $attrs = [];
    foreach($data->custom_attributes as $a) {
        $attrs[$a->attribute_code] = $a->value;
    }
    $image = null;
    if (@$attrs['image']) {
        $image = (object) [
            'url'=> $image_base_url.$attrs['image'],
            'alt' => 'Image of '.$data->name,
        ];
    }
    return (object) [
        'externalId' => $data->sku,
        'name'=> $data->name,
        'externalCreatedAt' => $data->created_at,
        'externalUpdatedAt' => $data->updated_at,
        'price'=>@$data->price,
        'image'=>$image,
    ];
}

function map_product($client, $data, $magento_options) {

    $image_base_url = $magento_options['image_base_url'];

    if ($data->status!==1) return null;
    if ($data->visibility==1) return null;

    $attrs = [];
    foreach($data->custom_attributes as $a) {
        $attrs[$a->attribute_code] = $a->value;
    }

    $image = null;
    if (@$attrs['image']) {
        $image = (object) [
            'url'=> $image_base_url.$attrs['image'],
            'alt' => 'Image of '.$data->name,
        ];
    }

    $variants = [];

    if ($data->type_id=='configurable') {
        $variant_product_ids = @$data->extension_attributes->configurable_product_links;
        if ($variant_product_ids && count($variant_product_ids)>0) {
            // Fetch the configurable option 'variant' products
            foreach($variant_product_ids as $variant_product_id) {
                $pdata = fetch_single_product($client, $variant_product_id);
                if ($pdata) {
                    $variant = map_variant($pdata, $image_base_url);
                    if ($variant) $variants[] = $variant;
                }
            }
        }
    } 
    
    if (count($variants)===0) {
        // Add single variant for the 1 product
        $variants[] = map_variant($data, $image_base_url);
    }

    $product = (object) [
        'externalId' => $data->id,
        'name'=> $data->name,
        'description'=> '',
        'image'=>$image,
        'variants'=>$variants,
        'externalCreatedAt' => $data->created_at,
        'externalUpdatedAt' => $data->updated_at,
    ];

    return ['product'=>$product];
}