<?php

require_once 'session.php';
require_once 'database.php';
require_once 'twig.php';

// Must be logged in
if (!Session::is_valid ())
{
    header ('Location: ./login');
    exit ();
}

$db = new Database();
$db->connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    // Make sure we have a title
    if (!isset ($_POST['title']))
    {
        header ('Location: ./');
        exit ();
    }
    
    // Trim title
    $title = trim ($_POST['title']);
    
    // Title empty
    if (0 == strlen ($title))
    {
        header ('Location: ./submit');
        exit ();
    }
    
    // Normalize Link
    $link = trim ($_POST['link']);
    
    // If no link given, keep an empty string
    if (strlen ($link) > 0)
    {
        $link_components = parse_url ($link);
        
        // Make sure there's a "scheme"
        if (!isset ($link_components['scheme']))
            $link = 'http://' . $link;
    }
    
    // Add the new post
    $post_hash_id = $db->new_post ($title, $link, $_POST['text'], Session::get_userid());
    
    // Redirect to the new post page
    header ('Location: ./post/' . $post_hash_id);
    exit();
}

// Render template
echo $twig->render (
    'submit.twig',
    array (
        'page'  => 'submit',
        'title' => 'Submit'));
