<?php

require '../Aura.Sql-2.5.0/autoload.php';
use Aura\Sql\ExtendedPdo;

function gimme_pdo() {

    /*// Define pdo as a static variable, to avoid connecting more than once 
    static $pdo;

    // Try and connect to the database, if a connection has not been established yet
    if(!isset($pdo)) {*/
         // Load configuration as an array. Use the actual location of your configuration file
        $config = parse_ini_file('../../config/db_config.ini'); 
        
        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        // TODO: Connect to this database as a limited user only! Use a specifically created account with min privileges!
        $pdo = new ExtendedPdo(
            'mysql:host=localhost;dbname='.$config['dbname'],
            $config['username'],
            $config['password']
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