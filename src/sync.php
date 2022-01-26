<?php

function doSync(
    $magento_options,
    $blueprint_options,
    $sync_options,
    $path,
    $mapper_fn,
    $page_size
) {
    $client = get_client($magento_options);
    $total_records_in = 0;
    $total_records_out = 0;

    $page = 1;
    while(true) {
        echo "\tloading $path page $page... ";
        $res = load_items($client, $path, $sync_options['updated_from'], $sync_options['updated_to'], $page_size, $page);
        $items = $res->items;

        $batch = [];
        foreach($items as $item){
            $total_records_in ++;
            $mapped = $mapper_fn($client, $item, $magento_options);
            if ($mapped) {
                $batch[] = $mapped;
                $total_records_out++;
            }
        }
        send_batch($blueprint_options, $batch);

        if (count($items) < $page_size) {
            echo "done\n";
            break;
        }


        $page++;
        echo "ok\n";
    }

    echo "\t$total_records_in total records downloaded, $total_records_out sent to blueprint\n";
}
