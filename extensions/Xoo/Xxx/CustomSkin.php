<?php
#
#	Preliminaries, look lower for interesting stuff
#
if ( !defined( 'MEDIAWIKI' ) ) { die( 'This file is a MediaWiki extension, it is not a valid entry point' );}

XxxInstaller::Install('XxxCustomSkin');
#
#  Custom skin - Define your skin in the wiki
#
#  Part of Xoo (c) 1997-2008 [[w:en:User:Zocky]], mitko.si
#  GPL3 applies
#
#  Based on: Extension:WikiSkin
# - See http://www.mediawiki.org/wiki/Extension:WikiSkin 
# - Licenced under LGPL (http://www.gnu.org/copyleft/lesser.html)
# - Author: http://www.organicdesign.co.nz/nad
#########################################################################
class XxxCustomSkin extends Xxx
{
	var $mDefaultSettings = array
	(
		'name'     => 'custom-skin',
		'default'  => 'custom-skin',
		'prefix'   => 'custom-skin-',
		'style'    => 'custom-skin.css',
	);

	
	function getSkinName()
	{
		return $this->S('name');
	}

	function getStyleName()
	{
		return $this->S('style');
	}

	function getMsgName()
	{
		global $wgRequest;
		$useSkin=$wgRequest->getText('customskin', false);
		if ($useSkin)
		{
			return $this->normalizeTitle($this->S('prefix').$useSkin);
		}
		else
		{
			return $this->normalizeTitle($this->S('default'));
		}
	}
	

	function applyCustomSkin() {
		global $wgUser;
		global $wgRequest;
		if (!$wgRequest->getVal('useskin'))
		{
			$wgUser->setOption('skin',$this->getSkinName());
			$wgUser->mSkin =& new SkinCustomSkin();
		}
		return true;
	}
	
	function hook_ArticleSaveComplete()
	{
		global $wgRequest;
		if ($r = $wgRequest->getText('backlink',false))
		{
			global $wgOut;
			$t = Title::newFromText($r);
			if ($t) $wgOut->redirect($t->getFullUrl());
			header("Location: " . $t->getFullUrl('action=purge'));
			$wgOut->disable();
		}
		return true;
	}
	
	function hook_EditPageBeforeEditButtons(&$editPage,&$buttons)
	{
		global $wgRequest;
		if ($r = $wgRequest->getText('backlink',false))
		{
			$buttons[]='<input type="hidden" name="backlink" value="'.htmlspecialchars($r).'"> will return to '.$r;
		}
		return true;
	}
	
	function hook_UserLoginComplete()
	{
		return $this->applyCustomSkin();
	}

	function hook_UserLogoutComplete()
	{
		return $this->applyCustomSkin();
	}

	var $skinParsing=false;
	var $skinArgs=array();
	
	function fl_skin(&$parser,&$frame,&$args)
	{
		$command = strtoupper($args[0]);
		if(!$this->skinParsing) return $this->notFound();
		if(!isset($this->skinArgs[$command])) return "not found $command";
		return array($this->skinArgs[$command],'isHTML'=>true);
	}

	var $extraHeadText='';
	function addHeadText($text)
	{
		$this->extraHeadText .= "\n$text";
#		die (htmlspecialchars($this->extraHeadText));
	}

