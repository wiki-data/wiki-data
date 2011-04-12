<?php
#
#	Preliminaries, look lower for interesting stuff
#
if ( !defined( 'MEDIAWIKI' ) ) { die( 'This file is a MediaWiki extension, it is not a valid entry point' );}
if (defined('XTT_LOADED')) return; 
define ('XTT_LOADED', true);

XxxInstaller::Install('Xtt');
#
#  Xtt - Xoo Text and other Types
#
#  Part of Xoo (c) 1997-2008 [[w:en:User:Zocky]], GPL3 applies
#
#########################################################################



#
#  	An extension for Mediawiki that allows casting of values to types
# 
#  	USAGE:
#	{{#cast:type|value|escape}} returns cast value
#
#	TYPES:
#	* text		foobar'baz
#	* number	   123.45E+12
#	* integer	123
#	* page		[[foo]]
#	* ref		   [[foo:bar]]
#	* date
#	* time
#	* datetime
#	* year
#
#	TODO: add formatting, etc. move types to separate classes
#
#########################################################################


class Xtt extends Xxx
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
	}
	
#######################
##
##  Member variables
##
#######################	

	var $mTypes=array
	(
		'blob'		 => true,
		'text'       => true,
		'number'     => true,
		'integer'    => true,
#		'angle'      => true,
		'year'       => true,
		'date'       => true,
		'datetime'   => true,
		'time'       => true,
		'boolean' 	 => true,
		'page'    	 => true,
		'reference'  => true
	);

###########################################################
##
##	function for guessing values from strings
##
###########################################################

static function guessValue($type,&$value)
	{
		$value = (string)$value;
		switch ($type)
		{
		case 'number'   :
			$value = preg_replace ('/<.*?>/','',$value);
			$value = preg_replace ('/\[\[(.*?)(#.*?)?(\|.*?)?\]\]/','$1$2',$value);
			if(!preg_match('/^[\s\S]*?(-?[0-9,]+(\.[0-9]+)?)[\s\S]*$/',$value,$m)) return false;
			$value = (float)(str_replace(',','',$m[1])); 
			return true;
		case 'integer'  : $value = (int)$value; 						
			$value = preg_replace ('/<.*?>/','',$value);
			$value = preg_replace ('/\[\[(.*?)(#.*?)?(\|.*?)?\]\]/','$1$2',$value);
			if(!preg_match('/^[\s\S]*?(-?[0-9,]+)[\s\S]*$/',$value,$m)) return false;
			$value = (int)(str_replace(',','',$m[1])); 
			return true;
#		case 'angle':
#			return false;
		case 'year'		: 
			if(!preg_match('/^[\s\S]*?([0-9]{1;4})[\s\S]*$/',$value,$m)) return false;
			$value = (int)$m[1]; return true;
		case 'date'		:
			$value = preg_replace ('/<.*?>/','',$value);
			$value = preg_replace ('/\[\[(.*?)(#.*?)?(\|.*?)?\]\]/','$1$2',$value);
			if ( preg_match('/(\d\d\d\d-\d\d?-\d\d?)/',$value,$m)) {
				$value=$m[1];
			}
			if ($value!='') $value = self::formatTime('Y-m-d',(string)$value); return true;
		case 'datetime' : 
			$value = preg_replace ('/<.*?>/','',$value);
			$value = preg_replace ('/\[\[(.*?)(#.*?)?(\|.*?)?\]\]/','$1$2',$value);
			if ( preg_match('/(\d\d\d\d-\d\d-\d\d\s+\d\d:\d\d(:\d\d)?)/',$value,$m)) $value=$m[1];
			if ($value!='') $value = self::formatTime('Y-m-d H:i:s', (string)$value); return true; 
		case 'time'		: 
			$value = preg_replace ('/<.*?>/','',$value);
			$value = preg_replace ('/\[\[(.*?)(#.*?)?(\|.*?)?\]\]/','$1$2',$value);
			if ( preg_match('/(\d\d:\d\d(:\d\d)?)/',$value,$m)) $value=$m[1];
			if ($value!='') $value = self::formatTime('H:i:s',(string)$value); return true; 
		case 'boolean':
			if (strtolower(trim($value))=='false' or !(trim($value)))	     
								 { $value = 0; return true; }
			else			     { $value = 1;  return true; }
		case 'page':
			if ( preg_match('/\[\[(.*?)(#.*?)?(\|.*?)?\]\]/',$value,$m)) $value=$m[1];			
			$t=Title::newFromText($value);
			if ($t) return $t->getPrefixedDBkey($value);
			else	return false;
		
		case 'multi':
			$multi = array();
			$value=preg_replace('/<\s*br(\s.*?)?\/?>/i',',',$value);
			$value = preg_replace ('/<.*?>/','',$value);
			$value = preg_replace ('/\[\[(.*?)(#.*?)?(\|.*?)?\]\]/','$1$2',$value);
			$arr = preg_split('/\s*(,|\sand\s)\s*/i',$value);
			$value=array();
			foreach ($arr as $k=>$v) {
				self::castValue('reference',$v);
				$value[$k]=$v;
			}
			return true;
		case 'reference':
			if ( preg_match('/\[\[(.*?)(#.*?)?(\|.*?)?\]\]/',$value,$m)) $value=$m[1].$m[2];			
			$ref_parts = explode('#',$value); 
			if (count($ref_parts)==2)
			{
				$ref_page=$ref_parts[0]; $ref_name=$ref_parts[1];
				if (self::normalizeName($ref_name))
				{
					$t=Title::newFromText($ref_page);
					if ($t)	{ $value=$t->getPrefixedDBkey()."#".$ref_name; return true;}
				}
			}
			elseif (count($ref_parts)==1)
			{
				$value=self::normalizeName($value);
				return true;
			}
			return false;

		case 'text' 	: $value= (string)$value; return true;
		case 'blob' 	: $value= (string)$value; return true;
			default			: return  false;
		}
	}
