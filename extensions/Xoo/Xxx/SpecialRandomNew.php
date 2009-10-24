<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "Not a valid MediaWiki entry point\n" );
}

$wgExtensionFunctions[] = 'wfSpecialRandomNewSetup';

function wfSpecialRandomNewSetup() {
	global $IP;
	require_once( "$IP/includes/SpecialPage.php" );
	SpecialPage::addPage( new SpecialPage( 'RandomNew', '', true, false, false ) );
}

function wfSpecialRandomNew() {
	global $wgOut, $wgRequest;
	$randstr = parser::getRandomString();
	$prefix=$wgRequest->getText( 'prefix','');
	$editintro=$wgRequest->getText( 'editintro','');
	$backlink=$wgRequest->getText( 'backlink','');
	$preload=$wgRequest->getText( 'preload','');
        $title = Title::newFromText("$prefix$randstr");
	$inargs=$wgRequest->getArray('arg');
	$outargs='';
	foreach ($inargs as $k=> $v)
	{
		$outargs.="&arg[$k]=$v";
	}
	$wgOut->redirect( $title->getFullUrl("action=edit&editintro=$editintro&preload=$preload&backlink=$backlink". $outargs ));
}

?>
