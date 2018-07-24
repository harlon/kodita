<?php

require_once 'session.php';
require_once 'database.php';
require_once 'date.php';
require_once 'parsedown.php';
require_once 'template/Twig/Autoloader.php';
require_once 'htmlpurifier/HTMLPurifier.standalone.php';

Twig_Autoloader::register();

$twig = new Twig_Environment(
    new Twig_Loader_Filesystem('./template/'),   // Path to templates
    array('cache' => './cache/template/'));      // Path to templates cache


// GLOBALS     =================================================================
// A global variable is like any other template variable, except that it's
// available in all templates and macros.


$twig->addGlobal ('user', Session::get_user ());


// FILTERS     =================================================================
// A filter transforms the passed value to something else


// Convert a date to "[date] ago"
$twig->addFilter ('ago', new Twig_Filter_Function (function ($datetime) {
    return Date::ago (strtotime ($datetime));
}));

$twig->addFilter ('datetime', new Twig_Filter_Function (function ($datetime) {
    return Date::datetime (strtotime ($datetime));
}));

$twig->addFilter ('title', new Twig_Filter_Function (function ($datetime) {
	return Date::title (strtotime ($datetime));
}));


// Format Markdown to HTML
$twig->addFilter ('markdown', new Twig_Filter_Function(function ($markdown) {
    $parsedown = new Parsedown ();
    
    $purifier_settings = HTMLPurifier_Config::createDefault ();
    $purifier_settings->set ('Core.EscapeInvalidTags', true);
    $purifier = new HTMLPurifier ($purifier_settings);
    
    return $purifier->purify ($parsedown->text ($markdown));
}));

// Return document root
$twig->addFilter ('docroot', new Twig_Filter_Function (function ($url) {
    $path = dirname ($_SERVER['SCRIPT_NAME']);
    
    /* This check is required because production server ends the path with
     * a slash, whereas my local setup does not.
     */
    if ('/' != substr ($path, -1))
        $path .= '/';
    
    return $path . $url;
}));


// FUNCTIONS     ===============================================================


// Return the number of new messages (received, but not read)
$twig->addFunction (new Twig_SimpleFunction ('new_messages', function () {
    if (!Session::is_valid ())
        return 0;
    
    $db = new Database ();
    $db->connect ();
    
    return $db->count_unread_messages (Session::get_userid ());
}));