###########################################################
##
##	function for casting values to types
##
###########################################################

	static function castValue($type,&$value)
	{
		switch ($type)
		{
			case 'number'   : $value = (float)$value; 						return true;
			case 'integer'  : $value = (int)$value; 						return true;
#			case 'angle'	 : 
#									if (preg_match('/^([+-]?\d\d?\d?)\s*(\s|˚|°)\s*(\d\d?)\s*([\s\']\s*(\d\d?(\.\d*)?)?\s*(\'\'|"|)\s*)?\s*([SNEWsnew])?\s*$/',$value, $m))
#									{
#									    $value = $m[1] + $m[3]/60 + $m[5]/3600;
#									    if (strtolower($m[7])=='w' or strtolower($m[7])=='s') $value = -$value;
#									    return true;
#									}
#									$value = (float) $value;
#									#print_r($value);
#									return true;
									
			case 'year'		: 
				if ($value!='') $value = self::formatTime('Y',(int)$value.'-1-1'); return true;
			case 'date'		: 
				if ($value!='') $value = self::formatTime('Y-m-d',(string)$value); return true;
			case 'datetime' : 
				if ($value!='') $value = self::formatTime('Y-m-d H:i:s', (string)$value); return true; 
			case 'time'		: 
				if ($value!='') $value = self::formatTime('H:i:s',(string)$value); return true; 	return true;
			case 'boolean':
				if (strtolower(trim($value))=='false' or !(trim($value)))	     
									 { $value = 0; return true; }
				else			     { $value = 1;  return true; }
				
			case 'page':
				$t=Title::newFromText($value);
				if ($t) return $t->getPrefixedDBkey($value);
				else	return false;
			
			case 'reference':
				$ref_parts = explode('#',$value); 
				if (count($ref_parts)==2)
				{
					$ref_page=$ref_parts[0]; $ref_name=$ref_parts[1];
					if (self::normalizeName($ref_name))
					{
						$t=Title::newFromText($ref_page);
						if ($t)	{ $value=$t->getPrefixedDBkey()."#".$ref_name; return true;}
					}
				}
				elseif (count($ref_parts)==1)
				{
					$value=self::normalizeName($value);
					return true;
				}
				return false;

			case 'text' 	: $value= (string)$value; return true;
			case 'blob' 	: $value= (string)$value; return true;
			default			: return  false;
		}
	}
	
	static function formatTime($format,$time)
	{
		$dt = date_create($time);
		if(!$dt) return '';
		return date_format($dt,$format);
	}

	function displayValue($type,$value)	#TODO: use properly localized display dates from mediawiki settings
	{
		switch ($type)
		{
			case 'number'   : $value = (float)$value; 						return $value;
			case 'integer'   : $value = (int)$value; 						return $value;
#			case 'angle'   : $value = (float)$value; 						return $value;
			case 'year'		: 
				if ($value!='') $value = date('Y',strtotime((string)$value));				return $value;
			case 'date'		: 
				if ($value!='') $value = date('d.m.Y',strtotime((string)$value));			return $value;
			case 'datetime' : 
				if ($value!='') $value = date('d.m.Y H:i:s',strtotime((string)$value));		return $value;
			case 'date'		: 
				if ($value!='') $value = date('H:i:s',strtotime((string)$value));			return $value;
			case 'boolean':
				if (!$value) 			return ''; 
				else					return 'TRUE'; 
				
			case 'page':
				$t=Title::newFromText($value);
				if ($t) return '[['.$t->getFullText($value).']]';
				else	return $value;
			
			case 'reference':
				$ref_parts = explode('#',$value); 
				if (count($ref_parts)==2)
				{
					$ref_page=$ref_parts[0]; $ref_name=$ref_parts[1];
					if ($this->normalizeName($ref_name))
					{
						$t=Title::newFromText($ref_page);
						if ($t)	{ $value='[['.$t->getFullText()."#".preg_replace('/_/',' ',$ref_name).']]'; return $value;}
					}
				}
				elseif (count($ref_parts)==1)
				{
					$value = $this->normalizeName($value);
					return $value;
				}
				return $value;

			case 'blob' 	: return (string)$value;
			case 'text' 	: return (string)$value;
			default			: return  false;
		}
	}

	function getDbFieldType($type,$db='mysql') 
	{
		static $dbFieldTypeMap=array
		(
			'mysql' => array
			(
				'text'       => 'TEXT',
				'blob'       => 'LONGBLOB',
				'number'     => 'FLOAT',
#				'angle'		 => 'FLOAT',
				'integer'    => 'INT(11)',
				'year'       => 'YEAR',
				'date'       => 'DATE',
				'datetime'   => 'DATETIME',
				'time'       => 'TIME',
				'boolean' 	 => 'BOOL',
				'page'    	 => 'CHAR(255)',
				'reference'  => 'CHAR(255)'
			),
			'postgres' => array
			(
				'text'       => 'TEXT',
				'blob'       => 'LONGBLOB',
				'number'     => 'NUMERIC',
#				'angle'		 => 'FLOAT',
				'integer'    => 'INTEGER',
				'year'       => 'YEAR',
				'date'       => 'DATE',
				'datetime'   => 'DATETIME',
				'time'       => 'TIME',
				'boolean' 	 => 'BOOL',
				'page'    	 => 'CHAR(255)',
				'reference'  => 'CHAR(255)'
			)
		);
	
		return $dbFieldTypeMap[strtolower($db)][strtolower($type)];
	}
	
