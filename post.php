<?php

require_once 'session.php';
require_once 'config.php';
require_once 'database.php';
require_once 'date.php';
require_once 'twig.php';

$db = new Database ();
$db->connect ();

// POST   new comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    // Must be logged in
    if (!Session::is_valid ())
    {
        header ('Location: ./');
        exit ();
    }
    
    // Make sure we have a valid comment
    if (!isset ($_POST['new_comment']) || is_null($_POST['new_comment']))
    {
        header ('Location: ./');
        exit ();
    }
    
    // Clear input data
    $comment = trim ($_POST['new_comment']);
    
    // Empty text... do nothing
    if (strlen ($comment) == 0)
    {
        // Retrieve the post
        $post = $db->get_post ($_GET['hash_id']);
        
        if (is_null ($post) || empty ($post))
            exit ();
        
        header ('Location: ./' . $post['hashId']);
        exit();
    }
    
    // Everything seems OK, add the new comment
    $post_hash_id = $_GET['hash_id'];
    
    $comment_hash_id = $db->new_comment ($comment, $post_hash_id, Session::get_userid());
    
    /* Send email notification for the new comment
     * $post_op is the post's original poster
     */
    /*
    if (Config::$SEND_EMAILS)
    {
        $post = $db->get_post ($post_hash_id);
        $post_op = $db->get_post_op ($post_hash_id);
        
        if ($post_op['email_notifications'])
            mail ($post_op['email'],
                'kodapost: new comment to one of your posts',
                $twig->render ('email/new_comment.twig', array (
                    'commenter'     => Session::get_username (),
                    'post'          => $post['title']
                )),
                'From: kodapost <noreply@kodaposting>' . "\r\n" . 'Reply-To: kodapost <noreply@kodaposting');
    }
    */
    
    header ('Location: ./' . $post_hash_id . '#comment-' . $comment_hash_id);
    
    exit ();
}


// GET   display default page


// Retrieve the post
$post = $db->get_post ($_GET['hash_id']);

// Wrong hash_id
if (is_null ($post) || empty ($post))
{
    echo '404';
    exit ();
}

// Retrieve if user has voted this post
$votes_post = $db->get_posts_votes ($post['id'], Session::get_userid ());

// Retrieve comments for this post
$comments = $db->get_post_comments ($post['id']);

// Retrieve a list of user votes for the comments
$IDs = array();

foreach ($comments as $parent)
    foreach ($parent as $child)
        $IDs[] = $child['id'];

$votes_comment = $db->get_comments_votes (implode (',', $IDs), Session::get_userid ());

// Render template
echo $twig->render (
    'post.twig',
    array(
        'title'    => $post['title'],
        'post'     => $post,
        'comments' => $comments,
        'votes'    => array (
            'post'    => $votes_post,
            'comment' => $votes_comment)));
        
        
        
        
        
        