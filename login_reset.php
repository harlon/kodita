<?php

require_once 'session.php';
require_once 'database.php';
require_once 'twig.php';

// Do not re-login if already loged in
if (Session::is_valid())
{
    header ('Location: ./login');
    exit ();
}

$db = new Database ();
$db->connect ();

// POST: Process form submission     ===========================================


if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    // User asked to reset his password
    if (isset ($_POST['reset']))
    {
        if (!isset ($_POST['username']))
        {
            header ('Location: ./login');
            exit ();
        }
        
        // Make sure the user exists
        $user = $db->get_user ($_POST['username']);
        
        // User exists
        if (is_null ($user) || empty ($user))
        {
            header ('Location: ./login_reset');
            exit ();
        }
        
        // Get a new secret token
        $token = $db->password_reset ($user['hashId']);
        
        // Send reset token by email
        if (!is_null ($token))
        {
            mail ($user['email'],
                'kodaposting: password reset',
                $twig->render ('login_reset_email.twig', array ('token' => $token)),
                'From: kodaposting <noreply@kodaposting>' . "\r\n" . 'Reply-To: kodaposting <noreply@kodaposting>');
        }
        
        // Render template (tell user the password was sent)
        echo $twig->render (
            'login_reset.twig',
            array ('token_sent' => true));
        
        exit ();
    }
    
    // Validate secret token sent by email
    if (isset ($_POST['validate']))
    {
        // POST form must have a token and a password
        if (!isset ($_POST['token']) || !isset ($_POST['password']))
        {
            header ('Location: ./login_reset');
            exit ();
        }
        
        $token = $_POST['token'];
        $new_password = $_POST['password'];
        
        // Check password length
        if (strlen ($new_password) < 8)
        {
            // Render template
            echo $twig->render (
                'login_reset.twig',
                array (
                    'token'    => $token,
                    'feedback' => 'La contraseña debe tener al menos 8 caracteres'));
                
                exit ();
        }
        
        // Is the token valid?
        $user = $db->password_reset_validate ($token, $new_password);
        
        header ('Location: ./login');
        exit ();
    }
}


// GET: show reset form     ====================================================


// Form for resetting password (this is displayed when user clicks email link)
if (isset ($_GET['token']))
{
    // Render template
    echo $twig->render (
        'login_reset.twig',
        array ('token' => $_GET['token']));
        
    exit ();
}

// Render template
echo $twig->render ('login_reset.twig');

