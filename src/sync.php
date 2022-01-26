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
    $total_records = 0;
    $page = 1;
    while(true) {
        echo "\tloading $path page $page... ";
        $res = load_items($client, $path, $sync_options['updated_from'], $sync_options['updated_to'], $page_size, $page);
        $items = $res->items;
        $total_records += count($items);

        $batch = [];
        foreach($items as $item){
            $mapped = $mapper_fn($client, $item);
            if ($mapped) {
                $batch[] = $mapped;
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

    echo "\t$total_records total records\n";
}
