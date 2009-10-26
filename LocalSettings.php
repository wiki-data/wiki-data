<?php

die("test");
# 
# Multiwiki switch board
#
# If you customize your file layout, set $IP to the directory that contains
# the other MediaWiki files. It will be used as a base to locate files.
if( defined( 'MW_INSTALL_PATH' ) ) {
	$IP = MW_INSTALL_PATH;
} else {
	$IP = dirname( __FILE__ );
	define( 'MW_INSTALL_PATH',$IP );
}

$path = array( $IP, "$IP/includes", "$IP/languages","$IP/../test" );
set_include_path( implode( PATH_SEPARATOR, $path ) . PATH_SEPARATOR . get_include_path() );


# If PHP's memory limit is very low, some operations may fail.
# ini_set( 'memory_limit', '20M' );
if ( $wgCommandLineMode ) {
	if ( isset( $_SERVER ) && array_key_exists( 'REQUEST_METHOD', $_SERVER ) ) {
		die( "This script must be run from the command line\n" );
	}
	else
	{
		$wgMwSitePath=getcwd();
		require_once( "$IP/includes/DefaultSettings.php" );
		include("$wgMwSitePath/LocalSettings.php");
		include("$wgMwSitePath/AdminSettings.php");
	}
}
elseif (file_exists ($_SERVER['DOCUMENT_ROOT'].'/LocalSettings.php'))
{
	$wgMwSitePath=$_SERVER['DOCUMENT_ROOT'];
	@include_once("$wgMwSitePath/PreliminarySettings.php");
	require_once( "$IP/includes/DefaultSettings.php" );
	include_once($wgMwSitePath.'/LocalSettings.php');
}
else
	die ('<b>Wiki not installed.</b> Try <a href="/w/config">config</a>.');
	



######################################################################################################
#
#	FILE CACHE WITH CGI PARAMETERS --- MOVE TO APPROPRIATE FILE !!!!
#
#	This is only the shortcut for displaying pages from file cache without loading the wiki.
#	It will figure out if the page should be showed from cache, and if so, show it. Otherwise, it will
#	set a flag to inform extensions/Xoo/Xxx/FileCache.php that the page should potentially be cached.
#	The actual caching, if necessary, will occur there.
#	
#	By default, pages are not cached. Caching is controlled with the {{#filecache:command}} parser function.
#	Examples:
#	{{#filecache:for|10 min}}
#	{{#filecache:until|1 January 2011}}
#	{{#filecache:clear|Main page}}
#
#	If the user is logged in, action=purge will delete the cache for this page, and action=purgeall 
#	will clear the cache for all pages. No further caching will take place, and the loggedin user will
#	see non-cached pages.
#
#	If the user is logged out, the appropriate file name will be figured out, and if the file exists
#	and is not expired, it will be shown and the process will die. Otherwise, a flag will be set and
#	the file will be potentially cached later. Any actions other then 'render' and 'raw' will be 
#	filtered out. 
#	
#	Cached pages are stored in a hashed tree of directories per page titles, each combination of CGI 
#	parameters has its own file. A cached file is expired if its modified time is in the past.
#
#######################################################################################################



