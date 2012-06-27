<?php
#
#  Xxx - Xoo Extension Expander
#
#  Part of Xoo (c) 1997-2008 [[w:en:User:Zocky]], GPL3 applies
#
#########################################################################

#
#  An extension registerer with automagic registration for your hooks,
#  tags, variables and parser functions
# 
#  USAGE:
/*
	require_once ("Xoo.Xxx.php");
	MyExtension::Install( $settings );

  	class MyExtension extends XxxExtension
	{
		var $mExtensionCredits = array (...);
		var $mDefaultSettings = array('foo'=>'bar',...);
		var $mMessages = array('add-link'=>'Add link');
		function setup()  { .... if ($success) return true; else return false; }

		function var_foo  (&$parser) {};  # {{FOO}}
		function fnx_fpp   (&$parser, $cmd, $arg1, $arg2...) {}; {{fpp:cmd|arg1|...}}, precalculated args
		function flx_fqq   (&$parser, &$frame, &$args) {}; {{#fqq:cmd|arg1|...}}, precalculated args
		function fn_frr   (&$parser, $arg1, $arg2...) {}; {{frr:cmd|arg1|...}}, lazy args
		function fl_fss   (&$parser, &$frame, &$args) {}; {{#fss:cmd|arg1|...}}, lazy args
		function tag_ftt  ($text, $args, &$parser) {};
		function hook_fuu (&$parser, ...) {};
	}
*/
#	Use $this->mSettings to access effective settings.
#
#	TODO:
#	function special_foo() for special pages
#	XXX_HOOKMAP, and MyExtension->hookMap for hooks and functions with 
#	names which are not idents in PHP.
#
#########################################################################

if ( !defined( 'MEDIAWIKI' ) ) { die( 'This file is a MediaWiki extension, it is not a valid entry point' );}
define ('XXX_LATER', false);
define ('XXX_UNDEF', null);
define ('XXX_FAIL', null);

$wgExtensionFunctions[] = 'wfXxxSetup'; 

$wgXxxInstaller = new XxxInstaller();
$wgXooInstaller =& $wgXxxInstaller;

$wgXooExtensions = array();

function wfXxxSetup() 
{
	#TODO: set credits and stuff
	global $wgXxxInstaller;
	$wgXxxInstaller->installExtensions();
}

class Xxx
{
	var $mSettings=array();
	var $mDefaultSettings=array();
	var $mMessages;
	function setDefaultSetting($name,$value)
	{
		if ($this->mSettings[$name]===XXX_LATER)
		{
			$this->mSettings[$name]=$value;
		}
	}

	function S($name,$value=XXX_UNDEF)
	{
		if ($value!==XXX_UNDEF)
		{
			$this->mSettings[$name]=$value;
			return $value;
		}
		else
		{	
			return $this->mSettings[$name];
		}
	}
	
	function normalizeTitle($text)
	{
		$t=Title::newFromText($text);
		if ($t) return $t->getPrefixedDBkey();
		else return false;
	}


######################################################################

	static function newExtendedFrame (&$f, &$a=array(), $title=null)
	{
		$args = $a;
		if (isset($f->xvvLocalVariables)) $args = $args + $f->xvvLocalVariables; 
		if ($f->isTemplate())
			$args = $args + $f->namedArgs + $f->numberedArgs;
		elseif (isset($f->args))
			$args = $args + $f->args;
		else $args = $args;
		
		return self::newChildFrame($f,$args,$title);
	}
	
	static function newChildFrame (&$f, &$a, $title=null)
	{
		if (!$title) $title=$f->title;
		foreach ($a as $k=>$v) { if(!is_object($v)) $a[$k]=(string)$v; }
		return $f->newCustomChild($a,$f->title);
	}

	static function coallesce()
	{
		$args=func_get_args();
		foreach($args as $a) { if ($a) return $a; }
		return false;
	}

	static function extractArgs($args, $unnamedCount=100,$unnamedOffset=1)
	{
		$argNames = array_keys(array_splice(func_get_args(),2));
		$argNames=$argNames;
		$ret = array();
		$counter=1;
		for($i=$unnamedOffset;$i<=$args->count;$i++)
		{
			if ($args->isNamed($i))
			{
				$key=$args->getKey($i);
				if (isset($argNames[$key]))
					$ret[$key]=$args->cropExpandValue($i);
				elseif ($counter<$unnamedCount)
					$ret[$counter++]=$args->cropExpand($i);
			}
		}
		return $ret;
	}
	static function notFound()
	{
		return array('found'=>false);
	}

