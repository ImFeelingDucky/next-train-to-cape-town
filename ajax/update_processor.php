<?php

require __DIR__ . '/../vendor/autoload.php';

use Goutte\Client;
$client = new Client();

/* SAMPLE OUTPUT:
{"southern": {
        "recency":"7 minutes ago",
        "message":"Other announcement. Trains T0512, 0514 are delayed 20 minutes. Trains T212, 212 are cancelled.",
        "affected_trains": {
            "0512": "20 minutes late",
            "0514": "10 minutes late",
            "0212": "cancelled",
            "0224": "cancelled"
        },
        "other_trains":"on time",
        "maintenance": ""
    },
"northern": {...},
...
}

*/

$results = ["southern"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => "", "maintenance" => []],
    "northern"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => "", "maintenance" => []],
    "central"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => "", "maintenance" => []],
    "capeflats"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => "", "maintenance" => []],
    "bellville via monte vista"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => "", "maintenance" => []],
    "malmesbury/ worcester"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => "", "maintenance" => []],
    "business express"=> ["message" => "", "recency" => "", "affected_trains" => [], "other_trains" => "", "maintenance" => []]
    ];

function analyseMessage($message, $line) {
    /*
    Parse the message into meaningful information for affected_trains and other_trains as in
    (If message contains x, add meaningful information to $results)
    */
    global $results;
    $train_no_replace_pat = '/\bT?([0-9]{3,4})/';
    $train_no_pat = '/(\b[0-9]{3,4})/';
    $delay_replace_pat = '/\b((([0-9]){1,3})-)?(([0-9]){1,3})(\+)? ?min(ute)?s?/';
    $delay_pat = '/(((([0-9]){1,2})-)?(([0-9]){1,2})\+? minutes)/';
    
    $count_cancel = preg_match_all("/\bcancel/i", $message);
    $count_delayed = preg_match_all("/\bdelay/i", $message);
    
    // First split message into sentences. This algorithm does per-sentence analysis.
    // Exclude "Train no. 0628" from being two different sentenes!
    $message = str_replace("no.", "no", $message);
    
    foreach (explode(".", $message) as $sen) {
        $train_no_matches = array();
        $delay_matches = array();
        
        $sen = preg_replace("/(\r|\n|)\s+/m", " ", $sen);
        // Normalise delays notices
        $sen = preg_replace($delay_replace_pat, "$1$4$6 minutes", $sen);
        // Normalise train no.s
        $sen = preg_replace($train_no_replace_pat, "$1", $sen);
        
        $score_cancel = $count_cancel + (preg_match_all("/\bcancel/i", $sen) * 100) + (preg_match_all("/\bcancel/i", $previous_sen) * 4);
        $score_delayed = $count_delayed + (preg_match_all("/\bdelay/i", $sen) * 100) + (preg_match_all("/\bdelay/i", $previous_sen) * 4);
        
        preg_match_all($train_no_pat, $sen, $train_no_matches);
        preg_match_all($delay_pat, $sen, $delay_matches);
        
        if (!empty($train_no_matches[0])) {
            // Specific trains are mentioned
            var_dump($train_no_matches[0]);
            echo "TRAIN NO.S: $line\n\n";
        }
        
        if (!empty($delay_matches[0])) {
            // There are delays
            var_dump($delay_matches[0]);
            echo "DELAYS: $line\n\n";
        }
        
        if (!empty($delay_matches[0]) && empty($train_no_matches[0])) {
            // There are delays but no specific trains are mentioned
            // Check if there are multiple mentioned delays: if there are,
            // the longer delay time prevails
            
            if (empty($results[$line]["other_trains"])) {
                $results[$line]["other_trains"] = $delay_matches[0][0] . " late";
            } else {
                $previous_delay_mins = array();
                $current_delay_mins = array();
                preg_match("/(([0-9]+)-)?([0-9]+)(\+)?/", $results[$line]["other_trains"], $previous_delay_mins);            }
                preg_match("/(([0-9]+)-)?([0-9]+)(\+)?/", $sen, $current_delay_mins);
                
                if (intval($previous_delay_mins[3]) > intval($current_delay_mins[3])) {
                    // Do nothing
                } else if (intval($previous_delay_mins[3]) < intval($current_delay_mins[3])) {
                    $results[$line]["other_trains"] = $delay_matches[0][0] . " late";
                } else if (intval($previous_delay_mins[3]) == intval($current_delay_mins[3])) {
                    if (!empty($previous_delay_mins[4])) {
                        // Do nothing
                    } else {
                        $results[$line]["other_trains"] = $delay_matches[0][0] . " late";
                    }
                }
                
        } else if (!empty($delay_matches[0]) && !empty($train_no_matches[0])) {
            // There are delays for specific, mentioned trains
            // For each in $train_no_matches, add an entry to affected trains
            // with the train no as the key and the delay time as the value
            
            foreach ($train_no_matches[0] as $train) {
                // set this train's status as the (first?) delay mentioned
                $results[$line]["affected_trains"][$train] = $delay_matches[0][0];
            }            
            // TODO: make sure this still works if there are multiple mentioned delays in the delay_matches array
        } else if (empty($results[$line]["other_trains"]) && strpos($sen, "Good Service") !== false) {
            $results[$line]["other_trains"] = "On time";
        } else if (!empty($train_no_matches[0]) && empty($delay_matches[0])) {
            // Train numbers are found, but no delays are mentioned. These trains are probably cancelled.
            // Check the algorithmic score of "cancel" vs "delay".
            // Whichever scores higher is more likely to be correct and so prevails
            echo "\n$line score_delayed = $score_delayed; score_cancel = $score_cancel\n";
            if ($score_cancel > $score_delayed) {
                foreach ($train_no_matches[0] as $train) {
                    // set this train's status as cancelled
                    $results[$line]["affected_trains"][$train] = "Cancelled";
                }
            } else if ($score_delayed > $score_cancel) {
                foreach ($train_no_matches[0] as $train) {
                    // set this train's status as delayed
                    $results[$line]["affected_trains"][$train] = $delay_matches[0][0] . " late";
                    // TODO: check that this ^^ does the right thing when (if?) $delay_matches has more than one delay time listed
                }
            } else {
                foreach ($train_no_matches[0] as $train) {
                    // set this train's status as possibly delayed or cancelled (uncertain)
                    $results[$line]["affected_trains"][$train] = "Delayed";
                }
            }
        }
        
        // Only update previous_sen if this sen has "cancel" or "delay"
        if (preg_match("/\bcancel/i", $sen) || preg_match("/\bdelay/i", $sen)) {
            $previous_sen = $sen;
        }
    }
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
        if (!$results[$line]["recency"]) {
            $results[$line]["recency"] = $recency;
        }
        if (!$results[$line]["message"]) {
            $results[$line]["message"] = $message;
            analyseMessage($message, $line);
        }
    } else {
        // Handle non-Metrorail-official reports
    }
});

file_put_contents("updates.txt", json_encode($results));

?>