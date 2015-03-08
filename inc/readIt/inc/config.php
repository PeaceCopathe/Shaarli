<?php
define ('SAVED_PATH', './data/saved');


if(!is_dir(SAVED_PATH)) {
    mkdir(SAVED_PATH, 0705);
}

$PICTURES_DOWNLOAD = false;
$PICTURES_BASE64 = true;

// Annulation de la fonction magic_quotes_gpc.
function strip_magic_quotes(&$valeur)
{
    $valeur = stripslashes($valeur);
}

if (get_magic_quotes_gpc())
{
	array_walk_recursive($_GET, 'strip_magic_quotes');
	array_walk_recursive($_POST, 'strip_magic_quotes');
	array_walk_recursive($_COOKIE, 'strip_magic_quotes');
	array_walk_recursive($_REQUEST, 'strip_magic_quotes');
}

// Désactivation de la fonction magic_quotes_runtime.
if (get_magic_quotes_runtime() && function_exists('set_magic_quotes_runtime'))
{
	set_magic_quotes_runtime(0);
}



