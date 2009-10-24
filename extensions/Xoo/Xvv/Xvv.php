<?php
#
#	Preliminaries, look lower for interesting stuff
#
if ( !defined( 'MEDIAWIKI' ) ) { die( 'This file is a MediaWiki extension, it is not a valid entry point' );}
if (defined('XVV_LOADED')) return; 
define ('XVV_LOADED', true);

XxxInstaller::Install('Xvv');
require_once (dirname(__FILE__).'/Expression.php');
require_once (dirname(__FILE__).'/CGI.php');
#
#  Xvv - Xoo Various Variables
#
#  Part of Xoo (c) 1997-2008 [[w:en:User:Zocky]], GPL3 applies
#
#########################################################################



#
#  	An extension for Mediawiki that allows casting of values to types
#
#	USAGE: 
#
#	VARIABLES:	
#	{{#var:local|name1=value1|name2=value2|...}}  		set local variable, available only in this frame
#	{{#var:local|name}}				  					get local variable
#	{{#var:local|name|default}}							get local variable, use default value if variable is not set
#
#	{{#var:global|name1=value1|name2=value2|...}}  		global variable, available in the whole document
#	{{#var:global|name}}				  		
#	{{#var:global|name|default}}				
#
#	{{#var:protected|name1=value1|name2=value2|...}}  	protected variable, available in this and child frames
#	{{#var:protected|name}}				  			
#	{{#var:protected|name|default}}					
#
#	ARRAYS:	
#	{{#arr:def|name1=value1|name2=value2|...}}			define an array, return array ID
#
#	{{#arr:foreach										iterate through an array, with {{{0}}} set to array key and {{{1}}} set to array value 
#	|array ID												ID of the array, produced by #arr:def and other functions
#	|name1=value1				
#	|name2=value2											
#	|#first 	= {{{name1}}} .... {{{name2}}}			expand this line on the first iteration
#	|#last		= ...								
#	|#notfirst	= ...
#	}}
#	{{#arr:map|
#
#
#  	BACKWARD COMPATIBLE:
#	{{#var:set|name|value}} set global variable
#	{{#var:get|name}}
#
#	TYPES:
#	* text		foobarbaz
#	* number	123.45
#	* page		[[foo]]
#	* ref		[[foo:bar]]
#	* date
#	* time
#	* datetime
#	* year
#
#	TODO: add formatting, etc. move types to separate classes
#
#########################################################################


class Xvv extends Xxx
{

#######################
##
##  Setup
##
#######################	
	var $mDefaultSettings = array
	(
	);

	function setupExtension()
	{
// The MAX_PATH below should point to the base of your OPENX adserver installation
     @include_once(MAX_PATH . '/www/delivery/alocal.php');
  	wfSetupSession();
  	}
	
############################
#
#	Array infrastructure
#
############################

	var $userArrays=array
	(
		'vars'=>array()
	);
	
	function hook_ParserBeforeStrip()
	{
		$this->userArrays=array
		(
			'vars'=>array()
		);
		return true;
	}

##############################3
#
#  Array helpers
#

	function arrMake($arr)
	{
		$id = Parser::getRandomString();
		$this->userArrays[$id] = $arr;		
		return $id;
	}


	function arrExists(&$id)
	{
		return is_array($this->userArrays[$id]);
		
	}
	
	function getArray(&$id)
	{
		return $this->userArrays[$id];
	}
	function arrGet(&$id)
	{
		return $this->userArrays[$id];
	}

##############################3
#
#  ItemHelpers
#

	function arrSetItem($id,$key,$value)
	{
		if ($key==='') $this->userArrays[$id][]=$value;
		else	$this->userArrays[$id][$key]=$value;
	}

	function arrUnsetItem($id,$key)
	{
		unset($this->userArrays[$id][$key]);
	}


	function arrGetItem($id,$key)
	{
		return $this->userArrays[$id][$key];
	}

	function arrItemExists($id,$key)
	{
		return isset($this->userArrays[$id][$key]);
	}


############################
#
#	{{#adserver:zone_id}}
#
############################
    
    var $wgBlockAdserver = true;
  
 	function fl_adserver(&$parser, &$f, $a)
	{   
	    #return(array('0'=>'R E K L A M A'));
	
		$args=new XxxArgs($f, $a);
		$zone_id= $args->command;
	    if (isset($zone_id)) {
			$phpAds_raw = view_local('', $zone_id, 0, 0, '', '', '0', array(),'');
			return array('0'=>$phpAds_raw['html'],'isHTML'=>true);
	    }
		return  $this->notFound();
	}

	

############################
#
#	{{#var:...}}
#
############################

	function flx_DOLLAR(&$parser,&$f, &$a)
	{
		$args=new XxxArgs(&$f,&$a);
		$currentId = 'vars';
		$varPathParts = split('#',$args->command);
		$lastKey=array_pop($varPathParts);
	
		$found = true;
		foreach($varPathParts as $v)
		{
			$newId = $this->arrGetItem($currentId,$v);
			if ($this->arrExists($newId))
			{
				$currentId = $newId;
			}
			else
			{
				$found=false;
				break;
			}
		}

		if ($found and $this->arrItemExists($currentId,$lastKey))
		{
			return array($this->arrGetItem($currentId,$lastKey));
		} 
		elseif ($args->exists(2))
		{
			return array($args->cropExpand(2));
		}
		else
		{
			return array('found'=>false);
		}
	}

