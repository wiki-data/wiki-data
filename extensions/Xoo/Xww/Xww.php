<?php
#
#	Preliminaries, look lower for interesting stuff
#
if ( !defined( 'MEDIAWIKI' ) ) { die( 'This file is a MediaWiki extension, it is not a valid entry point' );}
if (defined('XWW_LOADED')) return; 
define ('XWW_LOADED', true);
require_once (dirname(__FILE__).'/User.php');
require_once (dirname(__FILE__).'/Title.php');
require_once (dirname(__FILE__).'/Image.php');
require_once (dirname(__FILE__).'/Template.php');
require_once (dirname(__FILE__).'/TemplateProfiler.php');
require_once (dirname(__FILE__).'/List.php');