	static function foundUnlessFail(&$v)
	{
		if ($v===XXX_FAIL)
			return array('found'=>false);
		else
			return array($v);
	}
	
	static function removePrefix(&$v,$prefix)
	{
		if (substr($v,0,strlen($prefix))!=$prefix) return false;

		$v=substr($v,strlen($prefix));
		return true;
	}
	
	static function dumpVar($v)
	{
		return '<pre>'.var_export($v,true).'</pre>';
	}
	
	#	TODO: Make configurable
	
	function reportError($t,$where)
	{
		if ($class)
			echo ("ERROR in {$where}): $t<br>");
		else
			echo ("ERROR: $t<br>");
		return $t;
	}
	
	function makeOrPattern($strings)
	{
		$bits=array();
		$table=array();
		foreach ($strings as $v)
		{
			$table[$v]=strlen($v);
		}
		arsort($table);
		foreach ($table as $k=>$v)
		{
			$bits[]=preg_quote($k,'/');
		}
		return '(' . join('|',$bits) . ')';
	}
	
	function extractStart($regexp, &$text, $which=0, $flags='m')
	{
		if (!preg_match('/^'.$regexp.'(.*)$/'.$flags,$text,$m)) return false;
		if (count($m) < $which) return false;
      $rest =& $m[count($m)-1];
		if ($which==0) $ret = substr($text,0,strlen($text)-strlen($rest));
      else $ret = $m[$which];
		$text=$rest;
      return $ret;
	} 

	function setupExtension() {}

	function _setupExtension()
	{
		$varSettings = 'wg'.get_class($this).'Settings';

		global $$varSettings;

		$this->mSettings = $this->mDefaultSettings;
		
		if (is_array($$varSettings)) 
		{	
			foreach ($$varSettings as $k=>$v)
			{
				$this->mSettings[$k]=$v;
			}
		}
		global $wgMessageCache;
        if ($this->mMessages) $wgMessageCache->addMessages($this->mMessages);
		$this->setupExtension();
		return true;
	}
}


class XxxInstaller
{
	var $mExtensions=array();
	var $mExtensionNames=array();
	var $mInstalled=false;
	
################################
#
#	main entry point
#
################################

	public static function Install($classname)
	{
		global $wgXxxInstaller;
		$wgXxxInstaller->registerExtension($classname);
	}

	public static function getExtension($e)
	{
		global $wgXooExtensions;
		return $wgXooExtensions[$e];
	}

	
	public function registerExtension ($className)
	{
		if ($this->mExtensions[$className]) 
		{
			warn("Xxx: Extension $className already registered.");
			return false;
		}
		$this->mExtensions[] = $className;
	}
	
	public function installExtensions()
	{	
		if ($this->mInstalled) return true;
		$this->mInstalled = true;
		global $wgHooks, $wgParser, $wgXooExtensions;
	
		foreach ($this->mExtensions as $e)
		{
			$wgXooExtensions[$e] = new $e;
			$this->autoRegister($wgXooExtensions[$e]);
			$wgXooExtensions[$e]->_setupExtension();
		}
		
		#register the magic word hook, if needed
		if (count($this->magicWords)) 
		{
			$wgHooks['LanguageGetMagic'][] = array( &$this,'hookGetMagicWords');
		}
		
		#register variable hooks, if needed
		if (count($this->customVariables)) 
		{
			$wgHooks['MagicWordwgVariableIDs'][] = array( &$this,'hookVariableIDs');
			$wgHooks['ParserGetVariableValueSwitch'][] = array( &$this,'hookVariableValueSwitch');
		};

		#register all outstanding hooks
		foreach($this->hashFunctions as $n=>&$e)
		{
			$wgParser->setFunctionHook
			(
				$n,
				$e 
			);
		}
		foreach($this->parserFunctions as $n=>&$e)
		{
			$wgParser->setFunctionHook
			(
				$n,
				$e,
				SFH_NO_HASH 
			);
		}
		foreach($this->lazyHashFunctions as $n=>&$e)
		{
			$wgParser->setFunctionHook
			(
				$n,
				$e,
				SFH_OBJECT_ARGS 
			);
		}
		foreach($this->lazyFunctions as $n=>&$e)
		{
			$wgParser->setFunctionHook
			(
				$n,
				$e,
				SFH_OBJECT_ARGS | SFH_NO_HASH
			);
		}
		foreach($this->parserTags as $n=>&$e)
		{
			$wgParser->setHook
			(
				$n,
				$e 
			);
		};
	}
	
