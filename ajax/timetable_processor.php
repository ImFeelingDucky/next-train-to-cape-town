<?php

require '../db/connect.php';
require '../vendor/aura/sql/autoload.php';
use Aura\Sql\ExtendedPdo;

$dest = $_POST['dest'];
$loc = $_POST['loc'];
$direction = $_POST['direction'];
$day = $_POST['day'];
$time_now = $_POST['time_now'];

// DEBUG: remove this
// $time_now = "12:23";

$results_array = array("trains"=>[], "departure_station"=>$loc, "arrival_station"=>$dest, "error"=>"", "timeout_count"=>0, "results_count"=>0);

/* SAMPLE $results_array JUST BEFORE IT IS ENCODED AS JSON AND PASSED OUT:

$results_array = array("trains"=>[["departure"=>"14:22", "arrival"=>"14:35", "trainno"=>"0195"], ["departure"=>"14:32", "arrival"=>"14:45", "trainno"=>"0195"],["departure"=>"14:42", "arrival"=>"14:55", "trainno"=>"0195"]], "departure_station"=>"Rosebank", "arrival_station"=>"Rondebosch", "error"=>"", "timeout_count"=>0);

*/

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

$query_limit = 300;
$timeout_count = 0;
$num_results = 0;

$pdo = gimme_pdo();

// Get query ready
$pdo_query = "SELECT trainno, $mysql_friendly_loc, $mysql_friendly_dest FROM $table WHERE $mysql_friendly_loc=:ite_time_now AND $mysql_friendly_dest IS NOT NULL;";
// use other query for sorting trains by arrival time... actually maybe just use MySQL SORT BY..?
  
$bind_array = array(':loc' => $mysql_friendly_loc,
    ':dest' => $mysql_friendly_dest,
    ':ite_time_now' => $ite_time_now);

$pdo_statement = $pdo->prepare($pdo_query);

$pdo_statement->bindParam(':ite_time_now', $ite_time_now, PDO::PARAM_STR);

while ($num_results < 8) {
    
    if ($timeout_count >= $query_limit) { // Limit to under 400 counts
    // (it may or may not have found one or more trains)
        if ($num_results < 7) {
            if (!$num_results) {
                // TODO: remove this echo statements
                $results_array["error"] = 'Unfortunately, no results were found at this time and day of the week.';
                die(json_encode($results_array));
            } else if ($num_results == 1) {
                // TODO: remove this echo statements
                // echo PHP_EOL . "One result was successfully found.";
            } else {
                // TODO: remove this echo statements
                // echo PHP_EOL . "$num_results results were successfully found.";
            }
        } else {
            // TODO: remove this echo statements
            // echo PHP_EOL . "WOW! RESULTS FOUND BABY!";
        }
        
        break;
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
            break;
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
        $num_results++;
    } else {
        $timeout_count++;
    }
    
}

$results_array["timeout_count"] = $timeout_count;
$results_array["results_count"] = $num_results;

echo json_encode($results_array);

?>