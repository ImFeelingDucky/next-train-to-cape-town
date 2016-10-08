<?php

require __DIR__ . '/../vendor/autoload.php';

use Goutte\Client;
$client = new Client();

/* SAMPLE OUTPUT:
{"southern": {
        "recency":"7 minutes ago",
        "message":""
        "affected_trains": {
            "0512": "20 minutes late",
            "0514": "10 minutes late",
            "0212": "cancelled",
            "0224": "cancelled"
        },
        "other_trains":"on time"
    },
"northern": {...},
...
}

*/

$results = ["southern"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => ""],
    "northern"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => ""],
    "central"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => ""],
    "capeflats"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => ""],
    "bellville via monte vista"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => ""],
    "malmesbury/ worcester"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => ""],
    "business express"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => ""]
    ];

function trainMeUpScotty($message_details) {
    echo "Train up this message: '$message_details'";
}

// Get the updates website
$crawler = $client->request('GET', 'http://gometroapp.com/?updates+commuter');

// Parse the page and insert info into $results
$crawler->filter('#feed > div.listing')->each(function ($node) {
    global $results;

    $info_block = $node->filter('.right');
    $line = strtolower($info_block->filter('.update-route-small')->text());
    $recency = $info_block->filter('.commuter_feed_time')->text();
    $author = $info_block->filter('.commuter_feed_name')->text();
    $message = $info_block->filter('.update-text')->text();
    
    if ($author == "Metrorail Western Cape") {
        if (!$results[$line]["recency"]) { $results[$line]["recency"] = $recency; }
        if (!$results[$line]["message"]) { $results[$line]["message"] = $message; }
        
        // Parse the message into meaningful information for affected_trains and other_trains
        if ($message) {
            // If message contains x, assign y to affected_trains
        }
    } else {
        // Handle non-Metrorail official reports
    }
});

foreach ($results as $line) {
    echo $line["message"];
    echo "\n";
}

?>