	function hook_UnknownAction($action,&$article)
	{
		global $wgOut;
		if ($action=='src')
		{
			$rev = Revision::newFromTitle( $article->mTitle, 0 );
			if (!$rev)
			{
				$wgOut->addHTML($article->mTitle->getFullText().' not found');
				return false;
			}
			$text = $rev->getText();
			
			global $wgParser,$wgUser;
			$options = ParserOptions::newFromUser($wgUser);
			$wgParser->startExternalParse( $wgTitle, $options, Parser::OT_HTML);

			$text = (string)($wgParser->getPreprocessor()->preprocessToObj($text));
#			$wgOut->addHTML($obj);
#			$out=htmlspecialchars($text)."\n--------------------\n";
			$tagStack=array();

			while (preg_match('/^([\s\S]*?)<(\/?)(.\w+)(.*?)(\/?)>([\s\S]*?)(<\/\3>[\s\S]*)?$/',$text,$m))
			{
				list ($all,$before,$prefix,$tag,$attrs,$postfix,$value,$after) = $m;
				$text=$value.$after;
				if ($prefix)
				{
					array_pop($tagStack);
				}
				elseif (!$postfix)
				{
					array_push($tagStack,$tag);
				}
				$parentTag = $tagStack[count($tagStack)-1];
				
				$chunks = explode("\n",$before);
				$outchunks=array();
				foreach ($chunks as $chunk)
				{
					if (trim($chunk))
					{
						$outchunks[]= trim($chunk);
					}
				}
				$out.=implode("\n".str_repeat ("\t",$depth+1),$outchunks);

				switch ($_REQUEST['format'])
				{
				case 'highlight':
					switch ("$prefix$tag$postfix")
					{
					case 'root':
						$out .= "<span class=\"wiki-$tag\">";
						$depth = -1;
						break;
					case '/root':
						$out.="</span>";
						break;
					case 'template':
						$depth++;
						$out .= "<span class=\"wiki-$tag\">" .'{{';
						break;
					case '/template':
						$out .="\n" . str_repeat ("\t",$depth) . "}}</span>";
						$depth--;
						break;
					case 'tplarg':
						$depth++;
						$out .="<span class=\"wiki-$tag\">"."{{{";
						break;
					case '/tplarg':
						$depth--;
						$out .="}}}</span>";
						break;
					case 'title':
						$out .="<span class=\"wiki-$tag\">";
						break;
					case '/title':
						$out .="</span>";
						break;
					case 'name/':
						if ($tagStack[count($tagStack)-2]=='tplarg') $out.="|<span class=\"wiki-$tag\"></span>";
						else $out .="\n" . str_repeat ("\t",$depth) . "|\t<span class=\"wiki-$tag\"></span>";
						break;
					case 'name':
						if ($tagStack[count($tagStack)-2]=='tplarg') $out.="|<span class=\"wiki-$tag\">";
						elseif (strlen($value)>32 or preg_match('/&lt;/',$value)) $out.="\t<span class=\"wiki-value\">";
						else $out .="\n" . str_repeat ("\t",$depth) . "|\t<span class=\"wiki-$tag\">";
						break;
					case '/name':
						$out .="</span>";
						break;
					case 'value':
						$out .="<span class=\"wiki-$tag\">";
						break;
					case '/value':
						$out .="</span>";
						break;
					case 'value/':
						$out .="<span class=\"wiki-value\"></span>";
						break;
					}
					break;
				case 'prettycode':
				default:
					switch ("$prefix$tag$postfix")
					{
					case 'root':
						$depth = -1;
						break;
					case '/root':
						break;
					case 'template':
						$depth++;
						$out .= "{{";
						break;
					case '/template':
						$out .="\n" . str_repeat ("\t",$depth) . "}}";
						$depth--;
						break;
					case 'tplarg':
						$depth++;
						$out .="{{{";
						break;
					case '/tplarg':
						$depth--;
						$out .="}}}";
						break;
					case 'title':
						break;
					case '/title':
						break;
					case 'name':
						if ($tagStack[count($tagStack)-2]=='tplarg') $out.='|';
						else $out .="\n" . str_repeat ("\t",$depth) . "|\t";
						break;
					case '/name':
						$out .="\t";
						break;
					case 'value':
						$out.="";
						break;
					case '/value':
					}
				}
			};
			$out.=trim($text);
			$wgOut->addHTML("$out");
			return false;
		}
		return true;
	}


