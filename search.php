<?php

/* This script is used to search content and display results */

require_once 'session.php';
require_once 'database.php';
require_once 'date.php';
require_once 'twig.php';

$db = new Database();
$db->connect();

// Must have a query ?q=
if (!isset ($_GET['q']))
{
    header ('Location: ./');
    exit ();
}

$query = trim ($_GET['q']);

// Query must not be an empty string
if (strlen ($query) == 0)
{
    header ('Location: ./');
    exit ();
}

$search_results = $db->search ($query);

echo $twig->render (
    'search.twig',
    ['query' => $query,
     'search_results' => $search_results]);
        
        
        
        
        
        