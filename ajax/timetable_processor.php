<?php

require '../db/connect.php';
require '../vendor/aura/sql/autoload.php';
use Aura\Sql\ExtendedPdo;

$dest = $_POST['dest'];
$loc = $_POST['loc'];
$direction = $_POST['direction'];
$day = $_POST['day'];
$time_now = $_POST['time'];
$line = $_POST['line'];
$give_updates = $_POST['updates'];

$results_array = array("trains"=>[], "departure_station"=>$loc, "arrival_station"=>$dest, "error"=>"", "timeout_count"=>0, "results_count"=>0, "debug"=>"", "info"=>"", "recency"=>"", "status"=>["other_trains"=>"", "line_message"=>""]);

function validateData($dest, $loc, $direction, $day, $time_now) {
    /* Validates all data passed to it */
    // TODO: refine this function
    if ($dest && $loc && $direction && isset($day) && isset($time_now)) {
        return true;
    } else {
        $results_array["error"] = 'API_ERROR: INVALID QUERY';
        die(json_encode($results_array));
    }
}

function chooseTable() {
    global $day, $direction;
    
    if ($day == 0 || $day == "su") {
        $query_table_prefix = "su";
    } else if ($day == 6 || $day == "sa") {
        $query_table_prefix = "sa";
    } else {
        $query_table_prefix = "mf";
    }
    
    $query_table = $query_table_prefix . '_' . $direction;
    return $query_table;
}


$table = chooseTable();
$ite_time_now = $time_now;

$mysql_friendly_loc = str_replace(array(' ', "'", "."), '', strtolower($loc)); // TODO: Make hecking sure that this is REALLY mysql-friendly
$mysql_friendly_dest = str_replace(array(' ', "'", "."), '', strtolower($dest)); // TODO: Make hecking sure that this is REALLY mysql-friendly

if (preg_match("/([^a-z])/", $mysql_friendly_loc) || preg_match("/([^a-z])/", $mysql_friendly_dest)) {
    $results_array["error"] = 'API_ERROR: Illegal character used in input!';
    die(json_encode($results_array));
} else {
    // (Partial) Success: mysql friendly(-ish)!
    // TODO: Do more sanitising here!
    
}

$query_limit = 400;
$timeout_count = 0;
$num_results = 0;

$pdo = gimme_pdo();

// Get query ready
$pdo_query = "SELECT trainno, $mysql_friendly_loc, $mysql_friendly_dest FROM $table WHERE $mysql_friendly_loc=:ite_time_now AND $mysql_friendly_dest IS NOT NULL ORDER BY $mysql_friendly_loc ASC;";
// use other query for sorting trains by arrival time...

// THIS CODE STINKS! 
// TODO: Use a MySQL Sort function
$bind_array = array(':ite_time_now' => $ite_time_now);

$pdo_statement = $pdo->prepare($pdo_query);

$pdo_statement->bindParam(':ite_time_now', $ite_time_now, PDO::PARAM_STR);

while ($timeout_count <= $query_limit) {
    
    if ($timeout_count == $query_limit && !$num_results) {
        $results_array["error"] = 'Unfortunately, no results were found at this time and day of the week.';
        die(json_encode($results_array));
    }
    
    $current_hours = substr($ite_time_now, 0, 2);
    $current_minutes = substr($ite_time_now, 3, 2);
    
    // // Increment the time! // ---
    if ($current_minutes == 59) {
        if ($current_hours < 9) {
            $ite_time_now = "0" . (substr($current_hours, 1, 1) + 1) . ":00";
        } else if ($current_hours == 9) {
            $ite_time_now = "10:00";
        } else if ($current_hours < 23) {
            $ite_time_now = ($current_hours + 1) . ":00";
        } else if ($current_hours == 23) {
            // Don't search later than 23:59
            $results_array["info"] = "There are no more trains running this evening.";
            die(json_encode($results_array));
        }
    } else {
        if ($current_minutes < 9) {
            $ite_time_now = $current_hours . ":0" . (substr($current_minutes, 1, 1) + 1);
        } else if ($current_minutes == 9) {
            $ite_time_now = $current_hours . ":10";
        } else {
            // Time is in format: 12:22
            $ite_time_now = $current_hours . ":" . ($current_minutes + 1);
        }
    }
    
    // --- // Time Incremented //
    
    //echo $pdo_statement->queryString;
    $pdo_statement->execute();

    $result = $pdo_statement->fetch();
    
    if ($result) {
        // Add this result to the results array
        
        // eg " Train no. $result[0] will depart from $loc station at $result[1] and arrive at $dest at $result[2]; ";
        $results_array["trains"][$num_results]["trainno"] = $result[0];
        $results_array["trains"][$num_results]["departure"] = $result[1];
        $results_array["trains"][$num_results]["arrival"] = $result[2];
        
        // ================== UPDATES =======================================================
        if ($give_updates != "false" && $give_updates != "no") {
            $updates = json_decode(file_get_contents("updates.txt"), true);
            
            if ($updates != null && !empty($updates["meta"]["timestamp"])) {
                
                if ($updates["meta"]["timestamp"] === 'DEBUG') {
                    $diff_mins = 'DEBUG';
                } else {
                    $then = date_create();
                    date_timestamp_set($then, intval($updates["meta"]["timestamp"]));
                    
                    $now = date_create();
                    
                    $diff_mins = date_diff($then, $now, true)->i;
                    
                    $results_array["recency"] = strval($diff_mins) . " mins";
                }
                
                if ($diff_mins < 21 || $diff_mins === "DEBUG") {
                
                if (!empty($updates[$line])) {
                    $results_array["status"]["other_trains"] = $updates[$line]["other_trains"];
                    if (!empty($updates[$line]["affected_trains"])) {
                        foreach ($updates[$line]["affected_trains"] as $affected_train_no => $affected_train_status) {
                            if (intval($affected_train_no) === intval($result[0])) {
                                $results_array["trains"][$num_results]["status"] = $affected_train_status;
                            } else {
                                $results_array["trains"][$num_results]["status"] = $updates[$line]["other_trains"];
                            }
                        }
                    } else if ($updates[$line]["other_trains"]) {
                        $results_array["trains"][$num_results]["status"] = $updates[$line]["other_trains"];
                        // TODO: not this ^^
                        // This is a temporary way to show general delays on this line
                    } else {
                        // If there were updates available but nothing specific about other/general trains on a line was mentioned, assume they are on time TODO: this might change
                        $results_array["trains"][$num_results]["status"] = "On time";
                    }
                   
                } else {
                    $results_array["trains"][$num_results]["status"] = "UNKNOWN";
                    $results_array["debug"] = "JSON could not be decoded :(";
                }
                
            } else {
                $results_array["debug"] = "Realtime updates were too old so couldn't be used!";
            }
            
            $results_array["status"]["line_message"] = $updates[$line]["message"];
            }
        } else {
            
        }
        $num_results++;
    } else {
        $timeout_count++;
    }
    
}

$results_array["timeout_count"] = $timeout_count;
$results_array["results_count"] = $num_results;

echo json_encode($results_array);

?>