 	function fl_var(&$parser, &$f, &$a)
	{
		$args=new XxxArgs($f, $a);


		$varPathParts = split('#',$args->trimExpand(1));
		

		switch ($args->command)
		{

		case '':
		case 'global':
			if ($args->isNamed(1))
			{
				for ($i=1;$i<=$args->count;$i++)
				{
					if (!$args->isNamed($i)) return $this->notFound();
					
					$currentId = 'vars';
					$varPathParts = split('#',$args->getName($i));
					$lastKey=array_pop($varPathParts);
			
					foreach($varPathParts as $v)
					{

						$newId = $this->arrGetItem($currentId,$v);
						if (!$this->arrExists($newId))
						{
							$newId=$this->arrMake(array());
							$this->arrSetItem($currentId,$v,$newId);
						}
						$currentId = $newId;
					}
		
					$val = $args->cropExpandValue($i);
					$this->arrSetItem($currentId, $lastKey, $val);
				}
			 	return "";
			 }
			 else
			 {
				$currentId = 'vars';
				$varPathParts = split('#',$args->trimExpand(1));
				$lastKey=array_pop($varPathParts);
				
				$found = true;
				foreach($varPathParts as $v)
				{
					$newId = $this->arrGetItem($currentId,$v);
					if ($this->arrExists($newId))
					{
						$currentId = $newId;
					}
					else
					{
						$found=false;
						break;
					}
				}
		
				if ($found and $this->arrItemExists($currentId,$lastKey))
				{
					return array($this->arrGetItem($currentId,$lastKey));
				} 
				elseif ($args->exists(2))
				{
					return array($args->cropExpand(2));
				}
				else
				{
					return array('found'=>false);
				}
			}

#################################
#
#	{{#var:set|name|value}}
#
		case 'unset':
			$id = 'vars';
			$key=array_pop($varPathParts);
			$this->arrUnsetItem($id,$key,'');
			return "";

		case 'set':
		case 'inc':
		case 'dec':
		case 'flip':
		case 'toggle':
		case 'concat':
			$currentId = 'vars';
			$lastKey=array_pop($varPathParts);
			
			foreach($varPathParts as $v)
			{

				$newId = $this->arrGetItem($currentId,$v);
				if (!$this->arrExists($newId))
				{
					$newId=$this->arrMake(array());
					$this->arrSetItem($currentId,$v,$newId);
				}
				$currentId = $newId;
			}
		
			$val = $args->cropExpand(2,1);
		switch ($args->command)
		{
		case 'set':	$this->arrSetItem($currentId, $lastKey, $val); return "";
		case 'inc':	$this->arrSetItem($currentId, $lastKey, $this->arrGetItem($currentId, $lastKey) + $val); return "";
		case 'dec':	$this->arrSetItem($currentId, $lastKey, $this->arrGetItem($currentId, $lastKey) - $val); return "";
		case 'concat':	$this->arrSetItem($currentId, $lastKey, $this->arrGetItem($currentId, $lastKey).$val); return "";
		case 'flip':	$this->arrSetItem($currentId, $lastKey, $this->arrGetItem($currentId, $lastKey) == $val ? $args->cropExpand(3,'') : $val); return ""; 
		case 'toggle':	$this->arrSetItem($currentId, $lastKey, $this->arrGetItem($currentId, $lastKey) == $val ? $args->cropExpand(3,'') : $val); return $this->arrGetItem($currentId, $lastKey); 
		}
			


#################################
#
#	{{#var:get|name|default}}
#
		
		case 'get':
			$currentId = 'vars';
			$lastKey=array_pop($varPathParts);
			
			$found = true;
			foreach($varPathParts as $v)
			{
				$newId = $this->arrGetItem($currentId,$v);
				if ($this->arrExists($newId))
				{
					$currentId = $newId;
				}
				else
				{
					$found=false;
					break;
				}
			}
			
			if ($found and $this->arrItemExists($currentId,$lastKey))
			{
				return array($this->arrGetItem($currentId,$lastKey));
			} 
			elseif ($args->exists(2))
			{
				return array($args->cropExpand(2));
			}
			else
			{
				return array('found'=>false);
			}

		case 'local':
			if (!$args->count) return $this->notFound();

			if ($args->isNamed(1))
			{
				foreach ($args->args as $i)
				{
					if ($args->isNamed($i))
					{
						$f->xvvLocalVariables[$args->getKey($i)]=$args->cropExpandValue($i);
					}
					else
					{
						return $this->notFound();
					}
				}
				return '';
			}
			else
			{
				$varName = $args->trimExpand(1);
				
				#do we already know this variable?
				if (isset($f->xvvLocalVariables[$varName]))
				{
					return $f->xvvLocalVariables[$varName];
				}
				else
				{
					#if not, maybe it's a template argument
					if ($f->isTemplate())
					{
						$value=$f->getArgument($varName);
						$f->xvvLocalVariables[$varName]=$value;
						if ($value!==false) return $value;
					}
				}
				
				#var not found, return default if any
				if ($args->exists(2))
				{
					return $args->cropExpand(2);
				}
				# nothing can be returned, so fail
				return $this->notFound();
			}
			warn ('cosmic ray in __FILE__ at line __LINE__');
			return $this->notFound(); 
			#return	array($f->virtualBracketedImplode( '{{{', '|', '}}}', $nameWithSpaces, $parts ), 'isLocalObj'=>true);
			
						
			
		case 'session':
			if (!isset($_SESSION['wsXoo'])) $_SESSION['wsXoo']=array();
			$ret='';
			if ($args->isNamed(1))
			{
				foreach ($args->args as $i)
				{
				 
					if ($args->isNamed($i))
					{
				        $varName=$args->getKey($i);
				        $varNameParts = explode ('#',$varName);
				        $varPtr =& $_SESSION['wsXoo'];
				        for ($j=0; $j < count($varNameParts)-1;$j++) 
				        {
				            $v=$varNameParts[$j];
				            if (!is_array($varPtr)) $varPtr = array();
				            if (!isset($varPtr[$v])) $varPtr[$v]='';
				            $varPtr =& $varPtr[$v];
				        }
				        $varKey=$varNameParts[$j];
				        
				        $varVal = $args->cropExpandValue($i);
				        if ($this->arrExists($varVal))
				           $varPtr[$varKey] = $this->arrGet($varVal);
				        else
				           $varPtr[$varKey] = $varVal;
					}
					else
					{
						return ''; $this->notFound();
					}
				}
			
				#print_r($_SESSION);
			    return '';	
			}
			else
			{
	            $varName=$args->trimExpand(1);
	            $varNameParts = explode ('#',$varName);
	            $varPtr=&$_SESSION['wsXoo'];
	            for ($i=0; $i<count($varNameParts)-1;$i++)
	            {
	                if (!is_array($varPtr) or !isset($varPtr[$varNameParts[$i]])) 
	                    return $args->cropExpandValue(2,$this->notFound());
	                
	                $varPtr =& $varPtr[$varNameParts[$i]];
	            }
	            $varKey=$varNameParts[$i];
	            if (!isset($varPtr[$varKey])) return $args->cropExpandValue(2,$this->notFound());
	            $varVal =& $varPtr[$varKey];
	            if (is_array($varVal))
	               return $this->arrMake($varVal);
	            else
	               return $varVal;
			}
		case 'cookie':
			$ret='';
			if ($args->isNamed(1))
			{
				foreach ($args->args as $i)
				{
					if ($args->isNamed($i))
					{
				        $varName=$args->getKey($i);
				        $varVal = $args->cropExpandValue($i);
				        setcookie($varName,$varVal,time()+60*60*24*30,'/',false, false, false);
					}
				}
			
				#print_r($_SESSION);
			    return '';	
			}
			elseif ($args->isNumbered(1))
			{
	            $varName=$args->trimExpand(1);
	            if (isset($_COOKIE[$varName]))
	            {
	            	return (string)$_COOKIE[$varName];
	            }
	            elseif ($args->exists(2))
	            {
	            	return $args->cropExpand(2);
	            }
	            else return $this->notFound();
			}
			else return $this->notFound();
		case 'inc':
		case 'dec':
		case 'odd':
		case 'even':
		default:
			return $this->notFound();
		}
	}

############################
#
#	{{#arr:...}}
#
############################