	function setupExtension()
	{
		global $wgXxxCustomSkin;
		global $wgMessageCache;
		$wgMessageCache->addMessage($this->S('default'),'
{{#eval:html|<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{{LANG}}}" dir="{{{DIR}}}">
	<head>
		<meta http-equiv="Content-Type" content="{{{MIMETYPE}}}; charset={{{CHARSET}}}"/>
		{{{EXTRAHEAD|<!-- -->}}}
		{{{HEADLINKS|<!-- -->}}}
		<meta http-equiv="imagetoolbar" content="no" />
		{{{VARSCRIPT|<!-- -->}}}
		<script type="{{{JSMIMETYPE}}}" src="{{{STYLEPATH}}}/common/wikibits.js"><!-- wikibits js --></script>
		<link rel="stylesheet" type="text/css" href="{{{STYLEPATH}}}/monobook/main.css">
		{{#if:{{{JSVARURL|}}}
		| <script type="{{{JSMIMETYPE}}}" src="{{{JSVARURL}}}"></script>
		|<!-- -->}}
		{{#if:{{{PAGECSS|}}}
		| <style type="text/css">{{{PAGECSS}}}</style>
		|<!-- -->}}
		{{#if:{{{USERCSS|}}}
		| <style type="text/css">{{{USERCSS}}}</style>
		|<!-- -->}}
		{{#if:{{{USERJS|}}}
		| <script type="{{{JSMIMETYPE}}}" src="{{{USERJS}}}"><!-- user js --></script>
		|<!-- -->}}
		{{#if:{{{USERJSPREV|}}}
		| <script type="{{{JSMIMETYPE}}}" src="{{{USERJSPREV}}}"><!-- user js prev --></script>
		|<!-- -->}}
		{{{TRACKBACKHTML|}}}
		{{#if:{{{USERJSPREV|}}}
		| <script type="{{{JSMIMETYPE}}}" src="{{{USERJSPREV}}}"><!-- user js prev --></script>
		|<!-- -->}}
		{{{HEADSCRIPTS|<!-- -->}}}
		<title>
			{{{TITLE}}}<!-- {{FULLPAGENAME}} -->
		</title>
	</head>
	<body
	{{#if:{{{ONDBLCLICK|}}}
	| ondblclick="{{{ONDBLCLICK}}}"
	|<!-- -->}}
	{{#if:{{{ONLOAD|}}}
	| onload="{{{ONLOAD}}}"
	|<!-- -->}}
	class="{{{NSCLASS|}}} {{{DIR|}}} {{{PAGECLASS|}}}"
	>
		<a name="top" id="top"></a>
		<div id="globalWrapper">
			<div id="column-content">
				<div id="content">
					{{#if:{{{SITENOTICE|}}}
					|<div id="siteNotice">{{{SITENOTICE}}}</div>
					|<!- -->}}
					<h1 class="firstHeading">{{{TITLE}}}</h1>
						<div id="bodyContent">
					<h3 id="siteSub">{{{TAGLINE}}}</h3>
					<div id="contentSub">{{{SUBTITLE}}}</div>
					{{{INFO}}}
					}}{{{CONTENT}}}{{#eval:html|
					{{#if:{{{CATLINKS|}}}
					|<div id="catlinks">{{{CATLINKS}}}</div>
					|<!- -->}}
					<div class="visualClear"></div>
				</div>
			</div>
		</div>
		<div id="column-one">
			{{{SITELOGO}}}
			<div id="p-cactions" class="portlet">
				<h5>Views</h5>
				<ul>{{{ACTIONS}}}</ul>
			</div>
			<div class="portlet" id="p-personal">
				<h5>Personal tools</h5>
				<div class="pBody">
					<ul>{{{PERSONAL}}}</ul>
				</div>
			</div>
			{{{SIDEBAR}}}
			<div class="portlet" id="p-tb">
				<h5>{{int:toolbox}}</h5>
				<div class="pBody">
					<ul>{{{TOOLBOX}}}</ul>
				</div>
			</div>
			{{{SEARCH}}}
			{{#if:{{{LANGLINKS|}}}
			|<div class="portlet" id="p-lang">
				<h5>{{int:otherlanguages}}</h5>
				<div class="pBody">
					<ul>{{{LANGLINKS}}}</ul>
				</div>
			</div>
			|<!-- -->}}
		</div>
		<div class="visualClear"></div>
		<script type="text/javascript"> if (window.runOnloadHook) runOnloadHook();</script>
		{{{REPORTTIME}}}
	</body>
</html>}}
');
		$wgXxxCustomSkin = $this;
		wfXxxCustomSkinDeclare();
		$this->applyCustomSkin();
	}
}

function wfXxxCustomSkinDeclare()
{
	class SkinCustomSkin extends SkinTemplate 
	{
		function initPage(&$out) 
		{
			global $wgXxxCustomSkin;
			SkinTemplate::initPage($out);
			global $wgRequest;
			global $wgOut;
			$wgRequest->response()->header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', strtotime('+10 minutes' )) . ' GMT' );
			$wgOut->enableClientCache( true );
			$this->skinname  = $wgXxxCustomSkin->getSkinName();
			$this->stylename = $wgXxxCustomSkin->getStyleName();
			$this->template  = 'CustomSkinTemplate';
		}
/*
	var $brokenLinkObjDOM = null;
	function _makeBrokenLinkObj( $nt, $text = '', $query = '', $trail = '', $prefix = '' ) 
	{
		# Fail gracefully
		if ( ! isset($nt) ) {
			# throw new MWException();
			return "<!-- ERROR -->{$prefix}{$text}{$trail}";
		}

		$fname = 'CustomSkin::makeBrokenLinkObj';
		wfProfileIn( $fname );

		global $wgOut, $wgParser,$wgMessageCache;

		if (!$wgMessageCache->getMsgFromNamespace('Custom-link-broken') || !is_object($wgParser->mStripState))
		{
			$linkHTML = Skin::makeBrokenLinkObj($nt,$text,$query,$trail,$prefix);
		}
		else
		{

			if ( '' == $text ) {
				$text = $nt->getPrefixedText();
			}
			$style = $this->getInternalLinkAttributesObj( $nt, $text, "yes" );
			list( $inside, $trail ) = Linker::splitTrail( $trail );
		   $t=$nt->getPrefixedText();


			if (!$this->brokenLinkObjDOM)
			{
				$messageText = wfMsgGetKey( 'custom-link-broken', true, true, false );
				$this->brokenLinkObjDOM = $wgParser->getPreprocessor()->preprocessToObj($messageText);
			}
		
			$contextFrame=Xxx::MakeFrame
			(
				$wgParser->getPreprocessor()->newFrame(),
				array
				(
					1=>$t,
					2=>$prefix.$text.$inside,
					'query'=>$query,
					'style'=>$style
				),
				Title::newFromText('mediawiki:custom-link-broken')
			);
		   $linkHTML = $contextFrame->expand($this->brokenLinkObjDOM,1);
			$linkHTML = $wgParser->mStripState->unstripBoth($linkHTML);
		}
		wfProfileOut( $fname );
		return $linkHTML;
	}
	
	var $knownLinkObjDOM = null;
	function _makeKnownLinkObj( $nt, $text = '', $query = '', $trail = '', $prefix = '' , $aprops = '', $style = '' ) {
	
		global $wgUser;
		$fname = 'CustomSkin::makeKnownLinkObj';
		wfProfileIn( $fname );

		if ( !is_object( $nt ) ) {
			wfProfileOut( $fname );
			return $text;
		}

		global $wgOut, $wgParser, $wgMessageCache;

		if (!$wgMessageCache->getMsgFromNamespace('Custom-link-known',$wgLang->code))
		{
			$linkHTML = Skin::makeKnownLinkObj($nt,$text,$query,$trail,$prefix);
		}
		else
		{
			if ( $text == '' ) {
				$text = htmlspecialchars( $nt->getPrefixedText() );
			}
			if ( $style == '' ) {
				$style = $this->getInternalLinkAttributesObj( $nt, $text );
			}

			if ( $aprops !== '' ) $aprops = ' ' . $aprops;

			list( $inside, $trail ) = Linker::splitTrail( $trail );
		   $t=$nt->getPrefixedText();
		   
			if (!$this->knownLinkObjDOM)
			{
				$messageText = wfMsgGetKey( 'custom-link-known', true, true, false );
				$this->knownLinkObjDOM = $wgParser->getPreprocessor()->preprocessToObj($messageText,1);
			}
		
			$contextFrame=Xxx::MakeFrame
			(
				$wgParser->getPreprocessor()->newFrame(),
				array
				(
					1=>$t,
					2=>$prefix.$text.$inside,
					'query'=>$query,
					'aprops'=>$aprops,
					'style'=>$style
				),
				Title::newFromText('mediawiki:custom-link-known')
			);
		   $linkHTML = $contextFrame->expand($this->knownLinkObjDOM) . $trail;
			$linkHTML = $wgParser->mStripState->unstripBoth($linkHTML);
		}
		wfProfileOut( $fname );
		return $linkHTML;
	}
	
	function _makeSelfLinkObj( $nt, $text = '', $query = '', $trail = '', $prefix = '' ) 
	{
		$fname = 'CustomSkin::makeSelfLinkObj';
		wfProfileIn( $fname );

		global $wgOut, $wgParser, $wgMessageCache;

		$l = wfMsgGetKey( 'custom-link-self', true,true, false );

		if (!$wgMessageCache->getMsgFromNamespace('Custom-link-self') || !is_object($wgParser->mStripState))
		{
			$l = Skin::makeSelfLinkObj($nt,$text,$query,$trail,$prefix);
		}
		else
		{
			if ( '' == $text ) {
				$text = htmlspecialchars( $nt->getPrefixedText() );
			}
			list( $inside, $trail ) = Linker::splitTrail( $trail );
		   $t=$nt->getPrefixedText();
			$l = $wgParser->mStripState->unstripBoth($wgParser->replaceVariables("{{mediawiki:custom-link-self|1=$t|2={$prefix}{$text}{$inside}|query=$query}}$trail"));
		}
		wfProfileOut( $fname );
		return $l;
	}		
	*/	
}

	class CustomSkinTemplate extends QuickTemplate 
	{

	# Expand templates in CSS before expanding
		function expandcss($data) 
		{
			$css = $this->data[$data];
			$css = str_replace('action=raw','action=raw&templates=expand',$css);
			$css = str_replace('&usemsgcache=yes','',$css);
			return $css;
		}

		function execute() 
		{

			wfSuppressWarnings();
			global $wgUser,$wgTitle,$wgParser, $wgXxxCustomSkin;
			$skin = $wgUser->getSkin();

			wfSuppressWarnings();

		 	$skinArgs = array();
		

			$skinArgs['LANG'] 			= $this->data['lang']; 
			$skinArgs['DIR']			   = $this->data['dir'];
			$skinArgs['MIMETYPE']		= $this->data['mimetype'];
			$skinArgs['CHARSET']		   = $this->data['charset'];
			$skinArgs['HEADLINKS']		= $this->data['headlinks'];
			$skinArgs['EXTRAHEAD']		= $wgXxxCustomSkin->extraHeadText; 
			$skinArgs['HEADSCRIPTS'] 	= $this->data['headscripts'];
			$skinArgs['VARSCRIPT']		= Skin::makeGlobalVariablesScript( $this->data );
			$skinArgs['JSMIMETYPE']		= $this->data['jsmimetype'];
			$skinArgs['STYLEPATH']		= $this->data['stylepath'];

			if($this->data['jsvarurl'])
				$skinArgs['JSVARURL']		= $this->data['jsvarurl'];
			if($this->data['pagecss'])
				$skinArgs['PAGECSS']		= $this->expandcss('pagecss');
			if($this->data['usercss'])
				$skinArgs['USERCSS'] 		= $this->expandcss('usercss');
			if($this->data['userjs'])
				$skinArgs['USERJS'] 		= $this->data['userjs'];
			if($this->data['userjsprev'])
				$skinArgs['USERJSPREV']		= $this->data['userjsprev'];
			if($this->data['trackbackhtml'])
				$skinArgs['TRACKBACKHTML']	= $this->data['trackbackhtml'];
			if($this->data['body_ondblclick']) 
				$skinArgs['ONDBLCLICK']		= $this->data['body_ondblclick'];

			$skinArgs['NSCLASS']		= $this->data['nsclass'];
			$skinArgs['PAGECLASS']		= $this->data['pageclass'];
			$skinArgs['STYLEPATH']		= $this->data['stylepath'];
			$skinArgs['TITLE']			= $this->data['displaytitle'] ? htmlspecialchars($this->data['title']) : $this->data['title'];

			$skinArgs['SEARCH'] 		= '<div id="p-search" class="portlet">'
										. '<h5><label for="searchInput">'.htmlspecialchars($this->translator->translate('search')).'</label></h5>'
										. '<div id="searchBody" class="pBody">'
										. '<form action="'.htmlspecialchars($this->data['searchaction']).'" id="searchform"><div>'
										. '<input id="searchInput" name="search" type="text" '
										. ($this->haveMsg('accesskey-search') ? 'accesskey="'.htmlspecialchars($this->translator->translate('accesskey-search')).'"' : '')
										. (isset($this->data['search']) ? ' value="'.htmlspecialchars($this->data['search']).'"' : '')
										. ' /><input type="submit" name="go" class="searchButton" id="searchGoButton" '
										. 'value="'.htmlspecialchars($this->translator->translate('go')).'" />&nbsp;'
										. '<input type="submit" name="fulltext" class="searchButton" '
										. 'value="'.htmlspecialchars($this->translator->translate('search')).'" /></div></form>'
										. '</div></div>';
			$skinArgs['SITELOGO'] 		= '<div class="portlet" id="p-logo">'
										. '<a style="background-image: url('.htmlspecialchars($this->data['logopath']).');" '
										. 'href="'.htmlspecialchars($this->data['nav_urls']['mainpage']['href']).'" '
										. 'title="'.htmlspecialchars($this->translator->translate('mainpage')).'"></a></div>'
										. '<script type="'.htmlspecialchars($this->data['jsmimetype']).'"> if (window.isMSIE55) fixalpha(); </script>';

			if($this->data['sitenotice']) 
				$skinArgs['SITENOTICE'] 	= $this->data['sitenotice'];

			$skinArgs['TAGLINE']		= htmlspecialchars($this->translator->translate('tagline'));
			$skinArgs['SUBTITLE'] 		= $this->data['subtitle'];
		 	$skinArgs['INFO'] 			= ($this->data['undelete'] ? '<div id="contentSub2">'.$this->data['undelete'].'</div>' : '')
										. ($this->data['newtalk'] ? '<div class="usermessage">'.$this->data['newtalk'].'</div>' : '')
										. ($this->data['showjumplinks'] ? '<div id="jump-to-nav">'
										. htmlspecialchars($this->translator->translate('jumpto'))
										. '<a href="#column-one">'.htmlspecialchars($this->translator->translate('jumptonavigation')).'</a>'
										. ', <a href="#searchInput">'.htmlspecialchars($this->translator->translate('jumptosearch')).'</a></div>' : '');

			if ($this->data['catlinks'])
				$skinArgs['CATLINKS']		= $this->data['catlinks'];

			$skinArgs['ACTIONS']		= '';
			foreach ($this->data['content_actions'] as $key => $tab)
			{
				$skinArgs['ACTIONS']	.= '<li id="ca-'.htmlspecialchars($key).'"'
									 	 . ($tab['class'] ? ' class="'.htmlspecialchars($tab['class']).'"' : '')
										. '><a href="'.htmlspecialchars($tab['href']).'">'.htmlspecialchars($tab['text']).'</a></li>';
			}

			$skinArgs['PERSONAL'] = '';
			foreach ($this->data['personal_urls'] as $key => $item) 
			{
				$skinArgs['PERSONAL']		.= '<li id="pt-'.htmlspecialchars($key).'"'
											 . ($item['active'] ? ' class="active"' : '')
											 . '><a href="'.htmlspecialchars($item['href']).'"'
											 . (!empty($item['class']) ? ' class="'.htmlspecialchars($item['class']).'"' : '')
											 . '>'.htmlspecialchars($item['text']).'</a></li>';
				if ($key == 'pt-login' || $key == 'pt-anonlogin')
					$skinArgs['LOGIN'] 		= '<a href="'.htmlspecialchars($item['href']).'">'.htmlspecialchars($item['text']).'</a>';
			} 
			
			$skinArgs['TOOLBOX'] = '';
			if($this->data['notspecialpage'])
				$skinArgs['TOOLBOX']		.='<li id="t-whatlinkshere"><a href="' . htmlspecialchars($this->data['nav_urls']['whatlinkshere']['href']). '"'
											 . $skin->tooltipAndAccesskey('t-whatlinkshere') .'>'
											 . wfMsg('whatlinkshere') .'</a></li>';

			if( $this->data['nav_urls']['recentchangeslinked'] ) 
				$skinArgs['TOOLBOX']		.='<li id="t-recentchangeslinked"><a href="' . htmlspecialchars($this->data['nav_urls']['recentchangeslinked']['href']). '"'
											 . $skin->tooltipAndAccesskey('t-recentchangeslinked') .'>'
											 . wfMsg('recentchangeslinked') .'</a></li>';

			if( $this->data['nav_urls']['trackbacklink'] ) 
				$skinArgs['TOOLBOX']		.='<li id="t-trackbacklink"><a href="' . htmlspecialchars($this->data['nav_urls']['trackbacklink']['href']). '"'
											 . $skin->tooltipAndAccesskey('t-trackbacklink') .'>'
											 . wfMsg('trackbacklink') .'</a></li>';

			if(!empty($this->data['feeds'])) 
			{
				$skinArgs['TOOLBOX']		.='<li id="feedlinks">';
				foreach($this->data['feeds'] as $key => $feed)
				{
					$skinArgs['TOOLBOX']	.='<span id="feed-' . Sanitizer::escapeId($key) .'">'
											 .'<a href="' . htmlspecialchars($feed['href']) .'"' . $skin->tooltipAndAccesskey('feed-'.$key).'>'
											 . htmlspecialchars($feed['text']).'></a>&nbsp;</span>';
				}
				$skinArgs['TOOLBOX']		.='</li>';
			}

			foreach( array('contributions', 'log', 'blockip', 'emailuser', 'upload', 'specialpages') as $special ) 
			{
				if($this->data['nav_urls'][$special]) 
				{
					$skinArgs['TOOLBOX']	.='<li id="t-' . $special .'">'
											 .'<a href="' . htmlspecialchars($this->data['nav_urls'][$special]['href']).'"'. $skin->tooltipAndAccesskey('t-'.$special) .'>'
											 . wfMsg($special)
											 .'</a></li>';
				}
			}
			

			if(!empty($this->data['nav_urls']['print'])) 
				$skinArgs['TOOLBOX']		.='<li id="t-print"><a href="' . htmlspecialchars($this->data['nav_urls']['print']['href']). '"'
											 . $skin->tooltipAndAccesskey('t-print') .'>'
											 . wfMsg('printableversion') .'</a></li>';

			if(!empty($this->data['nav_urls']['permalink'])) 
			{
				$skinArgs['TOOLBOX']		.='<li id="t-permalink"><a href="' . htmlspecialchars($this->data['nav_urls']['permalink']['href']). '"'
											 . $skin->tooltipAndAccesskey('t-permalink') .'>'
											 . wfMsgForContent('permalink') .'</a></li>';
			}
			elseif ($this->data['nav_urls']['permalink']['href'] === '') 
			{
				$skinArgs['TOOLBOX']		.='<li id="t-ispermalink"'
											 . $skin->tooltip('t-ispermalink') .'>'
											 . wfMsg('permalink') .'</a></li>';
			}

	
			if( $this->data['language_urls'] ) 
			{ 
				$skinArgs['LANGLINKS']		= '';
				foreach($this->data['language_urls'] as $langlink) 
				{ 
					$skinArgs['LANGLINKS']		.= '<li class="' . htmlspecialchars($langlink['class']).'">'
												 . '<a href="' . htmlspecialchars($langlink['href']) .'">'
												 . $langlink['text']
												 . '</a></li>';
				}
			} 

			$skinArgs['SIDEBAR']		= '';
			foreach($this->data['sidebar'] as $bar=>$cont) 
			{ 
				$heading = wfMsg( $bar );
				$heading = wfEmptyMsg($bar, $heading) ? $bar : $heading;
				$skinArgs['SIDEBAR']		.= '<div class="generated-sidebar portlet" id="p-' . Sanitizer::escapeId($bar).'"' . $skin->tooltip('p-'.$bar) . '>'
											.  '<h5>'.$heading.'</h5>'
											.  '<div class="pBody"><ul>';
											
	 			foreach($cont as $key => $val) 
	 			{ 
	 				$skinArgs['SIDEBAR']	.='<li id="' . Sanitizer::escapeId($val['id']) .'"'
	 										. ($val['active'] ? 'class="active"':'') .'>'
	 										. '<a href="' . htmlspecialchars($val['href']).'"'. $skin->tooltipAndAccesskey($val['id']) .'>'
	 										.  htmlspecialchars($val['text'])
	 										. '</a></li>';
				} 
 				$skinArgs['SIDEBAR']	.='</ul></div></div>';
			}

			$skinArgs['REPORTTIME'] 		= $this->data['reporttime'];

			$skinArgs['CONTENT'] 			= $this->data['bodytext'];
			
			$skinArgs['']					=& $skinArgs['CONTENT'];

			$wgXxxCustomSkin->skinArgs = $skinArgs;
		
			$skinTemplate=wfMsgNoTrans($wgXxxCustomSkin->getMsgName());
			global $wgOut;
			$wgOut->disable();
#			return $skinArgs['CONTENT'];
			global $wgParser;
/*
			$wgXxxCustomSkin->skinParsing=true;
			global $wgParser;
			foreach($skinArgs as $k=>$v)
			{
				$frameArgs[$k]=$wgParser->insertStripItem($v);
			}
			$rootFrame= $wgParser->getPreprocessor()->newFrame();
			$argFrame = $rootFrame->newCustomChild($skinArgs, $wgTitle);
			$dom = $wgParser->getPreprocessor()->preprocessToObj($skinTemplate);
			$parsedText =$argFrame->expand($dom);
			$output = $wgParser->mStripState->unstripBoth($parsedText);
			$wgXxxCustomSkin->skinParsing=false;
*/

			$wgXxxCustomSkin->skinParsing=true;
			$parser=$wgParser;
			$options = ParserOptions::newFromUser($wgUser);
			$parser->startExternalParse( $wgTitle, $options, Parser::OT_HTML);

			$skin = $wgXxxCustomSkin->getMsgName();
			$textToParse="{{MediaWiki:".$skin;
			foreach($skinArgs as $k=>$v)
			{
				$textToParse.="|$k=".$parser->insertStripItem($v);
			}
			$textToParse.="}}";
			$storeStripState = $parser -> mStripState;

			$options = ParserOptions::newFromUser($wgUser);
			$parser->startExternalParse( $wgTitle, $options, Parser::OT_HTML);
			$parsedText=$parser->parse( $textToParse, $parser->mTitle, $options, false)->mText;			
			$wgXxxCustomSkin->skinParsing=false;
			wfRestoreWarnings();			
			

			$output = $storeStripState->unstripBoth($parsedText);
/*		
			// write $output to custom cache folder - slashes are converted to subfolders
			global $wgRequest;
			$wgCustomCache = true;
			$wgCustomCacheFolder = 'filecache/';
			if ($wgCustomCache && $wgCustomCacheFolder) {
				wfProfileIn( 'CustomSkin::outputPage::cache' );
				$fdir = $_SERVER['DOCUMENT_ROOT'].'/'.$wgCustomCacheFolder.$wgTitle->getDBKey();
				$fname = "$fdir/$skin-index.html";
				switch ($wgRequest->getText('custom-cache')) {
				case 'savetocache':
					print('creating '.$fname.'<br>');
					@mkdir($fdir,0777);
					chmod($fdir,0777);
					$handle = fopen($fname,'w');
					fwrite($handle,$output);
					fclose($handle);
					chmod($fname,0666);
					break;
				case 'deletefromcache':
					unlink($fname);
					break;
				}
				wfProfileOut( 'CustomSkin::outputPage::cache' );
			}
*/
			$wgXxxCustomSkin->extraHeadText='';
			return $output;
		} # end function execute()
	} # end class CustomSkinTemplate
};
