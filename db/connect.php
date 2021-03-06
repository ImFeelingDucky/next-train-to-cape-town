<?php

require '../vendor/aura/sql/autoload.php';
use Aura\Sql\ExtendedPdo;

function gimme_pdo() {

    /*// Define pdo as a static variable, to avoid connecting more than once 
    static $pdo;

    // Try and connect to the database, if a connection has not been established yet
    if(!isset($pdo)) {*/
        
        
         // Load configuration as an array. Use the actual location of the configuration file
        if (!getenv("CLEARDB_DATABASE_URL")) {
            $config = parse_ini_file('../../config/db_config.ini');
            $db_host = $config['host'];
            $db_name = $config['dbname'];
            $db_user = $config['username'];
            $db_pass = $config['password'];
        } else {
            $cleardb_url = parse_url(getenv("CLEARDB_DATABASE_URL"));
            $db_host = $cleardb_url['host'];
            $db_name = substr($cleardb_url['path'],1);
            $db_user = $cleardb_url['user'];
            $db_pass = $cleardb_url['pass'];
        
        }

        $pdo = new ExtendedPdo(
            'mysql:host='.$db_host.';dbname='.$db_name,
            $db_user,
            $db_pass
        );
        
        // If connection was not successful, handle the error
        if ($pdo === false) {
             // TODO: Handle error - notify administrator, log to a file, show an error screen, etc.
            error_log("COULDN'T CONNECT TO DATABASE!");
        }
    
    return $pdo;
    //}
}

?>