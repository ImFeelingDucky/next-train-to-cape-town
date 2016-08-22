<?php
$dest = $_POST['dest'];
$loc = $_POST['loc'];
$direction = $_POST['direction'];
$day = $_POST['day'];
$time_now = $_POST['time_now'];

// TODO: Remove the following debug:
$day = 2;

// TODO: delet this
echo "Got the deets: $dest $loc $direction $day $time_now";

// RESULTS!
$results_list = array(); // TODO: convert this to JSON later. This entire processor php file should output just one JSON-encoded list of suitable trains.
$current_prepared_query = NULL;

// NOTE arguments must be passed in this order:
function validateData($dest, $loc, $direction, $day, $time_now) {
    /* Validates all data passed to it */
    // TODO: refine this function
    if ($dest && $loc && $direction && isset($day) && $time_now) {
        return true;
    } else {
        return false;
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


function getTrainTimes($l, $d, $table, $t) {
    
    require '../db/connect.php';
    
    global $conn;
    
    $results_list = [];
    
    $ite_time_now = $t;
    
    $mysql_friendly_loc = str_replace(array(' ', "'"), '', strtolower($l));
    $mysql_friendly_dest = str_replace(array(' ', "'"), '', strtolower($d));
    
    $timeout_count = 0;
    
    while ($num_results < 8) {
        
        global $conn;
        
        if ($timeout_count > 100) { // Limit to under 100 counts
        // (it may or may not have found one or more trains)
            if ($num_results < 5) {
                if (!$num_results) {
                    // TODO: remove this echo statements
                    echo PHP_EOL . "PHPERROR: No results found.";
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
        
        // Debug 
        // echo "\r" . '$current_hours = ' . $current_hours . "\r";
        // echo "\r" . '$current_minutes = ' . $current_minutes . "\r";
        
        // // Increment the time! // ---
        if ($current_minutes == 59) {
            if ($current_hours < 9) {
                $ite_time_now = "0" . (substr($current_hours, 1, 1) + 1) . ":00";
            } else if ($current_hours == 9) {
                $ite_time_now = "10:00";
            } else if ($current_hours < 23) {
                $ite_time_now = ($current_hours + 1) . ":00";
            } else if ($current_hours == 23) {
                $ite_time_now = "00:00"; // Note that this is 00:00 the next day -- the
                // timetable may be wrong if it is Friday or Saturday as it may be
                // displaying times from here on as if it were still the previous day.
                // TODO: fix this
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
        
        
        // then run this while $num_results < 8. For each ite_time_now,
        // query the db: SELECT trainno, $loc, $dest WHERE $loc=$ite_time_now
        // if this returns a result row, $numresults++, echo this row as JSON, eg {'trainno': 101, 'kenilworth': '12:22', 'capetown': '12:41'};
        
        
        // THIS IS VULNERABLE TO SQL INJECTION!
        // TODO: rather use prepared statements
        
        // Testing this out v
        $current_query = "SELECT trainno, $mysql_friendly_loc, $mysql_friendly_dest FROM $table WHERE $mysql_friendly_loc=\"$ite_time_now\"";
        
        // This doesn't work
        $query_to_prepare = "SELECT `trainno`, `:loc`, `:dest` FROM `:table` WHERE `:loc`=':time'";
        // This does
        $alternate_query = "SELECT * FROM mf_north";
        // I think it's my SQL syntax??
        
        global $conn;
        
        // Step 1: Prepare query
        try {
            global $conn;
            $current_prepared_query = $conn->prepare($current_query);
        } catch (Exception $e) {
            echo "COULDN'T PREPARE QUERY! $e";
        }

        $array_to_bind = array('loc' => $mysql_friendly_loc, 'dest' => $mysql_friendly_dest, 'table' => $table, 'time' => $ite_time_now);
        
        // Step 2: Bind params and execute
        if (!$current_prepared_query->execute()) {
            echo 'PHPERROR: Couldnt execute query "$current_prepared_query"';
        }
        
        // Step 3: Get results
        $current_result = $current_prepared_query->get_result();
        
        // OLD
        // $current_result = $conn->query($current_query);
        
        if ($current_result->num_rows == 1) {
            $num_results++;
            
            $current_result_object = $current_result->fetch_object();
            
            // Pass result row to the $results_list array
            $results_list[] = $current_result_object;
            
        } else if ($current_result->num_rows > 1) {
            // TODO: remove this echo
            // echo "PHPERROR: unexpectedly, more result rows than 1 were received \r";
            $timeout_count++;
        } else if (!$current_result->num_rows) {
            $timeout_count++;
        }
    }
    
    $current_result->close();
    $current_prepared_query->close();
    
    // This is an array, with each element an object of a mysql row matching one train and its times
    return json_encode($results_list);
    
}


if (validateData($dest, $loc, $direction, $day, $time_now)) {
    echo "DEBUG: data_is_valid, running getTrainTimes\n"; // TODO: remove this debug
    echo "Data is: loc = $loc, dest = $dest, query_table = " . chooseTable() . ", time_now = $time_now"; // TODO: remove this debug
    echo getTrainTimes($loc, $dest, chooseTable(), $time_now);
} else {
    // Some data received was missing or the query was just invalid.
    die('API_ERROR: INVALID QUERY');
}

?>