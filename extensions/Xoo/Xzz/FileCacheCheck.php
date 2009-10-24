<?php

####################################################
#
#   Xoo / Xzz / File Cache for MediaWiki
#
#  (c) 2009 [[w:en:User:Zocky]], GPL 3.0 or later
#
####################################################

# flags for later use
$wgXzzCacheCandidate = false;
$wgXzzCacheExpireTime = 0;

# run the check
if ($wgXzzCachePath) wfXzzCacheCheck();


function wfXzzCacheMakePath($title)
{
	global $wgXzzCachePath;
	$title=str_replace(' ','_',$title);
	$title=urlencode($title);
	$titleHash=md5($title);		
	return "$wgXzzCachePath/{$titleHash[0]}{$titleHash[1]}/$title";
}

function wfXzzCacheCheck()
{
	global $wgXzzCachePath;
	global $wgXzzCacheLog;

	# global flags and vars that we need to set
	global $wgXzzCacheFilePath;	
	global $wgXzzCacheFileName;	
	global $wgXzzCacheCandidate;
	global $wgXzzSiteName;
		
	# check if the user is logged in
	# if so, we're only interested in the purging
	
	if ($_COOKIE["{$wgXzzSiteName}UserID"])
	{
		if ($_GET['action']=='purge')
		{
			$title=$_GET['title'];
			$path = wfXzzCacheMakePath($title);
			@exec( "rm -rf $path" ); 
			if ($wgXzzCacheLog==true)
				@file_put_contents("$wgXzzCachePath/cache.log", date('Y-m-d H:i:s') . "\tpurge\t$title\t$path\n", FILE_APPEND);
		}
		elseif ($_GET['action']=='purgeall')
		{
			$_GET['action']='purge';
			@exec( "rm -rf $wgXzzCachePath/??/" );	
			if ($wgXzzCacheLog==true)
				@file_put_contents("$wgXzzCachePath/cache.log", date('Y-m-d H:i:s') . "\tpurgeall\n", FILE_APPEND);
		}
		elseif ($_GET['action']=='purgecachelog')
		{
			$_GET['action']='purge';
			if ($wgXzzCacheLog==true)
			{
				@file_put_contents ("$wgXzzCachePath/cache.old.log", @file_get_contents("$wgXzzCachePath/cache.log"), FILE_APPEND);
				@file_put_contents("$wgXzzCachePath/cache.log", date('d.m.Y H:i:s') . "\tnewlog\n");
			}
		}
	}
	else
	{ 
		# if the user is logged out, we filter any action that's not render or raw
		if ($_GET['action']!='render' && $_GET['action']!='raw' ) unset($_GET['action']); 
		unset($_GET['oldid']);
		unset($_GET['previd']);
		
		# figure out if we're interested in caching at all
		if ( $_SERVER['REQUEST_METHOD']!='GET' )
		{
			# no, so set the flag and get out of here
			$wgXzzCacheCandidate = false;

			# log a nocache
			if ($wgXzzCacheLog==true)
				@file_put_contents ("$wgXzzCachePath/cache.log", date('Y-m-d H:i:s') . "\tnocache\t$uri\n", FILE_APPEND);
			return;
		}

		# yes, so see if we have this page cached
		
		# make the path from the title
		$wgXzzCacheFilePath = wfXzzCacheMakePath($_GET['title']);

		# make a hash out of the arguments, sans action and title
		$args=$_GET;
		unset ($args['title']);
		unset ($args['action']);
		ksort($args);
		$args=print_r($args,true);
	 	$argHash = md5($args);
	 	
	 	# construct the filename
		$wgXzzCacheFileName = "{$GET['action']}_$argHash.html";
		
		# construct the full file name for local use
		
		$file = "$wgXzzCacheFilePath/$wgXzzCacheFileName";
		$uri= $_SERVER['REQUEST_URI'];
		# check if the file exists
		if (file_exists($file))
		{
			# is its last modified time in the future?
			$stat=stat("$file");
			if ($stat[9] > strtotime('now'))
			{
				# it is, so let's give the expires header, show the cached file and die				
				session_start();
				header("Expires: " . date('r',$stat[9]));
				readfile($file);
				
				# log a hit
				if ($wgXzzCacheLog==true)
					@file_put_contents ("$wgXzzCachePath/cache.log", date('Y-m-d H:i:s') . "\thit\t$uri\t$file\n", FILE_APPEND);
				die();
			}
			else
			{
				# it's too old, so delete it, set the candidate flag, and return 
				@unlink("$file");
				$wgXzzCacheCandidate = true;

				# log expired
				if ($wgXzzCacheLog==true)
					@file_put_contents ("$wgXzzCachePath/cache.log", date('Y-m-d H:i:s') . "\texpired\t$uri\t$file\n", FILE_APPEND);
				return;
			}
		}
		else
		{
			# we don't have it, set the candidate flag and return 
			$wgXzzCacheCandidate = true;
			
			# log a miss
			if ($wgXzzCacheLog==true)
				@file_put_contents ("$wgXzzCachePath/cache.log", date('Y-m-d H:i:s') . "\tmiss\t$uri\t$file\n", FILE_APPEND);
			return;
		}
	}
}
