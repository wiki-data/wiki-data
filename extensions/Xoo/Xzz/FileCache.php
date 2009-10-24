<?php
#
#	Preliminaries, look lower for interesting stuff
#
if ( !defined( 'MEDIAWIKI' ) ) { die( 'This file is a MediaWiki extension, it is not a valid entry point' );}


XxxInstaller::Install('XzzFileCache');


class XzzFileCache extends Xxx
{

	function fl_filecache(&$parser,&$f,&$a)
	{
		global $wgXzzCacheExpireTime;

		$args = new xxxArgs($f,$a);
		switch ($args->command)
		{
		case 'purge':
			# clear file cache for the target page
			$title = $args->trimExpand(1,false);
			if (!$title) $title=$parser->mTitle;
			else $title = Title::newFromText($title);
			if (!$title) return $this->notFound();
			$title=$title->getFullText();
			$path = wfXzzCacheMakePath($title);
			@exec( "rm -rf $path" ); 
			@file_put_contents("$wgXzzCachePath/cache.log", date('Y-m-d H:i:s') . "\tpurge\t$title\t$path\n", FILE_APPEND);
			return ''; 
		case 'purgeall':
			# clear file cache for all pages
			@exec( "rm -rf $wgXzzCachePath/??/" );	
			@file_put_contents("$wgXzzCachePath/cache.log", date('Y-m-d H:i:s') . "\tpurgeall\n", FILE_APPEND);
			return ''; 
		case 'for':
			$time = $args->trimExpand(1,'1 day');
			$wgXzzCacheExpireTime =strtotime("now + $time"); 
			return '';
		case 'until':
			$time = $args->trimExpand(1,'now + 1 day');
			$wgXzzCacheExpireTime =strtotime($time); 
			return '';
		default:
			return $this->notFound();
		}
	}
	
	function hook_ArticleSave(&$article)
	{
		global $wgXzzCachePath;
		$title=$article->getTitle()->getFullText();
		$path = wfXzzCacheMakePath($title);
		@exec( "rm -rf $path" ); 
		@file_put_contents("$wgXzzCachePath/cache.log", date('Y-m-d H:i:s') . "\tedited\t$title\t$path\n", FILE_APPEND);
		return true; 
		
	}
	
	function hook_AfterOutputPage(&$outputText)
	{
		global 	$wgXzzCacheCandidate, 
				$wgXzzCachePath, 
				$wgXzzCacheFilePath,
				$wgXzzCacheFileName, 
				$wgXzzCacheExpireTime;
				
		global $wgUser;
		
		if ($outputText=='') return true;

		if ($wgXzzCacheCandidate && $wgXzzCacheExpireTime > strtotime('now') && !$wgUser->isLoggedIn()) {
			@mkdir($wgXzzCacheFilePath,0777,true);
			@file_put_contents($wgXzzCacheFilePath.'/'.$wgXzzCacheFileName,$outputText);
			@touch($wgXzzCacheFilePath.'/'.$wgXzzCacheFileName,$wgXzzCacheExpireTime);
			@file_put_contents("$wgXzzCachePath/cache.log", date('Y-m-d H:i:s') . 
				"\tstore\t{$_SERVER['REQUEST_URI']} --> $wgXzzCacheFilePath/$wgXzzCacheFileName\n", FILE_APPEND);
		}
		return true;
	}
};

