<?php
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
	


