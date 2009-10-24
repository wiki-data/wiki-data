<?php
XxxInstaller::Install('XwwTitle');

#
#  XwwTitle - Xoo World of Wiki - Title
#
#  Part of Xoo (c) 1997-2008 [[w:en:User:Zocky]], mitko.si
#	GPL3 applies
#
#########################################################################

#
#
#	Wikivariables and parser functions for dealing with titles, complementary to
#	built-in wiki variables and functions like {{PAGENAME}}. 
# 
#	Variables have the form {{<NAME>}} and refer to the current page.
#	e.g. {{ROOTPAGENAME}} 
#  
#	Functions have the form {{#title:<name>|<pagename>}}.
#	e.g. {{#title:rootpagename|News/2007/12}}
#      
#	The following variables and functions are available. 
#	All results are based on the pagename Category:News/2007/12 with UI language
#  set to 'sl'.
# 
#  name                   result
#	--------------------------------------------------
#	pagename			  			News/2007/12
#	fullpagename				Category:News/2007/12
#	basepagename				News/2007
#	fullbasepagename			Category:News/2007
#	rootpagename				News
#	fullrootpagename			Category:News
#	subpagename					12
#	issubpage					ISSUBPAGE
#	subpagelevel				3
#	nslocal						Kategorija
#	nslocalc						Kategorija:
#	nscanonical					Category
#	nscanonicalc				Category:
#	nsnumber						value of NS_CATEGORY
# 
# NOTE: In some cases, variables are already provided by MediaWiki, so this 
#       extension only implements the corresponding parser function.
#
############################################################################

class XwwTitle extends Xxx
{	
	
	function var_ROOTPAGENAME(&$parser)
	{
		return $this->fl_title($parser, 'rootpagename',$parser->mTitle->getPrefixedText());
	}
	
	function var_ROOTPAGENAMEE(&$parser)
	{
		return $this->fl_title($parser, 'rootpagenamee',$parser->mTitle->getPrefixedText());
	}

	function var_FULLROOTPAGENAME(&$parser)
	{
		return $this->fl_title($parser, 'fullrootpagename',$parser->mTitle->getPrefixedText());
	}
	
	function var_FULLROOTPAGENAMEE(&$parser)
	{
		return $this->fl_title($parser, 'fullrootpagenamee',$parser->mTitle->getPrefixedText());
	}

	function var_FULLBASEPAGENAME(&$parser)
	{
		return $this->fl_title($parser, 'fullbasepagename',$parser->mTitle->getPrefixedText());
	}

	function var_FULLBASEPAGENAMEE(&$parser)
	{
		return $this->fl_title($parser, 'fullbasepagenamee',$parser->mTitle->getPrefixedText());
	}

	function var_NAMESPACE(&$parser)
	{
		return $this->fl_title($parser, 'nslocal',$parser->mTitle->getPrefixedText());
	}
	function var_NAMESPACEE(&$parser)
	{
		return $this->fl_title($parser, 'nslocal',$parser->mTitle->getPrefixedText());
	}
	function var_NSLOCAL(&$parser)
	{
		return $this->fl_title($parser, 'nslocal',$parser->mTitle->getPrefixedText());
	}
	function var_NSLOCALE(&$parser)
	{
		return $this->fl_title($parser, 'nslocale',$parser->mTitle->getPrefixedText());
	}

	function var_NS(&$parser)
	{
		return $this->fl_title($parser, 'nscanonical',$parser->mTitle->getPrefixedText());
	}
	function var_NSE(&$parser)
	{
		return $this->fl_title($parser, 'nscanonicale',$parser->mTitle->getPrefixedText());
	}
	function var_NSCANONICAL(&$parser)
	{
		return $this->fl_title($parser, 'nscanonical',$parser->mTitle->getPrefixedText());
	}
	function var_NSCANONICALE(&$parser)
	{
		return $this->fl_title($parser, 'nscanonicale',$parser->mTitle->getPrefixedText());
	}

	function var_NSNUMBER(&$parser)
	{
		return $this->fl_title($parser, 'nsnumber',$parser->mTitle->getPrefixedText());
	}

	function var_ISSUBPAGE(&$parser)
	{
		return $this->fl_title($parser, 'issubpage',$parser->mTitle->getPrefixedText());
	}

	function var_SUBPAGELEVEL(&$parser)
	{
		return $this->fl_title($parser, 'subpagelevel',$parser->mTitle->getPrefixedText());
	}

	function var_PATH(&$parser)
	{
		return $this->fl_title($parser, 'path',$parser->mTitle->getPrefixedText());
	}
	function var_EDITED(&$parser)
	{
		return $this->fl_title($parser, 'edited',$parser->mTitle->getPrefixedText());
	}