	function autoRegister(&$e)
	{
		$methods=get_class_methods($e);
		
		foreach($methods as $m)
		{
			$parts = explode("_",$m,2);
			{
				if (count($parts)==2)
				{
					$method = $m;
					$name=preg_replace('/DOLLAR/','\$',$parts[1]);
					switch($parts[0])
					{
					case 'var':
						$this->newVariable($e,$method,$name);
						break;
					case 'fn':
						$this->newHashFunction($e,$method,$name);
						break;
					case 'fx':
						$this->newParserFunction($e,$method,$name);
						break;
					case 'fl':
						$this->newLazyHashFunction($e,$method,$name);
						break;
					case 'flx':
						$this->newLazyFunction($e,$method,$name);
						break;
					case 'tag':
						$this->newParserTag($e,$method,$name);
						break;
					case 'hook':
						$this->newHook($e,$method,$name);
					}
				}
			}
		}
	}

	# Handle magic words for this extension
	# --> newMagicWord($word)

	var $magicWords=array();

	function hookGetMagicWords( &$magicwords) 
	{
		foreach ($this->magicWords as $w)
		{
	   	$magicwords[$w] = array( 0, $w );
		}
		return true;
	}

	function newMagicWord($word)
	{
		$this->magicWords[]=strtolower($word);
		return strtolower($word);
	}
	
	# Custom variables
	# -->newCustomVariable
	
	var $customVariables=array();

	function hookVariableIDs(&$wgVariableIDs ) 
	{
		foreach ($this->customVariables as $k=>$e)
		{
			$wgVariableIDs[] = strtolower($k);
		}
		return true;
	}

	function hookVariableValueSwitch( &$parser, &$varCache, &$index, &$ret ) 
	{
		if ($this->customVariables[strtolower($index)])
		{
			$i=strtoupper($index);
			$ret=$this->customVariables[strtolower($index)][0]->{"var_$i"}($parser,$varCache, $index);
			return true;		
		}
		else
		{
			return false;
		}
   }

	# var_FOO -> {{FOO}}
	function newVariable($e, $method, $name)
	{
		$this->customVariables[$this->newMagicWord(strtolower($name))]=array($e,$method); 
		return $name;
	}

	# fn_foo -> {{#foo:}} function
	var $hashFunctions=array();
	
	function newHashFunction($e,$method,$name)
	{
		$name=strtolower($name);
		$this->hashFunctions[$this->newMagicWord($name)]=array($e,$method);
	}

	# fl_foo {{#foo:}} function, lazy parameters
	var $lazyHashFunctions=array();
	
	function newLazyHashFunction($e,$method,$name)
	{
		$name=strtolower($name);
		$this->lazyHashFunctions[$this->newMagicWord($name)]=array($e,$method);
	}

	# fnx_foo -> {{foo:}} function
	var $parserFunctions=array();
	
	function newParserFunction($e,$method,$name)
	{
		$name=strtolower($name);
		$this->parserFunctions[$this->newMagicWord($name)]=array($e,$method);
	}

	# flx_foo {{foo:}}, lazy parameters 
	var $lazyFunctions=array();

	function newLazyFunction($e,$method,$name)
	{
		$name=strtolower($name);
		$this->lazyFunctions[$this->newMagicWord($name)]=array($e,$method);
	}

	# tag_ parserTags
	var $parserTags=array();
	
	function newParserTag($e,$method,$name)
	{
		$name=strtolower($name);
		$this->parserTags[$this->newMagicWord($name)]=array($e,$method);
	}

	
	# general hooks
	function newHook($e,$method,$name)
	{
		global $wgHooks;
		$wgHooks[$name][]=array( &$e, $method );
	}
}

