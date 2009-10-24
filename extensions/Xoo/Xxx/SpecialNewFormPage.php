<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "Not a valid MediaWiki entry point\n" );
}

$wgExtensionFunctions[] = 'wfSpecialNewFormPageSetup';

function wfSpecialNewFormPageSetup() {
	global $IP;
	require_once( "$IP/includes/SpecialPage.php" );
	SpecialPage::addPage( new SpecialPage( 'NewFormPage', '', true, false, false ) );
}

function wfSpecialNewFormPage() {
	global $wgOut, $wgRequest;
	if (!$name=$wgRequest->getText( 'name','')) $name = parser::getRandomString();
	$prefix=$wgRequest->getText( 'prefix','');
	$backlink=$wgRequest->getText( 'backlink','');
    $title = Title::newFromText("$prefix$name");
    
	$outargs='action=editform&editform_new=true&editform=new';
	$outargs.='&editform_template=' . wfUrlEncode($wgRequest->getText('editform_template'));
	$outargs.='&editform_summary=' . wfUrlEncode($wgRequest->getText('editform_summary'));

	$inargs=$wgRequest->getArray('editform_data');
	if(count($inargs))
	{
		foreach ($inargs as $k=> $v)
		{
			$outargs.="&editform_data[$k]=$v";
		}
	}
	$wgOut->redirect( $title->getFullUrl( $outargs ));
}

?>
