<?php

/* This script is used to edit a user own post or comment */

require_once 'session.php';
require_once 'database.php';
require_once 'date.php';
require_once 'twig.php';

$db = new Database();
$db->connect();

// Must be logged in
if (!Session::is_valid ())
{
    header ('Location: ./');
    exit ();
}


// POST: save changes =======================================================


if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    // Edit a comment
    if (isset ($_POST['comment']))
    {
        $comment = $db->get_comment ($_POST['comment']);
        
        // Make sure user has the right to edit this comment
        if ($comment['userId'] != Session::get_userid ())
        {
            header ('Location: ./');
            exit ();
        }
        
        $new_comment_data =
        [
            'text' => isset ($_POST['text']) ? trim ($_POST['text']) : ''
        ];
        
        $db->edit_comment (
            $new_comment_data['text'],
            $comment['hashId'],
            Session::get_userid ());
        
        header ('Location: ./post/' . $comment['postHashId'] . '#comment-' . $comment['hashId']);
        exit ();
    }
    
    // Edit a post
    if (isset ($_POST['post']))
    {
        $post = $db->get_post ($_POST['post']);
        
        // Make sure user has the right to edit this post
        if ($post['userId'] != Session::get_userid ())
        {
            header ('Location: ./');
            exit ();
        }
        
        // New title/link/text to update the post with
        $new_post_data =
        [
            'title' => isset ($_POST['title'])  ? trim ($_POST['title']) : '',
            'link'  => isset ($_POST['link'])   ? trim ($_POST['link']) : '',
            'text'  => isset ($_POST['text'])   ? trim ($_POST['text']) : ''
        ];
        
        // MUST have a title
        if (strlen ($new_post_data['title']) == 0)
            $new_post_data['title'] = $post['title'];
        
        // If no link given, keep an empty string
        if (strlen ($new_post_data['link']) > 0)
        {
            $link_components = parse_url ($new_post_data['link']);
            
            // Make sure there's a "scheme"
            if (!isset ($link_components['scheme']))
                $new_post_data['link'] = 'http://' . $new_post_data['link'];
        }
        
        $db->edit_post (
            $new_post_data['title'],
            $new_post_data['link'],
            $new_post_data['text'],
            $post['hashId'],
            Session::get_userid ());
        
        header ('Location: ./post/' . $post['hashId']);
        exit ();
    }
    
    
    
    header ('Location: ./');
    exit ();
}


// GET: show reply page =====================================================


// Must have a comment id (to reply to)
if (!isset ($_GET['post']) && !isset ($_GET['comment']))
{
    header ('Location: ./');
    exit ();
}

// Is user editing a post or a comment?
if (isset ($_GET['password']))
    $item = array(
        'type' => 'password',
        'data' => $db->get_post ($_GET['post']));
else
    $item = array(
        'type' => 'comment',
        'data' => $db->get_comment ($_GET['comment']));

// Make sure the user is the actual poster/commenter
if ($item['data']['userId'] != Session::get_userid ())
{
    header ('Location: ./');
    exit ();
}

// Render template
switch ($item['type'])
{
    case 'comment':
        $template = 'edit_comment.twig';
        break;
        
    case 'post':
        $template = 'edit_post.twig';
        break;
}

echo $twig->render (
    $template,
    array ('item' => $item));
        
        
        
        
        
        