class XxxParserFunction extends Xxx {
	function dispatch($func,&$P,&$F,&$A) {
		$args=new XxxArgs($F,$A);
		$method="{$func}_{$args->cmd}";
		$default = "{$func}__def";
		if(method_exists($this,$method)) {
			$this->method($P,$F,$args);
		} elseif(method_exists($this,$default)) {
			$this->$default($P,$F,$args);
		} 
	}
}
#################################################################
#
#	XxxArgs
#
#	Wrapper for preprocessor frame arguments in fl_foo functions.
#
#	usage: $args = new XxxArgs($frame, $args);
#
#################################################################


class XxxArgs
{
	var $mFrame = null;
	var $mArgs = array();
	var $mBits = array();
   var $count = 0;

	function __construct(&$frame,&$args)
	{
		$this->mFrame=&$frame;
		$this->mArgs=&$args;

		$this->command=$this->trimExpand(0);
		$this->count=count($this->mArgs)-1;
		$this->args=array_slice(array_keys($args),1);
		$this->numbers=$this->args;
	}

	function get($index,$default=null)
	{
		if (!$this->exists($index) && $default !== null) return $default;
		return $this->mArgs[$index];
	}

	function getRaw($index,$default='')
	{
		if (!$this->exists($index)) return $default;
		return $this->mFrame->expand($this->mArgs[$index], PPFrame::RECOVER_ORIG );
	}	

	
	function expand($index,$default='')
	{
		if (!$this->exists($index)) return $default;
		return ($this->mFrame ? $this->mFrame->expand($this->mArgs[$index]) : 'error');
	}	

	function cropExpand($index,$default='')
	{
		if (!$this->exists($index)) return $default;
		return $this->cropSpace($this->mFrame->expand($this->mArgs[$index]));
	}	

	function trimExpand($index,$default='')
	{
		if (!$this->exists($index)) return $default;
		if(!is_object($this->mFrame)) {
			print wfBacktrace();
			die('hello');
		}
		return trim($this->mFrame->expand($this->mArgs[$index]));
	}	
	
	function cropSpace($text)
	{
		$text=preg_replace('/^[\t ]*(\n[ \t]*)?/s','',$text);
		$text=preg_replace('/[\t ]*(\n[ \t]*)?$/s','',$text);
		return $text;
	}
	
	function isNamed($index)
	{
		$this->split($index);
		return $this->mBits[$index]['index']==='';
	}
	
	function isNumbered($index)
	{
		$this->split($index);
		return $this->mBits[$index]['index']!=='';
	}
	
	function getName($index)
	{
		return $this->isNamed($index) ? trim($this->mFrame->expand($this->mBits[$index]['name'])) : false;
	}

	function getIndex($index)
	{
		return $this->isNumbered($index) ? $this->mBits[$index]['index'] : false;
	}
	
	function getKey($index)
	{
		return $this->isNumbered($index) ? $this->getIndex($index) : $this->getName($index);
	}

	function expandKey($index,$default='')
	{
		if (!$this->exists($index)) return $default;
		return $this->getKey($index);
	}

	function getValue($index,$default='')
	{
		if (!$this->exists($index)) return $default;
		$this->split($index);
		return $this->mBits[$index]['value'];
	}	
	
	function getRawValue($index,$default='')
	{
		if (!$this->exists($index)) return $default;
		$this->split($index);
		return $this->mFrame->expand($this->mBits[$index]['value'], PPFrame::RECOVER_ORIG );
	}	
	
	function expandValue($index,$default='')
	{
		if (!$this->exists($index)) return $default;
		$this->split($index);
		return $this->mFrame->expand($this->mBits[$index]['value']);
	}	

	function cropExpandValue($index,$default='')
	{
		if (!$this->exists($index)) return $default;
		$this->split($index);
		return $this->cropSpace($this->mFrame->expand($this->mBits[$index]['value']));
	}	

	function trimExpandValue($index,$default='')
	{
		if (!$this->exists($index)) return $default;
		$this->split($index);
		return trim($this->mFrame->expand($this->mBits[$index]['value']));
	}	

	function exists($index)
	{
		return $index <= $this->count;
	}	

	
	function split($index)
	{
		if (!isset($mBits[$index]))
		{
			$this->mBits[$index] = $this->mArgs[$index]->splitArg();
		}
	}
}

function wfCoallesce()
{
	$args = func_get_args();
	foreach ($args as $arg)
	{
		if ($arg) return $arg;
	}
	return false;
}

