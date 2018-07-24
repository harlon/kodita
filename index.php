<?php

require_once 'session.php';
require_once 'database.php';
require_once 'date.php';
require_once 'twig.php';

// Open database connection
$db = new Database ();
$db->connect ();

// Pagination. What page are we in?
if (isset ($_GET['page']))
{
    $page_number = intval ($_GET['page']);
    
    if ($page_number < 0)
        $page_number = 0;
} else {
    $page_number = 0;
}

// Retrieve list of posts
if (isset ($_GET['new']))
{
    $posts = $db->get_new_posts ($page_number);
    $page = 'new';
} else {
    $posts = $db->get_hot_posts ($page_number);
    $page = 'hot';
}

// Retrieve a list of user votes for the posts
$IDs = array ();

foreach ($posts as $post)
    $IDs[] = $post['id'];

$votes = $db->get_posts_votes (implode (',', $IDs), Session::get_userid ());

// Render template
echo $twig->render (
    'index.twig',
    array(
        'posts'       => $posts,
        'page'        => $page,
        'votes'       => $votes,
        'page_number' => $page_number));
