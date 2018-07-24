<?php

require_once 'database.php';

class Session {
    public static function is_valid ()
    {
        return isset ($_SESSION) && !is_null ($_SESSION) && !empty ($_SESSION);
    }
    
    public static function get_user ()
    {
        if (self::is_valid ())
            return $_SESSION['user'];
        else
            return NULL;
    }
    
    public static function get_username ()
    {
        if (self::is_valid ())
            return $_SESSION['user']['name'];
        else
            return '';
    }
    
    public static function get_userid ()
    {
        if (self::is_valid ())
            return $_SESSION['user']['id'];
        else
            return '';
    }
    
    /**
     * Set user information to the session
     * 
     * @param user Associative array of user properties
     */
    public static function set ($user)
    {
        if (is_null ($user) || empty ($user))
            return;
        
        // Set session variable
        $_SESSION = array (
            'user' => array (
                'id'                    => $user['id'],
                'hash_id'               => $user['hashId'],
                'email'                 => $user['email'],
                'email_notifications'   => $user['email_notifications'],
                'registered'            => $user['registered'],
                'name'                  => $user['username'],
                'about'                 => $user['about']));
    }
    
    /**
     * Set user information to the session.
     * This is like "set ($user)", but instead of $user we are given
     * a single property.
     */
    public static function set_property ($property, $value)
    {
        $_SESSION['user'][$property] = $value;
    }
    
    // Retrieve session from cookie
    public static function remember_me ()
    {
        // We already have a session, nothing to do here
        if (Session::is_valid ())
            return;
        
        // Check if user does not have a "remember_me" cookie
        if (!isset ($_COOKIE['remember_me']))
            return;
        
        // Validate token
        $db = new Database ();
        $db->connect ();
        
        $user = $db->get_remember_me ($_COOKIE['remember_me']);
        
        self::set ($user);
    }
    
    public static function delete ()
    {
        unset ($_SESSION);
        session_destroy ();
        
        // Delete session
        $_SESSION = NULL;
    }
}

session_name ('kodapost');
session_start ();

/* Once the session is started, check for "remember_me" tokens.
 * If the session is already set, this function doesn't do anything.
 * If session is not set, and a valid token is set on user's cookies,
 * than the user is retrieved.
 */
Session::remember_me ();
    