###################################
##
##  {{#cast:type|value|escape}} 
##
###################################
	
	function fl_cast($parser,&$f,&$a)
	{
		$args=new XxxArgs($f,$a);
		if ($args->count<1 or $args->count>2) return $this->notFound();
		$type=$args->command;
		$value=$args->cropExpand(1);
		$format = $args->cropExpand(2,false);
		
		if (!$format)
		{
			return $this->displayValue($type,$value);
		}
		
		$cast =  $this->castValue($type,$value);
	    	    
	    switch ($args->trimExpand(2,''))
	    {
        case 'slashes':
        case 'esc':
            return addslashes($value); 
        case 'db':
            switch ($this->getDbFieldType($value))
            {
            case 'BOOL':
            case 'INT':
            case 'FLOAT':
                return $value;
            default:
                return "'".addslashes($value)."'";
            }
        case 'html':
            return htmlspecialchars($value);
	    }
	    return $this->notFound();
	}	
	static function normalizeName(&$s)
	{
		if ($s==='' or preg_match('/[#@?&<>=:]/',$s)) return false;
		$s=trim(preg_replace('/[_\s]+/',' ',$s));
		if (is_numeric($s)) return false;
		$t=Title::newFromText($s);
		if (!$t) return false;
		$s=$t->getDBkey();
		return $s;
	}
}