 	function fl_array(&$parser, &$f, $a)
 	{
 		return $this->fl_arr($parser,$f,$a);
 	}
	

 	function fl_arr(&$parser, &$f, $a)
	{
		$args=new XxxArgs($f, $a);
		
		switch ($args->command)
		{

		case 'dump':
			return '<pre>' . var_export($this->userArrays,true) . '</pre>';

		case 'def':
		case 'define':
			$arr=array();
			foreach($args->args as $i)
			{
				$arr[$args->getKey($i)] = $args->cropExpandValue($i);
			}
			$id = $this->arrMake($arr);
			return array('0'=>$id);
			
		case 'count':
			if ($args->count!=1 ) return array('found'=>false);

			$id  = $args->trimExpand(1);
			if (!$this->arrExists($id)) return 0;
			$r=count($this->getArray($id));
			return array('0'=>$r);		
		

		case 'series':
		    switch ($args->count)
		    {
		        case 1:
		            $from = 1;
		            $to   = (int)$args->trimExpand(1);
		            $step = 1;
                    $prefix = '';
		            $postfix = '';
		            break;
		        case 2:
		            $from = (int)$args->trimExpand(1);
		            $to   = (int)$args->trimExpand(2);
		            $step = 1;
                    $prefix = '';
		            $postfix = '';
		            break;
		        case 3:
		        case 4:
		        case 5:
		            $from = (int)$args->trimExpand(1);
		            $to   = (int)$args->trimExpand(2);
		            $step = abs((int)$args->trimExpand(3));
                    $prefix = $args->cropExpand(4,'');
		            $postfix = $args->cropExpand(5,'');
		    }
		    if ($from>$to) $step=-$step;
		    $arr=array();
		    #return "$from::$to::$step";
            for ($i = $from; $i<=$to; $i+=$step)
			{
				$arr[count($arr)+1] = $prefix.$i.$postfix;
			#	print $i;
			}
			$id = $this->arrMake($arr);
			return array('0'=>$id);



		case 'get':
		case 'getitem':
			if( $args->count < 2 or $args->count>3 ) return array( 'found' => false );
			$id   = $args->trimExpand(1);
			$key  = $args->cropExpand(2);

			if ($this->arrItemExists($id,$key))
			{
				return array($this->arrGetItem($id,$key));
			}
			elseif ($args->exists(3))
			{
				return array($args->cropExpand(3));
			}
			else
			{
				return array('found'=>false);
			}

		case 'set':
		case 'setitem':
			if( $args->count !=3 ) return array( 'found' => false );
			$id    = $args->trimExpand(1);
			$key   = $args->cropExpand(2);
			$value = $args->cropExpand(3);
			
			$this->arrSetItem($id,$key,$value);
			return "";

		case 'unset':
		case 'unsetitem':
			if( $args->count !=2 ) return array( 'found' => false );
			$id    = $args->trimExpand(1);
			$key   = $args->cropExpand(2);
			$this->arrUnsetItem($id,$key);
			return "";

		case 'split':

			if ($args->count < 2 || $args->count>3) return array('found'=>false);
			$sep  = $args->trimExpand(2); 
			$string  = $args->cropExpand(1);
			$count = (int)$args->trimExpand(3,-1);
			
			$arr=array();
			$id = $this->arrMake(preg_split("/$sep/",$string,$count));
			return array('0'=>$id);

		case 'join':			
			if ($args->count<1 ) return array('found'=>false);

			$id  = $args->trimExpand(1);
			if (!$this->arrExists($id)) return array('found'=>false);

			$glue = $args->cropExpand(2,'');
			$r=join($glue,$this->getArray($id));
			return array('0'=>$r);		

		case 'sort':
			if ($args->count!=1 ) return array('found'=>false);
			$id  = $args->trimExpand(1);
			if (!$this->arrExists($id)) return array('found'=>false);
			$thisArr = $this->getArray($id);
			sort($thisArr);
			$newId = $this->arrMake($thisArr);
			return array('0'=>$newId);			

		case 'keys':			
			if ($args->count!=1 ) return array('found'=>false);

			$id  = $args->trimExpand(1);
			if (!$this->arrExists($id)) return array('found'=>false);
			$newId = $this->arrMake(array_keys($this->getArray($id)));
			return array('0'=>$newId);		


		case 'filterkeys':			
			if ($args->count!=1 ) return array('found'=>false);

			$id  = $args->trimExpand(1);
			if (!$this->arrExists($id)) return array('found'=>false);
			$oldArray=$this->getArray($id);
			$newArray=array();
			
			foreach ($oldArray as $k=>$v)
			{
				if ($v)	$newArray[]=$k;
			}
			$newId = $this->arrMake($newArray);
			return array('0'=>$newId);


		case 'flip':			
			if ($args->count!=1 ) return array('found'=>false);

			$id  = $args->trimExpand(1);
			if (!$this->arrExists($id)) return array('found'=>false);
			$oldArray=$this->getArray($id);
			$newArray=array();
			
			foreach ($oldArray as $k=>$v)
			{
				$newArray[$v]=$k;
			}
			$newId = $this->arrMake($newArray);
			return array('0'=>$newId);

		case 'makeset':			
			if ($args->count!=1 ) return array('found'=>false);

			$id  = $args->trimExpand(1);
			if (!$this->arrExists($id)) return array('found'=>false);
			$oldArray=$this->getArray($id);
			$newArray=array();
			
			foreach ($oldArray as $k=>$v)
			{
				$newArray[$v]=true;
			}
			$newId = $this->arrMake($newArray);
			return array('0'=>$newId);
		
		case 'transpose':
			if ($args->count!=1 ) return array('found'=>false);
			$id  = $args->trimExpand(1);
			if (!$this->arrExists($id)) return array('found'=>false);
			$oldMatrix=$this->getArray($id);
			$newMatrix=array();

			foreach ($oldMatrix as $key1=>$val1)
			{
				if ($this->arrExists($val1))
				{
					$oldArray=$this->getArray($val1);
					foreach ($oldArray as $key2=>$val2)
					{
						$newArray[$key2][$key1]=$val2;
					}
				}
			}
			foreach ($newArray as $key=>$val)
			{
				$newArray[$key]=$this->arrMake($val);
			}
			return $this->arrMake($newArray);
		
		case 'foreach':
		case 'for each':
			if( $args->count != 3 ) return array( 'found' => false );

			$keyVar  = $args->getName(1);
			$itemVar = $args->trimExpandValue(1);
			
			$id  = $args->trimExpand(2);
			
			if (!$this->arrExists($id)) return array('found'=>false);

			$r="";
			foreach ($this->getArray($id) as $k=>$v)
			{
				$this->arrSetItem('vars',$itemVar,$v);
				if ($keyVar) $this->arrSetItem('vars',$keyVar,$k);
				
				$r .= $args->cropExpand(3);
			}
			return array('0'=>$r);			

		
		case 'list':

			if ($args->count != 1) return array('found'=>false);
			$id  = $args->trimExpand(1);
			
			if (!$this->arrExists($id)) return "$id"; array('found'=>false);

			$r=$this->arrRecursiveList('',$id,$f);

			return "<ul>$r</ul>";		

			
		case 'walk':

			if ($args->count != 4) return array('found'=>false);


			$keyVar  = $args->getName(1);
			$itemVar = $args->trimExpandValue(1);
			$id      = $args->trimExpand(2);
			
			if (!$this->arrExists($id)) return array('found'=>false);

			$nodeCode=$args->get(3);
			$leafCode=$args->get(4);
			
			$r=$this->arrRecursiveWalk('',$id,$keyVar,$itemVar,$nodeCode,$leafCode,$f);

			return array('0'=>$r);		

		case 'columnget':
		case 'columnmin':
		case 'columnmax':
		case 'columnfirst':
		case 'columnlast':
		case 'columncount':
		case 'columncounta':
		case 'columncountif':
		case 'columnaverage':
		case 'columnavg':
		case 'columnsum':
		case 'lookup':
		case 'lookupmax':
		case 'lookupmin':
		if ($args->count < 2) return array('found'=>false);
			$id = $args->trimExpand(1);
			if ($id==='') return "";
			if (!$this->arrExists($id)) return array('found'=>false);
			$thisArray = $this->getArray($id);
			$columnName = $args->trimExpand(2);
			unset ($ret);
			$count=0;
			foreach ($thisArray as $k=>$v)
			{
				$val = $this->arrGetItem($v,$columnName);
				switch ($args->command)
				{
				case 'lookup':
					if (!isset($ret))
					{
						if ($args->count<4) return $this->notFound();	
			 			$lookupValue=$args->trimExpand(3);
			 			$lookupColumn=$args->trimExpand(4);
			 			$lookupFound=false;
			 			$ret='';
			 		}
					elseif (!$lookupFound && (string)$val == (string)$lookupValue) 
					{
						$lookupFound=true;
						$ret = $this->arrGetItem($v,$lookupColumn);;
					}
					break;

				case 'lookupmin':
					if (!isset($ret))
					{
						if ($args->count<3) return $this->notFound();	
			 			$lookupColumn=$args->trimExpand(3);
			 			$lookupMin=$val;
						$ret = $this->arrGetItem($v,$lookupColumn);;
			 		}
					elseif ($val < $lookupMin) 
					{
						$lookupMin=$val;
						$ret = $this->arrGetItem($v,$lookupColumn);;
					}
					break;

				case 'lookupmax':
					if (!isset($ret))
					{
						if ($args->count<3) return $this->notFound();	
			 			$lookupColumn=$args->trimExpand(3);
			 			$lookupMax=$val;
						$ret = $this->arrGetItem($v,$lookupColumn);;
			 		}
					elseif ($val > $lookupMin) 
					{
						$lookupMax=$val;
						$ret = $this->arrGetItem($v,$lookupColumn);;
					}
					break;

				case 'columnget':
					if (!isset($ret)) $ret=array();
					$ret[$k] = $columnName;
					break;
				case 'columnmin':
					$val=(float)$val;
					if (!isset($ret)) $ret=$val;
					if ($val<$ret) $ret=$val;
					break;
				case 'columnmax':
					$val=(float)$val;
					if (!isset($ret)) $ret=$val;
					if ($val>$ret) $ret=$val;
					break;
				case 'columnfirst':
					if (!isset($ret)) $ret=$val;
					break;
				case 'columnlast':
					$ret=$val;
					break;
				case 'columncounta':
					if (!isset($ret)) $ret=0;
					if ($val) $ret++;
					break;
				case 'columncount':
					if (!isset($ret)) $ret=0;
					if ($this->arrItemExists($v,$columnName)) $ret++;
					break;
				case 'columncountif':
					if ($args->count<3) return $this->notFound();	
		 			$argArray=array(':#:'=>$k, ':$:'=>$val);
					$newFrame=$this->newExtendedFrame($f,$argArray);
		  			$r =trim($newFrame->expand($args->get(3)));
 					if($r) $ret++;
 					#$ret .=$r;
					break;
				case 'columnaverage':
				case 'columnavg':
					$val=(float)$val;
					if (!isset($ret)) $ret=$val;
					else $ret = ($ret * $count + $val) / ($count+1);
					break;
				case 'columnsum':
					if (!isset($ret)) $ret=0;
					$val=(float)$val;
					$ret +=$val;	
				}
				$count++;
			}
			if (is_array($ret)) return $this->arrMake($ret);
			else return $ret;


		case 'min':
		case 'max':
		case 'first':
		case 'last':
		case 'count':
		case 'counta':
		case 'countif':
		case 'average':
		case 'avg':
		case 'sum':
		if ($args->count < 1) return array('found'=>false);
			$id = $args->trimExpand(1);
			if ($id==='') return "";
			if (!$this->arrExists($id)) return array('found'=>false);
			$thisArray = $this->getArray($id);
			unset ($ret);
			$count=0;
			foreach ($thisArray as $k=>$val)
			{
				switch ($args->command)
				{
				case 'min':
					if (!is_numeric($val)) break;
					$val=(float)$val;
					if (!isset($ret)) $ret=$val;
					if ($val<$ret) $ret=$val;
					break;
				case 'max':
					$val=(float)$val;
					if (!isset($ret)) $ret=$val;
					if ($val>$ret) $ret=$val;
					break;
				case 'first':
					if (!isset($ret)) $ret=$val;
					break;
				case 'last':
					$ret=$val;
					break;
				case 'counta':
					if (!isset($ret)) $ret=0;
					if ($val) $ret++;
					break;
				case 'countif':
					if ($args->count<2) return $this->notFound();	
		 			$argArray=array(':#:'=>$k, ':$:'=>$val);
					$newFrame=$this->newExtendedFrame($f,$argArray);
		  			$r =trim($newFrame->expand($args->get(2)));
 					if($r) $ret++;
 					#$ret .=$r;
					break;
				case 'average':
				case 'avg':
					$val=(float)$val;
					if (!isset($ret)) $ret=$val;
					else $ret = ($ret * $count + $val) / ($count+1);
					break;
				case 'sum':
					if (!isset($ret)) $ret=0;
					$val=(float)$val;
					$ret +=$val;	
				}
				$count++;
			}
			if (is_array($ret)) return $this->arrMake($ret);
			else return $ret;

		case 'loop':
		case 'dataloop':
			if ($args->isNamed(1))
			{
				if ($args->count < 3) return array('found'=>false);

				$keyVar  = $args->getName(1);
				$itemVar = $args->trimExpandValue(1);
				$id      = $args->trimExpand(2);
			}
			else
			{
				$keyVar = ":#:";
				$itemVar = ":$:";
				$id      = $args->trimExpand(1);
			}
			# check arguments

			if ($id==='') return "";
			if (!$this->arrExists($id)) return array('found'=>false);
            
			# set initial state
			$returnText = "";
			$loopCounter = 0;
			$thisArray = $this->getArray($id);
#			unset ($thisArray[':#:']);
#			unset ($thisArray[':$:']);
			$loopLength = count($thisArray);

			$empty=true;
			$default=true;

			$condCache=array();
			$codeCache=array();
			
			# loop through rows
			if (count($thisArray)==0)
			{
				$ret="";
			    for ($i=2;$i<=$args->count;$i++)
			    {
				    # default to never
				    if ($args->isNamed($i) && $args->getName($i)=='empty')
				    {
					    $ret.=$args->cropExpandValue($i);
				    }
				}
				return $ret;
			}
			
			foreach ($thisArray as $k=>$v)
			{
			    $loopCounter++;
			    # new row needs new frame
			    $newFrame=null;
			
			    # loop through #arr:dataloop arguments
			    for ($i=2;$i<=$args->count;$i++)
			    {
				    # default to never
				    if (!$args->isNamed($i))
				    {
					    $found=false;
				    }
				    else
				    {
					    #figure out whether it needs to be shown
				    	if (isset($condCache[$i])) { $cond=$condCache[$i]; }
				    	else { $cond = $args->getName($i); $condCache[$i]=$cond; }
					    switch ($cond)
					    {
					    case 'never':		$found = false;							break;
					    case 'each':		$found = true;							break;
					    case 'numbered':	$found = is_numeric($k);				break;
					    case 'named':		$found = !is_numeric($k);				break;
					    case 'first':		$found = $loopCounter == 1;				break;
					    case 'last':		$found = $loopCounter == $loopLength;	break;
					    case 'notfirst':	$found = $loopCounter != 1;				break;
					    case 'notlast':		$found = $loopCounter != $loopLength;	break;
					    case 'odd':			$found = $loopCounter % 2 == 1;			break;
					    case 'even':		$found = $loopCounter % 2 == 0;			break;
					    default:			$cond = explode('%',$cond,2);
					    					if (count($cond)==2)
												$found	=
														(
															( is_numeric($cond[0]) || $cond[0]=='' ) 
															&& 
															is_numeric($cond[1]) && $cond[1] > 0
													   	)																
													   	? ( $loopCounter>=$cond[0] && ($loopCounter - $cond[0] ) % $cond[1]== 0) 
													   	: false;
											else
												$found = is_numeric($cond[0]) 
													   ? ($cond[0] == $loopCounter) 
													   : false;
					    }
				    }
				    if ($found) # should we try to show this row?
				    {
				    	# do we already have the frame?
						if (!$newFrame)
						{
							$argArray=array();
							if ($args->command == 'dataloop' && $this->arrExists($v))
							{
						    	$argArray = $this->getArray($v);
							} 
							$argArray[$itemVar]=(string)$v;
							$argArray[$keyVar]=(string)$k;
							$newFrame=$this->newExtendedFrame($f,$argArray);
						}
						
						# get the unexpanded value of the argument		
				    	if (isset($codeCache[$i])) { $code=$codeCache[$i]; }
				    	else {  
						    $argParts=$args->get($i)->splitArg();
						    $code=$argParts['value'];
							$codeCache[$i]=$code; 
						}			 
					    #expand and add to return
					    $returnText.=$args->cropSpace($newFrame->expand($code));
				    }
				}
			}
			if (trim($returnText) !='') return array('0'=>$returnText);		
			
			$ret="";
		    for ($i=2;$i<=$args->count;$i++)
		    {
			    # default, if no text gets output
			    if ($args->isNamed($i) && $args->getName($i)=='default')
			    {
				    $ret.=$args->cropExpandValue($i);
			    }
			}
			return $ret;	
						
		default: 
			return array('found'=>false);	
		}
	}

