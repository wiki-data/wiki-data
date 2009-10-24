<?php

XxxInstaller::Install('XxxDomCache');

class XxxDomCache extends Xxx
{

	var $mTplRedirCache=array();
	var $mTplDomCache=array();

	var $mCacheActive=false;

	function setupExtension()
	{
		global $wgDomCachePath;
		$this->mCacheActive = ($wgDomCachePath && filetype($wgDomCachePath)=='dir' && is_writable($wgDomCachePath));
#		die ($wgDomCachePath . ($this->mCacheActive ? 'true' : 'false'));
	}
	
	function getCacheFileName($titleText)
	{
		global $wgDomCachePath;
		
		return "${wgDomCachePath}/" . md5($titleText) . ".wiki.cache";
	}

	function hook_ArticleSaveComplete(&$article)
	{
		if ($this->mCacheActive)
		{
			$titleText = $article->getTitle()->getPrefixedDBkey();
			$cacheFileName=$this->getCacheFileName($titleText);
			unlink($cacheFileName);
		}
		return true;
	}

	function hook_BeforeGetTemplateDom( &$parser, &$retTitle=false, &$retDom=false) 
	{
		if (!$this->mCacheActive) return true;
#		print '<br/>';
		$title=$retTitle;
		$titleText = $title->getPrefixedDBkey();

		# figure out the real title and title text
		
		if ( isset( $this->mTplRedirCache[$titleText] ) ) {
			# if the title is in the redirect cache, get real title from cache
			list( $ns, $dbk ) = $this->mTplRedirCache[$titleText];
			$realTitle = Title::makeTitle( $ns, $dbk );
			$realTitleText = $title->getPrefixedDBkey();
		}
		elseif ($title->isRedirect())
		{
			# if it isn't, but is a redirect, resolve the redirect
			$redirArticle=new Article($title);
			$realTitle=$redirArticle->getRedirectTarget();
			$realTitleText=$title->getPrefixedDBkey();
			
			# save the title to redirect
			$this->mTplRedirCache[$title->getPrefixedDBkey()] =	array
			(
				$realTitle->getNamespace(),
				$cdb = $realTitle->getDBkey()
			);
		}
		else
		{
			$realTitle=$title;
			$realTitleText=$titleText;
		}
#		print ("$titleText->$realTitleText ... ");
		
		# we have the real title, let's find the DOM
		$retTitle=$realTitle;
		$found=false;
		# is it in memory cache?
		if ( isset( $this->mTplDomCache[$realTitleText] ) ) {
			$retDom = $this->mTplDomCache[$realTitleText];
#			print "$realTitleText found in memory cache ...<br> ";
			return true;
		}

		wfSetupSession();

		if ( isset( $_SESSION['xxxTplDomCache'][$realTitleText] ) ) {
			$retDom = $_SESSION['xxxTplDomCache'][$realTitleText];
			$this->mTplDomCache[$realTitleText]=$retDom;
#			print "$realTitleText found in session cache ...<br> ";
			return true;
		}

		
		#we will operate with the file cache
		$cacheFileName=$this->getCacheFileName($realTitleText);
#		print "file name: $cacheFileName ... ";
			
		global $wgDomCacheEpoch;
		# is it in file cache
		if (is_readable($cacheFileName))
		{
			# check if it's expired - stat[9] = last modified
			$stat = stat($cacheFileName);
			if ($stat[9]>$wgDomCacheEpoch)
			{
				#we found it, let's load and parse
				$text = file_get_contents($cacheFileName);
				$retDom=unserialize($text);
	#			print "found in file cache ... ";
				wfRestoreWarnings();
				#succesfully loaded, save to memory cache
				if ( $retDom )
				{
					$_SESSION['xxxTplDomCache'][$realTitleText]=$retDom;
					$this->mTplDomCache[$realTitleText] = $retDom;
	#				print "loaded ... ";
	#				$retDom=$dom;
					return true;
				}
			}
		}
		# no luck in cache, go to the database		
		list( $text, $realTitle ) = $parser->fetchTemplateAndTitle( $title );
#			print "$realTitleText ($cacheFileName) not found in any cache ...<br>";

		if ( $text === false )
		{
			$retDom=false;
			return true;
		}
		else
		{
			$retDom = $parser->preprocessToDom( $text );
			# save it to memory cache
			$this->mTplDomCache[$realTitleText] = $retDom;
			# save it to file cache			
			file_put_contents($cacheFileName,serialize($retDom));
			return true;
#			print "writing $realTitleText to $cacheFileName<br>";
		}
		return;
	}
}

