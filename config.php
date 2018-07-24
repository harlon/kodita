<?php

class Config {
    
    public static $DATABASE = array (
        // Data Source Name, contains the information required to connect to the database
        'dsn'      => 'mysql:host=localhost;dbname=koda_posting;charset=utf8',
        'username' => 'root',
        'password' => 'root'
    );
    
    public static $SEND_EMAILS = true;
}
