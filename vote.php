<?php

require_once 'session.php';
require_once 'database.php';

// Do not re-login
if (!Session::is_valid())
    exit ();

$db = new Database ();
$db->connect ();

// Vote a post
if (isset ($_GET['post']))
{
    if (isset ($_GET['up']))
        $db->upvote_post ($_GET['post'], Session::get_userid ());
    
    if (isset ($_GET['down']))
        $db->downvote_post ($_GET['post'], Session::get_userid ());
    
    exit ();
}

// Vote a comment
if (isset ($_GET['comment']))
{
    if (isset ($_GET['up']))
        $db->upvote_comment ($_GET['comment'], Session::get_userid ());
    
    if (isset ($_GET['down']))
        $db->downvote_comment ($_GET['comment'], Session::get_userid ());
    
    exit ();
}