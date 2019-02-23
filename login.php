<?php

require_once 'session.php';
require_once 'database.php';
require_once 'twig.php';

// Do not re-login if already loged in
if (Session::is_valid())
{
    header ('Location: ./user');
    exit ();
}


// POST: Process form submission     ===========================================


if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $db = new Database();
    $db->connect ();
    
    // Process login request
    if (isset ($_POST['login']))
    {
        // Bad POST request!
        if (!isset ($_POST['username']) || !isset ($_POST['password']))
            exit ();
        
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // Check username/password
        $user = $db->check_user_credentials ($username, $password);
        
        // Does the user exist?
        if (is_null ($user) || empty ($user))
        {
            $feedback = 'Login incorrecto!';
            
        } else {
            
            // Set session
            Session::set ($user);
            
            // Also set "remember_me" cookie
            // Add "remember_me" cookie with secret token (30 days)
            $token = $db->set_remember_me ($user['id']);
            
            setcookie (
                'remember_me',       // name
                $token,              // value
                time()+60*60*24*30,  // expire (30 days)
                '/',                 // path
                'freepo.st',         // domain
                false,               // secure (clients send cookie only through HTTPS)
                true);               // httponly (no javascript)
            
            // After login, redirect to homepage
            header ('Location: ./');
            exit ();
        }
        
    }
    
    // Process new account request
    if (isset ($_POST['new_account']))
    {
        // Bad POST request!
        if (!isset ($_POST['username']) || !isset ($_POST['password']))
            exit ();
        
        // Error to display if can't create new user
        $feedback = NULL;
        
        // Make sure the username is not empty
        $username = trim ($_POST['username']);
        $password = $_POST['password'];
        
        // Username taken
        if (strlen ($username) == 0 || $db->user_exists ($username))
            $feedback = 'Nombre ya usado';
        
        // Password too short
        if (!$feedback && strlen ($password) < 8)
            $feedback = 'Contraseña muy corta';
        
        if (!$feedback)
        {
            // Username OK, Password OK: create new user
            $user = $db->new_user ($username, $password);
            
            // Something bad happened...
            if (is_null ($user) || empty ($user))
                $feedback = 'Un error a ocurrido, intente nuevamente.';
            
            if (!$feedback)
            {
                // Everything fine, login user and redirect
                Session::set ($user);
                
                header ('Location: ./user');
                exit ();
            }
        }
    }
}


// GET: show login form     ====================================================


// Render template
echo $twig->render (
    'login.twig',
    array(
        'page'     => 'login',
        'title'    => 'Login',
        'feedback' => isset ($feedback) ? $feedback : ''));