	function arrRecursiveWalk($key,&$item,$keyVar,$itemVar,&$nodeCode,&$leafCode,&$frame)
	{
		if ($this->arrExists($item))
		{
			foreach ($this->userArrays[$item] as $k=>$v)
			{
				$s .= $this->arrRecursiveWalk($k,$v,$keyVar,$itemVar,$nodeCode,$leafCode,$frame);
			}
			if($keyVar) $this->arrSetItem('vars',$keyVar,$key);
			$this->arrSetItem('vars',$itemVar,$s);
			return trim($frame->expand($nodeCode));
		}
		else
		{
			if($keyVar) $this->arrSetItem('vars',$keyVar,$key);
			$this->arrSetItem('vars',$itemVar,$item);
			return trim($frame->expand($leafCode));
		}
	}
	function arrRecursiveList($key,&$item,&$frame, $first=true)
	{
		if ($this->arrExists($item))
		{
			foreach ($this->userArrays[$item] as $k=>$v)
			{
				$s .= $this->arrRecursiveList($k,$v,$frame,false);
			}
			if ($first) return $s;
			return "<li>$key = <ul>$s</ul>";
		}
		elseif (isset($item))
		{
			return "<li>$key = $item";
		}
	}
	
	function fl_parent(&$parser, &$frame, &$a)
	{
		if (count($a)<2) return $this->notFound();
		$parentCount = $a[0];
		$command=$frame->expand($a[1]);
		if ($parentCount<0) return $this->notFound();
		array_shift($a);array_shift($a);
		array_unshift($a,$command);
		return $this->fl_this(&$parser, &$frame, &$a, $parentCount);
	}
	
