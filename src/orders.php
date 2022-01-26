<?php

function syncOrders(
    $magento_options,
    $blueprint_options,
    $sync_options
){
    doSync(
        $magento_options,
        $blueprint_options,
        $sync_options,
        'orders', 
        'map_order', 
        MAGENTO_PAGE_SIZE
    );
}

function map_address($a){
    return (object) [
        'country'=>$a->country_id,
        'city'=>$a->city,
        'zipOrPostCode'=>$a->postcode,
        'phone'=>$a->telephone
    ];
}

function map_status($magento_state) {
    $states = [
        'new'=>'PENDING',
        'processing'=>'PENDING',
        'on hold'=>'PENDING',
        'payment review'=>'PENDING',
        'complete'=>'CLEARED',
        'closed'=>'CLEARED',
        'canceled' => 'VOIDED'
    ];
    return $states[$magento_state];
}

function map_lineitem($o, $i){
    $childs = $i->childitems;
    $c = count($childs)>0 ? $childs[0] : $i;
    return (object) [
        'externalId'=>$o->increment_id.':'.$i->item_id,
        'externalProductId'=>$i->product_id,
        'externalVariantId'=>$c->sku,
        'quantity'=>$i->qty_ordered,
        'productTitle'=>$i->name,
        'variantTitle'=>$c->name,
        'price'=> $i->base_row_total
    ];
}

function map_order($client, $data, $magento_options) {

    if (@$data->store_id !== $magento_options['filter_store_id']) return null;

    $status = map_status($data->state);

    // Group by parent_item_id so only parent items are in list and childitems within
    $grouped_lineitems = [];
    foreach($data->items as $i){
        if (@$i->parent_item_id) continue;
        $i->childitems = [];
        $grouped_lineitems[$i->item_id] = $i;
    }
    foreach($data->items as $i){
        if (!@$i->parent_item_id) continue;
        @$grouped_lineitems[$i->parent_item_id]->childitems[] = $i;
    }
    
    $line_items = [];
    foreach($grouped_lineitems as $i) {
        $line_items[] = map_lineitem($data, $i);
    }

    $order = (object) [
        'externalId' => $data->increment_id,
        'externalCreatedAt' => $data->created_at,
        'externalUpdatedAt' => $data->updated_at,
        'externalCustomerId' => @$data->customer_id,
        'orderLabel'=> '#'.$data->increment_id,
        'status' => $status,

        'totalPrice' => $data->base_grand_total,
        'subTotalPrice' => $data->base_subtotal,
        'totalTax' => $data->base_tax_amount,
        'totalDiscount' => $data->base_discount_amount,
        'currency' => $data->base_currency_code,

        'billingAddress'=> map_address($data->billing_address),
        //'shippingAddress'=> (object) [], //@todo
        'lineItems'=> $line_items,
        'discountLines'=>[],
        'isSubscriptionOrder'=> false,
        'isCustomerInitiated'=> true,
        'isOrderCompleted'=> $status == 'CLEARED',

        'phone'=> null,
        'smsMarketingConsent' => 'NO_PREFERENCE',

    ];

    if (@$data->billing_address->telephone) {
        $order->phone = $data->billing_address->telephone;
    }

    return ['order'=>$order];
}