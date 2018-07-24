<?php

require_once 'database.php';

$db = new Database ();
$db->connect ();

// How should the feeds be sorted?
$rss_sort = isset ($_GET['sort']) ? strtoupper ($_GET['sort']) : NULL;

// Retrieve the posts
switch ($rss_sort)
{
    case 'NEW': $posts = $db->get_new_posts (); break;
    default:    $posts = $db->get_hot_posts ();
}


/*****   Create the XML (RSS) feed   *****/


$rss = new SimpleXMLElement ('<rss/>');
$rss->addAttribute ("version", "2.0");

// <channel> info
$channel = $rss->addChild ('channel');

$channel->addChild ('title', 'kodapost');
$channel->addChild ('description', '');
$channel->addChild ('link', 'http://kodaposting.st');
$channel->addChild ('lastBuildDate', date ('r'));

// Add our posts to the feed
foreach ($posts as $post)
{
    $item = $channel->addChild ('item');
    
    // The link of the the kodapost submission
    $kodapost_link = 'http://localhost/kodaposting/post/' . $post['hashId'];
    
    /* Link submitted by the user.
     * If no URL was posted (only title/text), link to freepo.st
     */
    $link = strlen ($post['link']) > 0 ? $post['link'] : $kodapost_link;
    
    // Short description with username and comments count
    $description = 'by ' . $post['username'] . ' — ' . $post['vote'] . ' votes, <a href="' . $kodapost_link . '">' . ($post['commentsCount'] > 0 ? $post['commentsCount'] . ' comments' : 'discuss') . '</a>';
    
    // Add post text if any
    if (strlen ($post['text']) > 0)
    {
        // Cut text at 1024 chars
        $description .= '<p>' . substr ($post['text'], 0, 1024);
        
        // Add a [Read More] link if some text has been cut
        if (strlen ($post['text']) > 1024)
            $description .= '... [<a href="' . $kodapost_link . '">Leer Mas</a>]';
        
        $description .= '</p>';
    }
    
    // 'r' » RFC 2822 formatted date (Example: Thu, 21 Dec 2000 16:01:07 +0200)
    $date = date ('r', strtotime ($post['created']));
    
    /**
     * It's recommended that you provide the guid, and if possible make it a
     * permalink. This enables aggregators to not repeat items, even if there
     * have been editing changes.
     */
    $item->addChild ('guid',         $post['hashId']);
    
    /**
     * Optional. If set to true, the reader may assume that it is a permalink
     * to the item (a url that points to the full item described by the <item>
     * element). The default value is true. If set to false, the guid may not
     * be assumed to be a url.
     */
    $item->addChild ('isPermaLink',  false);
    
    $item->addChild ('title',        htmlspecialchars ($post['title']));
    $item->addChild ('description',  htmlspecialchars ($description));
    $item->addChild ('link',         $link);
    $item->addChild ('kodapostLink', $kodapost_link);
    $item->addChild ('pubDate',      $date);
    $item->addChild ('author',       htmlspecialchars ($post['username']));
}

// Output RSS
header ('Content-Type: application/rss+xml; charset=UTF-8');
echo $rss->asXML ();