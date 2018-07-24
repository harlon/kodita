<?php

require_once 'session.php';
require_once 'database.php';

// Not a valid session
if (!Session::is_valid ())
{
    header ('Location: ./');
    exit ();
}

// Delete "remember_me" cookie
if (isset ($_COOKIE['remember_me']))
{
    $db = new Database ();
    $db->connect ();
    $db->delete_remember_me (Session::get_userid ());
    
    unset ($_COOKIE['remember_me']);

    // Invalidate cookie
    setcookie (
        'remember_me',      // name
        NULL,               // value
        -1,                 // expire
        '/',                // path
        'freepo.st',        // domain
        false,              // secure (clients send cookie only through HTTPS)
        true);              // httponly (no javascript)
}

// Delete session
Session::delete ();

// Logged out, redirect to homepage
header ('Location: ./');
exit ();