<?php

require_once 'session.php';
require_once 'database.php';
require_once 'date.php';
require_once 'twig.php';

$db = new Database ();
$db->connect ();

// Form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    if (!isset ($_POST['update']))
    {
        header ('Location: ./');
        exit ();
    }
    
    // Open database connection
    $db = new Database();
    $db->connect();
    
    // Update database with new user information
    $data = array(
        'about' => (isset ($_POST['about']) ? $_POST['about'] : ''),
        'email' => (isset ($_POST['email']) ? $_POST['email'] : ''),
        'email_notifications' => (isset ($_POST['email_notifications']) ? $_POST['email_notifications'] : ''));
    
    $db->edit_user (
        $data['about'],
        $data['email'],
        $data['email_notifications'],
        Session::get_userid ());
    
    // Update $_SESSION
    Session::set_property ('about', $data['about']);
    Session::set_property ('email', $data['email']);
    Session::set_property ('email_notifications', $data['email_notifications']);
    
    header ('Location: ./user');
    exit ();
}


// Show public profile
if (isset ($_GET['username']))
{
    $user = $db->get_user ($_GET['username']);
    
    // User doesn't exist
    if (is_null ($user) || empty ($user))
    {
        header ('Location: ../login');
        exit ();
    }
    
    echo $twig->render (
        'user.twig',
        array (
            'title'      => $user['username'],
            'profile'    => 'public',
            'other_user' => $user));
} else {
    // Show private page
    echo $twig->render (
        'user.twig',
        array (
            'title'   => Session::get_username (),
            'profile' => 'private'));
}