	function fl_this(&$parser, &$frame, &$a, $parentCount=0)
	{
		$args=new XxxArgs($frame, $a);
		$f =& $frame;
		$base=0;
		for ($i=0;$i<$parentCount;$i++)
		{
			if (!$f->parent) return $this->notFound();
			$f =& $f->parent;
		}
		
		switch ($args->command)	
		{
		
		# {{#this:title}}
		case 'depth':
			if ($args->count != $base) return array('found'=>false);
			for ($i=0; $f->parent; $i++, $f=$f->parent);
			return $i;
		case 'title':
			if ($args->count != $base) return array('found'=>false);
		
			return $f->title->getFullText();
			
        # {{#this:parent}}
		case 'caller':
			if ($args->count != $base) return array('found'=>false);
			if (!$f->parent) return array('found'=>false);
			return $f->parent->title->getFullText();

        # {{#this:rawarg|name|default}}
		case 'rawarg':
			if ($args->count < $base+1 or $args->count > $base+2) return array('found'=>false);
			if (!$f->parent) return array('found'=>false);
			$name=($args->trimExpand($base+1));
			
			if (isset($f->namedArgs[$name]))
			{
				$val = $f->namedArgs[$name];
			}
			elseif (isset($f->numberedArgs[$name]))
			{
				$val = $f->numberedArgs[$name];			
			}
			else
			{
				return array('found'=>false);
			}
			return array(trim($f->parent->expand($val, PPFrame::RECOVER_ORIG)));

		case 'rawargnw':
			if ($args->count < $base+1 or $args->count > $base+2) return array('found'=>false);
			if (!$f->parent) return array('found'=>false);
			$name=($args->trimExpand($base+1));
			
			if (isset($f->namedArgs[$name]))
			{
				$val = $f->namedArgs[$name];
			}
			elseif (isset($f->numberedArgs[$name]))
			{
				$val = $f->numberedArgs[$name];			
			}
			else
			{
				return array('found'=>false);
			}
			return array(trim(htmlspecialchars($f->parent->expand($val, PPFrame::RECOVER_ORIG))), 'isHTML'=>true);


		# {{#this:args}}	
		case 'args':
			if ($args->count != $base+0) return array('found'=>false);
			if (!$f->parent) return array('found'=>false);
			$p = array();
			foreach($f->numberedArgs as $k=>$v)
			{
				$p[$k] = trim($f->parent->expand($v));
			}		
			foreach($f->namedArgs as $k=>$v)
			{
				$p[$k] = trim($f->parent->expand($v));
			}
			$id = $this->arrMake($p);
			return array('0'=>$id);
		
		# {{#this:args}}				
		case 'numberedargs':
			if ($args->count != $base+0) return array('found'=>false);
			if (!$f->parent) return array('found'=>false);
			$p = array();
			foreach($f->numberedArgs as $k=>$v)
			{
				$p[$k] = trim($f->parent->expand($v));
			}		
			$id = $this->arrMake($p);
			return array('0'=>$id);

		# {{#this:namedargs}}	
		case 'namedargs':
			if ($args->count != $base+0) return array('found'=>false);
			if (!$f->parent) return array('found'=>false);
			$p = array();
			foreach($f->namedArgs as $k=>$v)
			{
				$p[$k] = trim($f->parent->expand($v));
			}
			$id = $this->arrMake($p);
			return array('0'=>$id);

		default: 
			return array('found'=>false);	
		}
	}

	
	function hook_BeforeArgSubstitution(&$parser,&$frame,&$title, $argName, &$parts,&$result)
	{
		#$argName=mb_strtolower($argName);
		global $wgXxxCustomSkin;
		#if ($wgXxxCustomSkin->skinParsing) return true;
		if (!$parser->ot['html']) return true;
		$funcArgs=array($argName);
		for ( $i = 0; $i < $parts->getLength(); $i++ ) {
			$funcArgs[] = $parts->item( $i );
		}
		$args = new XxxArgs($frame,$funcArgs);		
	
		$p = explode('=',$args->command,2);
		if (count($p)==2)
		{
			$frame->xvvLocalVariables[trim($p[0])]=$p[1];			
#			print "setting {$p[0]} = {$p[1]}\n";
			foreach ($args->args as $i)
			{
				if ($args->isNamed($i))
				{
#					print($args->getKey($i));
					$val = $args->cropExpandValue($i);
					$name=$args->getName($i);
					$frame->xvvLocalVariables[$name]=$val;
#					print "setting $name = " . htmlspecialchars($val) . "\n";
				}
			}
			$result=array('text'=>'');
			return true;
		}
		else 
		{
			$name = trim($args->command);
			#do we already know this variable?
			if (isset($frame->xvvLocalVariables[$name]))
			{
				$val = $frame->xvvLocalVariables[$name];
#				print "getting $name = " . htmlspecialchars($val) ."\n";
				$result = array('text'=>$val);
			}
		}
		return true;
	}
	
/*
	function hook_AfterArgSubstitution(&$parser,&$frame,&$ret,&$result))
	{
		
	}
			#var not found, return default if any
			if ($args->exists(2))
			{
				
				if ($frame->isTemplate())
				{
					if ($varName===int($varName))
						$value=$frame->expand($frame->numberedArguments($varName));
					else
						$value=$frame->expand($frame->namedArguments[$varName]);
				}
			
				if ($value!==false)
				{ 
					if ($args->isNamed(1) && $args->getName(1)==='')
					{
						$value = $args->cropExpandValue(1);
						$frame->xvvLocalVariables[$varName]=$value;
						$result =  $value;
						return true;
					}
					else
					{
						$result = $args->cropExpand(1);
						return true;
					}
				}
				else
				{
					$result = $value;
					return true;
				}
			}
			return false;
			
			# nothing can be returned, so fail
#			return array(0=>$f->virtualBracketedImplode( '{{{', '|', '}}}', $args->command, $a ));
#			return $this->notFound();
		}
		die('cosmic ray');
		warn ('cosmic ray in __FILE__ at line __LINE__');
		return 'cosmic ray';$this->notFound(); 
	}	
*/	
	function fl_for(&$parser,&$f, &$a)
	{	
		global $wgOut;
  		$args=new XxxArgs($f,$a);
  		$varName=$args->command;

  		switch ($args->count)
  		{	
  		case 2: 
  			$from = 1;
  			$to = $args->trimExpand(1);
  			$step = 1;
  			$do = 2 ;
  			break;
  		case 3:
  			$from = $args->trimExpand(1);
  			$to = $args->trimExpand(2);
  			$step = 1;
  			$do = 3;
  			break;
  		case 4:
  			$from = $args->trimExpand(1);
  			$to = $args->trimExpand(2);
  			$step = $args->trimExpand(3);
  			$do = 4;
  			break;
  		default: 
  			return $this->notFound();
  		}
  #		return "$varName|$from|$to|$step|$do|".serialize($args->get($do));
  		$r='';
  		for ($i = $from; $i<=$to; $i+=$step)
  		{
  			$argArray=array($varName=>"$i");
			$newFrame=$this->newExtendedFrame($f,$argArray);
  			$r.=trim($newFrame->expand($args->get($do)));
  		}
  		return array(0=>$r,'isHTML'=>true);
	}

}
