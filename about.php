<?php

require_once 'session.php';
require_once 'twig.php';

// Render template
echo $twig->render (
    'about.twig',
    array (
        'page'   => 'about',
        'title' => 'About'));
