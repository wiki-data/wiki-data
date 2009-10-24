<?php
if ( !defined( 'MEDIAWIKI' ) ) { die( 'This file is a MediaWiki extension, it is not a valid entry point' );}
if (defined('XSS_LOADED')) return; 
define ('XSS_LOADED', true);
require_once (dirname(__FILE__).'/Data.php');

