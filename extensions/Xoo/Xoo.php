<?php
#
#	Preliminaries, look lower for interesting stuff
#
if ( !defined( 'MEDIAWIKI' ) ) { die( 'This file is a MediaWiki extension, it is not a valid entry point' );}
if (defined('XOO_LOADED')) return; 
define ('XOO_LOADED', true);
require_once('Xxx/Xxx.php');
require_once('Xww/Xww.php');
require_once('Xvv/Xvv.php');
require_once('Xuu/Xuu.php');
require_once('Xtt/Xtt.php');
require_once('Xss/Xss.php');

$wgExtensionCredits['other']['Xoo'] = array(
	'path' => __FILE__,
	'name' => 'Xoo',
	'author' => array( 'Zoran Obradović'),
	'url' => 'http://www.wiki-data.org/',
	'descriptionmsg' => 'xww-desc',
	'version' => '0.5',
);

$wgConfigureAdditionalExtensions[] = array(
    'name' => 'Xww',
    'settings' => array(
        'wgEvalHtmlIn' => 'array',
    ),
    'array' => array(
        'wgEvalHtmlIn' => 'ns-bool',
    ),
    'schema' => false,
    'url' => 'http://www.wiki-data.org/',
    'dir' => 'Xoo/Xww'
);

#require_once('Xpp/Xpp.php');
