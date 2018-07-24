<?php

/* This script is used to reply to a user comment */

require_once 'session.php';
require_once 'database.php';
require_once 'date.php';
require_once 'twig.php';

$db = new Database();
$db->connect();

// Must be logged in
if (!Session::is_valid())
{
    header ('Location: ./');
    exit ();
}


// POST     ====================================================================


// Submit the new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    if (!isset ($_POST['text']))
    {
        header ('Location: ./');
        exit ();
    }
    
    $parent_comment = $db->get_comment ($_POST['parent_comment']);
    
    $text = trim ($_POST['text']);
    
    // Empty comment. Redirect to parent comment
    if (strlen ($text) == 0)
    {
        header ('Location: ./post/' . $parent_comment['postHashId'] . '#comment-' . $parent_comment['hashId']);
        exit ();
    }
    
    // We have a text, add the reply and redirect to the new reply
    
    $hash_id = $db->new_reply (
        $text,
        $parent_comment['hashId'],
        Session::get_userid ());
    
    // Can't post?! What happened?!
    if (is_null ($hash_id))
        header ('Location: ./');
    else
        header ('Location: ./post/' . $hash_id['post'] . '#comment-' . $hash_id['comment']);
    
    exit ();
}


// GET     =====================================================================


// Must have a comment id (to reply to)
if (!isset ($_GET['comment']))
{
    header ('Location: ./');
    exit ();
}

$comment = $db->get_comment ($_GET['comment']);

// Render template
echo $twig->render (
    'reply.twig',
    array ('comment' => $comment));