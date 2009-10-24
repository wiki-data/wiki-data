<?php
 

if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}
/* 
$wgExtensionCredits['specialpage'][] = array(
'name' => 'Confirm Users Email',
'author' => 'Ryan Schmidt',
'url' => 'http://www.mediawiki.org/wiki/Extension:ConfirmUsersEmail',
'version' => '2.0',
'description' => 'Allows bureaucrats to set other users as emailconfirmed',
);
*/
$wgAutoloadClasses['SpecialMyAccount'] = dirname( __FILE__ ) . '/SpecialMyAccount.page.php';
$wgSpecialPages['MyAccount'] = 'SpecialMyAccount';
$wgExtensionFunctions[] = 'efSpecialMyAccount';
 
/**
* Determines who can use the extension; as a default, bureaucrats are permitted
*/
 
/**
* Populate the message cache and register the special page
*/
function efSpecialMyAccount() {
	global $wgMessageCache;
	require_once( dirname( __FILE__ ) . '/SpecialMyAccount.i18n.php' );
	foreach( efSpecialMyAccountMessages() as $lang => $messages )
	$wgMessageCache->addMessages( $messages, $lang );
}

