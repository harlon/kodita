<?php

require_once 'session.php';
require_once 'database.php';
require_once 'date.php';
require_once 'twig.php';

// Must be logged in
if (!Session::is_valid ())
{
    header ('Location: ./login');
    exit ();
}

$db = new Database ();
$db->connect ();

// Show posts
if (isset ($_GET['posts']))
{
    $posts = $db->get_user_posts (Session::get_userid ());
    
    echo $twig->render (
        'user_posts.twig',
        array ('posts' => $posts));          
}

// Show comments
elseif (isset ($_GET['comments']))
{
    $comments = $db->get_user_comments (Session::get_userid ());
    
    echo $twig->render (
        'user_comments.twig',
        array ('comments' => $comments));
}

// Show replies
elseif (isset ($_GET['replies']))
{
    $replies = $db->get_user_replies (Session::get_userid ());
    
    // We need to set the replies as "read"
    $db->set_replies_as_read (Session::get_userid ());
    
    echo $twig->render (
        'user_replies.twig',
        array ('replies' => $replies));
}

// Wrong argument
else
{
    header ('Location: ./user');
    exit ();
}