	function var_EDITOR(&$parser)
	{
		return $this->fl_title($parser, 'editor',$parser->mTitle->getPrefixedText());
	}

	function var_CREATED(&$parser)
	{
		$firstrev = $this->getEarliestRevID($parser->mTitle);
		$rev = Revision::newFromId($firstrev);
		return $rev ? $rev->getTimestamp() : 0;
	}

	function var_CREATOR(&$parser)
	{
		return $this->fl_title($parser, 'creator',$parser->mTitle->getPrefixedText());
	}



	public function getEarliestRevID( $t ) {
		if ($t->mEarliestID != false)
			return $t->mEarliestId;

		$db = wfGetDB(DB_SLAVE);
		return $t->mEarliestID = $db->selectField( 'revision',
			"min(rev_id)",
			array('rev_page' => $t->getArticleID()),
			'XwwTitle::getEarliestRevID' );
	}

	function fl_title(&$parser, &$f, &$a, $titleText=null)
	{
		global $wgContLang,$wgNamespacesWithSubpages;
		$args = new xxxArgs($f,$a);
		$title = $args->trimExpand(1);
		if(!$title)	return array('found'=>false);
		$t=Title::newFromText($title);
		if ($t)
		{
			switch($args->command)
			{
			case 'edited':
				$lastrev = $t->getLatestRevID();
				$rev = Revision::newFromId($lastrev);
				return $rev->getTimestamp();
			case 'editor':
				$lastrev = $t->getLatestRevID();
				$rev = Revision::newFromId($lastrev);
				return $rev->getUserText();
			case 'created':
				$firstrev = $this->getEarliestRevID($t);
				$rev = Revision::newFromId($firstrev);
				return $rev ? $rev->getTimestamp() : 0;
			case 'creator':
				$firstrev = $this->getEarliestRevID($t);
				$rev = Revision::newFromId($firstrev);
				return $rev->getUserText();
			case 'pagename':
				return $t->getText();
			case 'pagenamee':
				return wfUrlEncode($t->getDBKey());
			case 'fullpagename':
				return $t->getPrefixedText();
			case 'fullpagenamee':
				return $t->getPrefixedURL();
			case 'subpagename':
				return $t->getSubpageText();
			case 'subpagenamee':
				return $t->getSubpageUrlForm();
			case 'basepagename':
				return $t->getBaseText();
			case 'basepagenamee':
				return wfUrlEncode( str_replace( ' ', '_', $t->getBaseText() ) );
			case 'fullbasepagename':
				if ($t->getNameSpace() == NS_MAIN)
				{
					return $t->getBaseText();
				}
				else
				{
					return str_replace('_',' ',$wgContLang->getNsText( $t->getNamespace() ) ) 
							. ':'
							. $t->getBaseText();
				}

			case 'fullbasepagenamee':
				if ($t->getNameSpace() == NS_MAIN)
				{
					return wfUrlEncode( str_replace( ' ', '_', $t->getBaseText() ) );
				}
				else
				{
					return wfUrlencode( $wgContLang->getNsText( $t->getNamespace() ) ) 
								. ':'
								. wfUrlEncode( str_replace( ' ', '_', $t->getBaseText() ) );
				}
			case 'rootpagename':
				if( isset( $wgNamespacesWithSubpages[ $t->getNamespace() ] ) && $wgNamespacesWithSubpages[ $t->getNamespace() ] ) 
				{
					$parts = explode( '/', $t->getText(),2 );
					return $parts[0];
				}
				else
				{
					return $t->getText();
				}
			case 'rootpagenamee':
				if( isset( $wgNamespacesWithSubpages[ $t->getNamespace() ] ) && $wgNamespacesWithSubpages[ $t->getNamespace() ] ) 
				{
					$parts = explode( '/', $t->getPartialURL(),2 );
					return $parts[0];
				}
				else
				{
					return $t->getPartialURL();
				}
			case 'fullrootpagename':
				if( isset( $wgNamespacesWithSubpages[ $t->getNamespace() ] ) && $wgNamespacesWithSubpages[ $t->getNamespace() ] ) 
				{
					$parts = explode( '/', $t->getPrefixedText(),2 );
					return $parts[0];
				}
				else
				{
					return $t->getPrefixedText();
				}
			case 'fullrootpagenamee':
				if( isset( $wgNamespacesWithSubpages[ $t->getNamespace() ] ) && $wgNamespacesWithSubpages[ $t->getNamespace() ] ) 
				{
					$parts = explode( '/', $t->getPrefixedURL(),2 );
					return $parts[0];
				}
				else
				{
					return $t->getPrefixedURL();
				}
			case 'talkpagename':
				if( $t->canTalk() ) {
					$talkPage = $t->getTalkPage();
					return $talkPage->getPrefixedText();
				} else {
					return '';
				}
			case 'talkpagenamee':
				if( $t->canTalk() ) {
					$talkPage = $t->getTalkPage();
					return $talkPage->getPrefixedUrl();
				} else {
					return '';
				}
			case 'subjectpagename':
				$subjPage = $t->getSubjectPage();
				return $subjPage->getPrefixedText();
			case 'subjectpagenamee':
				$subjPage = $t->getSubjectPage();
				return $subjPage->getPrefixedUrl();
			case 'nslocal':
			case 'namespace':
				return str_replace('_',' ',$wgContLang->getNsText( $t->getNamespace() ) );
			case 'nslocale':
			case 'namespacee':
				return wfUrlencode( $wgContLang->getNsText( $t->getNamespace() ) );
			case 'ns':
			case 'nscanonical':
				return str_replace('_',' ', Namespace::getCanonicalName($t->getNamespace()) );
			case 'nse':
			case 'nscanonicale':
				return wfUrlencode( Namespace::getCanonicalName($t->getNamespace()) );
			case 'nsnumber':
				return $t->getNamespace();
			case 'talkspace':
				return $t->canTalk() ? str_replace('_',' ',$t->getTalkNsText()) : '';
			case 'talkspacee':
				return $t->canTalk() ? wfUrlencode( $t->getTalkNsText() ) : '';
			case 'subjectspace':
				return $t->getSubjectNsText();
			case 'subjectspacee':
				return( wfUrlencode( $t->getSubjectNsText() ) );			
			case 'issubpage':
				return strpos($t->getText(),'/') > -1 ? 'ISSUBPAGE' : ''; 
			case 'subpagelevel':
				return strpos($t->getText(),'/') > -1 ? (count(explode('/',$t->getText()))-1)-'': '0'; 			
			case 'fullurl':
				return $t->getFullUrl($this->makeUrlQuery($args));
			case 'fullurle':
				return wfUrlencode($t->getFullUrl($this->makeUrlQuery($args)));
			case 'localurl':
			case 'url':
				return $t->getLocalUrl($this->makeUrlQuery($args));
			case 'localurle':
			case 'urle':
				return wfUrlencode($t->getLocalUrl($this->makeUrlQuery($args))); 
			case 'path':
				return $this->makePath($t,$args); 
			case 'link':
				if ($args->count > 2 && $args->isNumbered($args->count))
					$display=$args->cropExpand($args->count);
				elseif ($args->count > 3 && $args->trimExpand($args->count-1) == '') 
					$display=$args->cropExpand($args->count);
				else
					$display=$t->getFullText();

				if ($t->getFullText()==$parser->mTitle->getFullText()) {
				  $class = 'link-self';				  
				} elseif (stripos($t->getFullText(),$parser->mTitle->getFullText()."/")===0) {
				  $class = 'link-active';
				} 

				$html = '<a href="' 
				        . $t->getLocalUrl($this->makeUrlQuery($args))
				        . '" '
						. $this->makeHtmlAttributes($args,array('class'=>"link $class"))
						.'><span class="link-inner">' 
						. $display 
						.'</span></a>';
				return array($html, 'isHTML'=>true); 
			}
		}
		return array('found'=>false);
	}

	
	function makeUrlQuery(&$args) # $parser, $command , $title
	{
		$params=array();
		for($i=2;$i<=$args->count; $i++)
		{
			if ($args->isNamed($i))
			{
			    $name = $args->getName($i);
			    if (!$this->removePrefix($name,'#'))	$params[]=urlencode($name)."=".urlencode($args->trimExpandValue($i));
			}
			else break;
		}
		return join('&',$params);
	}

	function makeHtmlAttributes(&$args,$params=array()) # $parser, $command , $title
	{
		for($i=2;$i<=$args->count; $i++)
		{
			if ($args->isNamed($i))
			{
			    $name = $args->getName($i);
			    if ($this->removePrefix($name,'#')) $params[$name] = htmlspecialchars($args->trimExpandValue($i));
			}
			else break;
		}
		foreach ($params as $k=>$v)
		{
		  $ret.="$k=\"$v\" ";
		}
		return $ret;
	}

	function makePath($t, $args) #$parser, $command, $title, $template;
	{
		$template=count($args)>3 ? $args[3] : false; 

	   $prefix = "";
		$prefix.= $t->getNsText() ? $t->getNsText() . ":" : "";
		$pages=split('/',$t->getText());
		$path.=array_shift($pages);
		$ret= $template ? '{{'.$template.'|'.$prefix.$path.'|'.$path.'}}' : "[[$prefix$path]]";
		foreach ($pages as $p)
		{
			$path .= "/$p";
			$ret .= $template ? '{{'.$template.'|'.$prefix.$path.'|'.$p.'}}' : "/[[$prefix$path|$p]]";
		}
		return $ret;
	}
}
