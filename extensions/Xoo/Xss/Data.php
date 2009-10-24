<?php
#
#	Preliminaries, look lower for interesting stuff
#
if ( !defined( 'MEDIAWIKI' ) ) { die( 'This file is a MediaWiki extension, it is not a valid entry point' );}
require_once ("DataQuery.php");
XxxInstaller::Install('Xss');


#########################
#
# TODO: Make namespace settings customizable, but note that Xss::ExtensionSetup runs too late for this to work
#
#########################
define ("NS_XSSDATA",1242);
$wgExtraNamespaces[NS_XSSDATA]  = "Data";
$wgExtraNamespaces[NS_XSSDATA+1]= "Data_talk";

#
#  Xss - Xoo Simple Schemas
#
#  Part of Xoo (c) 1997-2008 [[w:en:User:Zocky]], GPL3 applies
#
#########################################################################

#
#  An extension for Mediawiki that allows creation, maintenance and 
#  querying of database tables.
# 
#  USAGE:
#	in the Data Namespace:
/*
	{{#data:table
	| field1 = type | default value
	| field2 = #table |reverse reference name
	}}
*/
#	On other pages:
/*
	{{#data:row|Foo
	| row_name
	| field1 = value
	| field2 = page#row_name
	}}
*/
#	Queries:
/*
	{{#data:select:query expression}}
*/
#
#	TODO: everything
#
#########################################################################


class Xss extends Xxx
{

#######################
##
##  Setup
##
#######################	
	var $mDefaultSettings = array
	(
		"dbsuffix"			=> '_xss', 		# will be added to the wikibase name, if no dbname is provided
		"tableprefix"		=> '',			# prefix for data tables
		"internalprefix"	=> '_xss_',		# prefix for internal tables
		"dbname" 			=> XXX_LATER, 	# defaults to $wgDbName . dbsuffix
		"dbhost" 			=> XXX_LATER, 	# defaults to $wgDbServer
		"dbuser" 			=> XXX_LATER, 	# defaults to $wgDbUser
		"dbpass" 			=> XXX_LATER, 	# defaults to $wgDbPassword
		"nsnumber"			=> 1244, 		# namespace settings
		"nsname"			=> 'Data',
		"nstalk"			=> 'Data_talk'
	);

	function setupExtension()
	{
		global $wgExtraNamespaces;
		global $wgDBname;
		global $wgDBserver;
		global $wgDBuser;
		global $wgDBpassword;

		$this->setDefaultSetting('dbname',$wgDBname . $this->S('dbsuffix'));
		$this->setDefaultSetting('dbhost',$wgDBserver);
		$this->setDefaultSetting('dbuser',$wgDBuser);
		$this->setDefaultSetting('dbpass',$wgDBpassword);

		$dbr=$this->getDbr();
		$pref=$this->S('internalprefix');
		if (!$dbr->tableExists("{$pref}fields"))
		{
			$sql=<<<END
CREATE TABLE `{$pref}fields` (
  `field_table` char(160) NOT NULL,
  `field_name` char(160) NOT NULL,
  `field_type` char(64) NOT NULL,
  `field_default` text,
  `field_reference` char(160) default NULL,
  `field_reverse` char(160) default NULL,
  PRIMARY KEY  (`field_table`,`field_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
END;
			$dbr->query($sql,__METHOD__,__LINE__);
		}
		
		if (!$dbr->tableExists("{$pref}links"))
		{
			$sql=<<<END
CREATE TABLE `{$pref}links` (
  `ln_from` int(11) NOT NULL,
  `ln_title` char(255) NOT NULL,
  PRIMARY KEY  (`ln_from`,`ln_title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
END;
			$dbr->query($sql,__METHOD__,__LINE__);
		}
		if (!$dbr->tableExists("{$pref}multilinks"))
		{
			$sql=<<<END
CREATE TABLE `{$pref}multilinks` (
  `ml_from` int(11) NOT NULL,
  `ml_table` char(255) NOT NULL,
  PRIMARY KEY  (`ml_from`,`ml_table`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
END;
			$dbr->query($sql,__METHOD__,__LINE__);
		}

# 		TODO: Doesn't work here, so invent a way to make them customizable without breaking too much stuff
#		define ("NS_XSSDATA",$this->S('nsnumber'));
#		$wgExtraNamespaces[NS_XSSDATA]  = $this->S['nsname'];
#		$wgExtraNamespaces[NS_XSSDATA+1]= $this->S['nstalk'];
	}
	
#######################
##
##  Member variables
##
#######################	

	var $mOutputTableDef	= null;		# a tableDef
	var $mOutputRows		= array();	# an array of arrays of actual rows in data tables to save - $this->mOutputRows[tableName][rowNumber][fieldName]=value
	var $mOutputRowNames	= array();	# an array of arrays of flags for used row names - $this->mOutputRowNames[rowName]=true;
	var $mSavedPages		= array();	# for debugging;
	var $mShouldSave		= false;	# for debugging;

	function resetState()
	{
		$this->mOutputRows=array();
		$this->mOutputTableDef=null;
		$this->mShouldSave=false;
#		$this->mSavedPages[$pageId] = false;
	}
###################################################
##
##  Main function
##
###################################################
	
	
 	function fl_data(&$parser, &$frame, &$a)
	{
		static $rowCounter=0;
		$args=new xxxArgs($frame, $a);
		$cmd = $args->command;
		switch ($cmd)
		{

		case 'help':
		return "<tt>
;Table definition
* #data:table|field=''type''|[''default'']|field=#''table''|...		
;DataInsertion
*Page based data
** #data:set|''table name''|[''row name'']|field=''value''|field=''value''...|
** #data:row|''table name''|[''row name'']|field=''value''|field=''value''...|
* Other data
** #data:insert|''table name''|[''row name'']|field=''value''|field=''value''...|[#succes=|#failure=...]
** #data:replace|''table name''|[''row name'']|field=''value''|field=''value''...|[#succes=|#failure=...]
** #data:change|''table name''|[''row name'']|field=''value''|field=''value''...|[#succes=|#failure=...]
* Data manipulation queries
** #data:update|''table name''.[''reference field''].''field name''|...|[#where=|#from=|#orderby=|#groupby=...]|[#succes=|#failure=...]
** #data:delete|''table name''.[''reference field''].''field name''|...|[#where=|#from=|#orderby=|#groupby=...]|[#succes=|#failure=...]
* Data selection queries
** #data:select|''table name''.[''reference field''].''field name''|...|[#where=|#from=|#orderby=|#groupby=...]|
** #data:selectrow|''table name''.[''reference field''].''field name''|...|[#where=|#from=|#orderby=|#groupby=...]|
** #data:selectfield|''table name''.[''reference field''].''field name''|...|[#where=|#from=|#orderby=|#groupby=...]|
** #data:grid|''table name''.[''reference field''].''field name''|...|[#where=|#from=|#orderby=|#groupby=...]|
** #data:query|''table name''.[''reference field''].''field name''|...|[#where=|#from=|#orderby=|#groupby=...]|
** #data:sql|''table name''.[''reference field''].''field name''|...|[#where=|#from=|#orderby=|#groupby=...]|
</tt>";

	
###################################
##
##  {#data:table
##	| field =  type 
##	| field =  type | default value
##	| field = #table name | name of the reverse reference
##	| ...
##	}}
##
###################################

		case 'table':
			$parser->disableCache();
			$pageTitle=$parser->getTitle();
			$pageNamespace=$pageTitle->getNamespace();
			
			if ($pageNamespace!=NS_XSSDATA) 
			    return  $this->formatError("Table definitions only allowed in ".$wgExtraNamespaces[NS_XSSDATA]." namespace ($pageNamespace)");
			
			#only one table per page allowed
			if ($this->outputTableDefExists(&$parser)) return $this->formatError("Only one data def per page allowed");
		
			# construct a table def from arguments
			
			$tableName     	= 	$pageTitle->getText(); if(!$this->normalizeName($tableName)) return $this->formatError("Bad table name $tableName");
			$fieldRows		=	array();	# this is where we put data we gathered
			$fieldNames		=	array();	# check for duplicate names	
			$fieldTypes		=	array();	# for showing in the data grid
			$defaultValues	=	array();	# for showing in the data grid


			# loop through arguments, all the continue statements below go here
			for ($i = 1; $i<=$args->count; $i++)
			{

				if ($args->isNamed($i))
				{
    				# named argument, either a field or a special argument			
					$argName  = $args->getName($i);
					$argValue = $args->trimExpandValue($i);

                    # it's a special argument, i.e. its name starts with a #
					if ($this->removePrefix($argName,'#')) 
					{
						switch ($argName)
						{
							#none for now
							default: 
							$errorMessage .= $this->formatError("Unrecognized argument $key");
						}
						continue;
					}
					
					# if we're still here, it's a field definition

					#get the field's name
					$fieldName = $argName;
					if (!$this->normalizeName($fieldName)) 	
					{
					    # it can't be normalized
						$errorMessage .= $this->formatError("Bad field name $argName");
						continue;
					}
					
					if (isset($fieldNames[$fieldName]))
					{ 	
					    # already exists
						$errorMessage .= $this->formatError("Duplicate field name $key");
						continue;
					}
					
					# it's a new field, so register it and set up defaults
					
					$fieldNames[$fieldName]		= true;
					$fieldType					= null;
					$fieldDefault   			= null;
					$fieldReference 			= null;
					$fieldReverse   			= null;
					$defaultValues[$fieldName]	= null;
                    
                    # get the value, i.e. the part after =
					$argValue = $args->trimExpandValue($i);

                    # does it start with a # 
					if (!$this->removePrefix($argValue,'#')) 
					{
					    # the value doesn't start with a #, so it's the name of the type for this field
						
						if (!$this->normalizeName($argValue)) 
						{
    						# the type can't be properlly normalized
						    $errorMessage.=$this->formatError("Bad type name $fieldName = $argValue");
						    continue;
						}
                        
                        #lowercase the typename, and reject if the type is unknown
						$fieldType = strtolower($argValue);			
						$fieldTypes[$fieldName]	= $fieldType;
						if (!Xtt::getDbFieldType($fieldType)) 
						{
						    $errorMessage .= $this->formatError("Unknown field type $fieldName = $fieldType");
						    continue;
						}
						
						# if the next argument is nameless, then it's the default value
						# if it needs to contain "=", start it with a = i.e. |field=type|=default
						if ($args->exists($i+1) and ($args->isNumbered($i+1) or $args->getKey($i+1)==''))
						{
							# the next argument is default valrow_nameue
							$fieldDefault = $args->cropExpandValue($i+1);
							if (!Xtt::castValue($fieldType,$fieldDefault))
							{
							    $errorMessage .= $this->formatError("Invalid default value $fieldName = $fieldType | $fieldDefault");
							    continue;
							}
							$defaultValues[$fieldName]	= $fieldDefault;
							$i++;
						}
					}
					else 
					{
						# the field is a reference to another table
						# is it a multi-multi reference?						
						if ($this->removePrefix($argValue,'#')) 
						{
							$fieldType='multi';						
						}
						else
						{
							$fieldType='reference';												
						}
						# normalize or die!
						if (!$this->normalizeName($argValue)) 
						{
							$errorMessage .= ("Bad referenced table name $argValue");
	                        continue;
	                    }
	                    
						$fieldTypes[$fieldName]	= $fieldType;
						$fieldReference = $argValue;
				
						# check the next field
						if ($args->exists($i+1) and $args->isNumbered($i+1))
						{
							# the next field is the name of the reverse reference							
							$fieldReverse = $args->trimExpand($i+1);
							if (!$this->normalizeName($fieldReverse))
							{
								$errorMessage .= $this->formatError("Bad reverse reference name $fieldReverse");
								continue;
							}
							$i++;
						}
					}
					$fieldRows[]=$this->makeFieldDef($tableName, $fieldName, $fieldType, $fieldDefault, $fieldReference, $fieldReverse);
				} 
				# done field definition					
				else 
				{
					# it's an unexpected nameless argument				
					$errorMessage .= $this->formatError ("Unrecognized argument ".$args->trimExpand($i));
				}
			} 

			$tableDef=$this->makeTableDef($tableName, $fieldRows);
			$this->addOutputTableDef(&$parser, &$pageTitle, $tableDef);

			# done looping through arguments, display stuff
			$returnText='<h2>Table definition <small><a href="'. $pageTitle->escapeFullUrl('command=browse') .'">(browse)</a></small></h2>';
			# display table def
			$tableHead = $this->formatHeaderRow('field name','field type','default value','reference table','reverse reference name');
			$tableBody='';
			foreach ($tableDef['fieldsByNumber'] as $fieldRow)
			{
				$cellRow = array_slice($fieldRow,1);
				if ($cellRow['field_reference']) 
				{
					$refTitle=Title::newFromText($cellRow['field_reference'],NS_XSSDATA);
					$cellRow['field_reference']='<a href="'. $refTitle->escapeFullUrl() .'">'. $refTitle->getText() .'</a>';
				}
				$tableBody.=$this->formatCellRow($cellRow);
#				$tableBody.=$this->formatCellRow(array_keys($cellRow));
			}
			$returnText.=$this->formatTable($tableHead, $tableBody);

			# if the table exists, show its data with the definition parsed from text
			# this should allow friendly previews of edits to table definitions
		

			if ($_GET['command']=='browse') $returnText='';
			
			if ($_GET['action'] != 'submit' && $_GET['command']!='browse') return array( $returnText, 'isHTML'=>true);
			
			$dbr =&$this->getDbr();


			if ($dbr->tableExists($this->getDataTableName($tableName)))
			{
				global $wgRequest;
				$parser->disableCache();
				$returnText.='<h2> Browse';
				$returnText.=' <small><a href="' . $pageTitle->escapeFullUrl() . '">(table definition)</a></small>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<small>';
				$offset = $wgRequest->getInt('data_offset',0);
				$limit = $wgRequest->getInt('data_limit',20);
				if ($limit>2000) $limit=2000;
				if ($offset>0)				
				{
					$returnText.='<a href="'. $pageTitle->escapeFullUrl("command=browse&sort={$_GET['sort']}&direction={$_GET['direction']}&data_offset=".($offset-$limit>0?$offset-$limit:0)."&data_limit=$limit").'" onclick="if(window.loadContent) { loadContent(this) ; return false }">◀</a> ';
				}
				$returnText.='<a href="'. $pageTitle->escapeFullUrl("command=browse&sort={$_GET['sort']}&direction={$_GET['direction']}&data_offset=".($offset+$limit)."&data_limit=$limit").'" onclick="if(window.loadContent) { loadContent(this) ; return false }">▶</a>';
				$returnText.='</small></h2>'; 
				
				$DIR = $_GET['direction'] == 'desc' ? 'DESC' : 'ASC'; 
				$ORDERBY = $_GET['sort'] ? "ORDER BY" . $this->escapeName($_GET['sort']) . " $DIR" : '';
				$res= $dbr->query('SELECT * FROM ' . $this->escapeDataTableName($tableName) ." $ORDERBY LIMIT $limit OFFSET $offset");
				
				$headerRow=array();
				
				# find missing fields
				$headerRow=array_keys($fieldNames);
				array_unshift($headerRow,'_row_ref');
				array_unshift($headerRow,'_page');
				
				$missingFields=array();
				foreach ($headerRow as $k=>$fName)
				{
					$urlAsc = $parser->mTitle->escapeFullUrl("command=browse&sort=$fName&direction=asc");
					$urlDesc = $parser->mTitle->escapeFullUrl("command=browse&sort=$fName&direction=desc");
					$headerRow[$k]="<a href=\"$urlDesc\" onclick=\"if(window.loadContent) { loadContent(this) ; return false }\">▼</a>&nbsp;$fName&nbsp;<a href=\"$urlAsc\" onclick=\"if(window.loadContent) { loadContent(this) ; return false }\">▲</a><a name=\"column_$fName\"/>";
				}
				for ($i=5;$i<$dbr->numFields($res);$i++)
				{
					$fName=$dbr->fieldName($res,$i);
					
					if (!$fieldNames[$fName]) 
					{
						$missingFields[]=$fName;
						$headerRow[]="<s>$fName</s>";
					}
				}
				$tableHead = $this->formatHeaderRow($headerRow);
				$tableBody='';
				while ($row=$dbr->fetchRow($res))
				{	
					$rowTitle=Title::makeTitle($row['_page_ns'],$row['_page_title']);
					$rowName=preg_replace('/_/',' ',$row['_row_name']);
					$rowRef=$row['_row_ref'];
					$cellRow=array
					(
						'<a href="' . $rowTitle->escapeFullUrl() .'">' . $rowTitle->getFullText() . '</a>',
						'<a href="' . $rowTitle->escapeFullUrl() . '#' . $rowName . '">' . $rowRef . '</a>'
					);
					foreach ($defaultValues as $fName=>$fValue)
					{
						$value=$row[$fName];
						$fixValue=$value;
						$fType=$fieldTypes[$fName];
						$cast = Xtt::castValue($fType,$fixValue);
						$displayValue=substr(htmlspecialchars($value),0,255);
#						$displayValue=$fixValue;
						if (!$cast)
						{
							$cellRow[]='<div class="xss-cell" style="position:relative;color:red">'.$displayValue.'</div>';
						}
						elseif ($value!=$row[$fName])
						{
							$cellRow[]='<div class="xss-cell" style="position:relative;color:navy">'.$displayValue.'</div>';
						}
						elseif ($value!==$fValue)
						{
							$cellRow[]='<div class="xss-cell" style="position:relative">'.$displayValue.'</div>';
						}
						else
						{
							$cellRow[]='<div class="xss-cell" style="position:relative;">'.$displayValue.'</div>';
						}
					}
					foreach ($missingFields as $fName)
					{
						$cellRow[]="<s>{$row[$fName]}</s>";
					}
					$tableBody.=$this->formatCellRow($cellRow);
				};
				$returnText.=$this->formatTable($tableHead, $tableBody);
			}
			return array(0=>$returnText.$errorMessage,'isHTML'=>true,'noParse'=>true);
		
		case 'row':
		case 'set':
		case 'guess':
			$pageTitle=$parser->getTitle();
			$pageNamespace=$pageTitle->getNamespace();
		    if ($pageNamespace==NS_XSSDATA) return  "<b>Data definitions not allowed in ".$wgExtraNamespaces[NS_XSSDATA]." namespace ($pageNamespace)</b>";
			if (!$args->exists(1)) return $this->notFound();

			$rowCounter++;
			extract($this->getFieldValues($parser,$frame,$args,$rowCounter,true,$cmd=='guess'));
			if ($fatal) return $error;

			# add a transclusion link, so data gets updated in queue jobs			
			
			$tableTitle=Title::makeTitle(NS_XSSDATA,$tableName);
			$parser->fetchTemplate($tableTitle);

			$pageName = $parser->mTitle->getFullText();
			#if ($this->outputRowExists($parser,$rowName)) return $this->formatError ("Duplicate row name $rowName"); 
			# TODO: handle this more wiki way, probably display anyway, just not save

			$this->addOutputRow (&$parser, &$pageTitle, $tableName, $rowName, $fieldValues);
			if ($args->command=='set') return $errorMessage;
			
			$returnText=$error;

			$tableHead=$this->formatHeaderRow
			(
				'[['.$this->S('nsname').":$tableName|$tableName]]",
				"<span class=\"xss-row-name\" id=\"$rowName\">" . (is_numeric($rowName) ? '' : $rowName ) . '</span>'
			);
			$tableBody='';
			if (is_array($fieldDisplay)) foreach ($fieldDisplay as $fName=>$fValue)
			{
				$tableBody.=$this->formatCellRow($fName, $fValue);
			}
			$returnText .= $this->formatTable($tableHead, $tableBody);
			
			return "<h2>$rowName</h2>$returnText $errorMessage";

			
#############################################################################
#############################################################################
###
###  {{#data:getrow|table|rowid}} returns record array
###  {{#data:get|table|rowid|fieldname}} returns field value
###  {{#data:wraprow|table|rowid|template|arg=val|...}} maps the record array to the tempate
###  {{#data:evalrow|table|rowid|arg=val|...|code}} evals the code with field values as params
##############################################################################
##############################################################################
		case 'get':
			# we need at least 3 arguments for get
			if ($args->count>3) $this->notFound();			
			$fieldName = $args->trimExpand(3,''); 
			if (!$this->normalizeName($fieldName)) return $this->notFound();
		case 'getrow':
			# we need exactly two arguments for getrow
#			if ($args->count<2) return $this->notFound();
		case 'wraprow':
		case 'evalrow':
			#we need at least two arguments for anything
			if ($args->count<2) return $this->notFound();
			#get table name, check that it exists, escape it
			$tableName=$args->trimExpand(1);
			if (!$this->normalizeName($tableName)) return $this->notFound();
			if (!$this->tableExists($tableName)) return $this->notFound();
			
			#get row ref
			$rowRef=$this->escapeValue($args->trimExpand(2));
			if (!$this->normalizeName($rowRef)) return $this->notFound();
		
			#check if the row is already in the cache
			if(isset($this->dataRowCache[$tableName][$rowName]))
			{
				$row = $this->dataRowCache[$tableName][$rowName];
			}			
			#else, get it from DB
			{
				#make SQL, get the row
				$xssQuery = XssQuery::Make($this,array(""=>"$tableName.*"),array('where'=>"$tableName.#=$rowRef"));
				$sql=$xssQuery->getSql();
				$dbr=&$this->getDbr();
				if (!$dbr) return $this->notFound();	
				$res=$dbr->query($sql,__METHOD__,true);
				if(!$res) return $this->notFound();
				$row=$dbr->fetchRow($res);
				
				#put into cache
				$this->dataRowCache[$tableName][$rowName]=$row;
			}
						
			if ($args->command=='get')
			{
				if(!$row) return "";
				return $row[$fieldName];
			}
			elseif ($args->command=='getrow')
			{
				if (!$row) return $this->makeResultRow(array());
				return $this->makeResultRow($row);
			}
			elseif ($args->command=='wraprow')
			{
				if (!$row) return "";
				
				#$fieldNum=count($row)/2; for($i=0;$i<$fieldNum;$i++) unset($row[$i]);
				
				for ($i=4;$i<=$args->count;$i++)
				{
					$row[$args->getKey($i)]=$args->getValue($i);
				}				
				
				$template=$args->trimExpand(3);
				$ns=NS_TEMPLATE;
				$title = Title::newFromText( $template, $ns);
				if (!$title) return $this->notFound();
			    list( $dom, $title ) = $parser->getTemplateDom( $title );
				if (!$dom) return "[[".$title->getPrefixedText()."]]";

#				print_r($row);
				$customFrame = $this->newChildFrame($f,$row,$title);
			    return $customFrame->expand($dom);			
			}
			elseif ($args->command=='evalrow')
			{
				if (!$row) return "";
				
				#$fieldNum=count($row)/2; for($i=0;$i<$fieldNum;$i++) unset($row[$i]);
				
				for ($i=3;$i<$args->count-1;$i++)
				{
					$row[$args->getKey($i)]=$args->getValue($i);
				}				
				
				$code=$args->get($args->count);

				$customFrame = $this->newExtendedFrame($f,$row);
			    return $customFrame->expand($code);			
			}
			else return $this->notFound();
			break;
#############################################################################
#############################################################################
###
###  {#data:select|query }}
###
##############################################################################
##############################################################################
		case 'schema':
			if ($args->count>1) return $this->notFound();
			$format=$args->trimExpand(1,'ajax');
			switch($format)
			{		
			case 'ajax':
			default:
				$dbr = $this->getDbr();
				$fieldTable = $this->escapeInternalTableName('fields');
				$ret="{\n";
				$sql = "
SELECT * FROM (
	(
		SELECT 	field_table AS ta,
			field_name AS fi, 
			field_type AS ty, 
			field_reference AS re 
		FROM xss_fields 
	) UNION ( 
		SELECT 	field_reference AS ta, 
			field_reverse AS fi, 
			CONCAT('rev_',field_type) AS ty, 
			field_table AS re
		FROM xss_fields 
                WHERE (field_type = 'multi' OR field_type='reference') AND field_reverse <>''
	) 
) AS sch
ORDER BY ta, fi;
";
				$res = $dbr->query($sql);
				$currentTable='';
				while($row=$dbr->fetchRow($res))
				{
					if($row['ta']!=$currentTable) {
						if($currentTable!='')
						{
							$ret.="\t},\n";	
						}
						$currentTable=addslashes($row['ta']);
						$ret.="\t'{$currentTable}' : {\n";
					}
					$ret.="\t\t'".addSlashes($row['fi'])."':";
					if($row['re']!=null) {
						$ret.="'" . addslashes($row['re'])."'";
					} else {
						$ret.="0";
					} 
					$ret.=",\n";
				}
				if($currentTable!='') $ret.="\t}\n";
				$ret.="};";
				return array(0=>$ret,'isHTML'=>true);
			}
		case 'query':			
		case 'select':
		case 'grid':
		case 'sql':
		case 'explain':
		case 'selectrow':
		case 'selectfield':
		case 'selectcolumn':
			if ($args->count<1) return $this->notFound();		
			$dbr = $this->getDbr();
#			print_r($options); die;

			extract ($this->getQueryArgs('SELECT',$args));
			#we get $fields and $options
			$xssQuery = XssQuery::Make($this,$fields,$options);

			if ($errorMessage=$xssQuery->getError()) $returnText =  $this->formatError($errorMessage);
			
			$sql=$xssQuery->getSql();
			switch($args->command)
			{
			case 'sql':
				return  $sql;
			case 'explain': 
				$sql="EXPLAIN $sql";
			case 'query':
				$returnText = "<pre>".$sql."</pre>";
			case 'grid':
		#		return $sql;
			
				try
				{
					$now = time() + microtime();
					$res=$dbr->query($sql,__METHOD__,true);
					/*if ($args->command != 'grid')*/ $returnText.=sprintf("%0.5fs<br>",time()+microtime()-$now);
					if(!$res) {$returnText.="<div style=\"font-face:monospace\"> Error:".$dbr->lastError()."</div>"; break;}
				}
				catch(DBError $e){ return "<pre>sql</pre>".$e->error."<br>";}

				try
				{
					$tableHead="<tr>";
					$numFields=$dbr->numFields($res);
					
					for ($i=0;$i<$numFields;$i++)
					{
						$tableHead.="<th>".$dbr->fieldName($res,$i)."</th>";
					}
					$tableHead.="</tr>";
					$tableBody='';
					while ($row=$dbr->fetchRow($res))
					{
						$tableBody.="<tr>";
						for ($i=0;$i<$numFields;$i++)
						{
							$last = preg_replace('/^.*\./','',$dbr->fieldName($res,$i));
							if ($last=="#page")
								$tableBody.="<td>[[".$row[$i]."]]</td>";
							else					
								$tableBody.="<td>".$row[$i]."</td>";
						}
						$tableBody.="</tr>";
			
					}
					$returnText .= $this->formatTable($tableHead, $tableBody);

				}
				catch(DBError $e){ return "<pre>sql</pre>".$e->error."<br>";}
				return $returnText;

			$resultColumn=array();
			case 'select':
			case 'selectrow':
			case 'selectfield':
			case 'selectcolumn':
			   global $wgXooExtensions;
				try
				{	
					$res=$dbr->query($sql,__METHOD__,true);
					if(!$res) {$returnText.="<pre> Error:".$dbr->lastError()."</pre>"; break;}
					
					$rowCounter=1;
					$resultArray=array();
					$resultColumn=array();
					
					while ($row=$dbr->fetchRow($res))
					{
						if ($args->command == 'selectfield') return $row[0]; 
						if ($args->command == 'selectrow') return  $this->makeResultRow($row);
						else $resultArray[$rowCounter]=$this->makeResultRow($row);
						$resultColumn[$rowCounter]=$row[0];
						$rowCounter++;
					}
					if ($args->command == 'selectfield') return '';
					elseif ($args->command == 'selectrow') return  $this->makeResultRow(array());
					if ($args->command=='selectcolumn')
					{
				       $returnText = $wgXooExtensions['Xvv']->arrMake($resultColumn);					
					}
					else
					{
				       $returnText = $this->makeResultRow($resultArray);
				    }
				}
				catch(DBError $e){ $returnText.="<pre>".$e->error."</pre>";}
				
				return $returnText;
			}
			return $returnText;
		
		default:
			return $this->notFound();
		} # end {{#data: switch 

	} # 

############################################################################################
############################################################################################	
############################################################################################
####
####
####     END {{#data:}} LOOP
####
####
############################################################################################
############################################################################################
############################################################################################	
	
	function makeResultRow($row)
	{
		$numFields=count($row)/2;
		for ($i=0;$i<$numFields;$i++) unset ($row[$i]);
		global $wgXooExtensions;
		return $wgXooExtensions['Xvv']->arrMake($row);
	}					
	
	
#######################
##
##  DB connection
##
#######################	

	var $mDbr=null;
	function getDbr()
	{
		global $wgDBserver;
		global $wgDname;
		global $wgDBuser;
		
		$S=$this->mSettings;
		if (!$this->mDbr) 
		{
			if ($S['dbhost']!=$wgDBserver or $S['dbname']!=$wgDBname or $S['dbuser']!=$wgDBuser)
			{
				$this->mDbr = Database::newFromParams( $this->S('dbhost'), $this->S('dbuser'), $this->S('dbpass'), $this->S('dbname'));
			}
			else
			{	
				$this->mDbr=& wfGetDB( DB_MASTER );
			}
		}
		return $this->mDbr; 
	}

#######################
##
##  DB helpers - TODO: should use mw functions
##
#######################	

	static function normalizeName(&$s)
	{
		if ($s==='' or preg_match('/[#@]/',$s)) return false;
		$s=trim(preg_replace('/[_\s]+/',' ',$s));
		if (is_numeric($s)) return false;
		$t=Title::newFromText($s);
		if (!$t) return false;
		$s=$t->getDBkey();
		return $s;
	}
	
	function getDataTableName($n)
	{
		$this->normalizeName($n);
		return $this->S('tableprefix').$n;
	}
	
	function getInternalTableName($n)
	{
		return $this->S('internalprefix').$n;
	}
	
	function getMultiTableName($n,$f)
	{
		$this->normalizeName($n);
		$this->normalizeName($f);
		return "_{$n}__x__{$f}";
	}
	
	function escapeMultiTableName($n,$f)
	{
		$this->normalizeName($n);
		$this->normalizeName($f);
		return $this->escapeName($this->S('tableprefix')."_{$n}__x__{$f}");
	}


	function escapeName($s)
	{
		return '`'.trim(preg_replace('/`/','\`',$s)).'`';
	}
	function escapeDataTableName($s)
	{
		return '`'.$this->S('tableprefix').trim(preg_replace('/`/','\`',$s)).'`';
	}
	function escapeInternalTableName($s)
	{
		return '`'.$this->S('internalprefix').trim(preg_replace('/`/','\`',$s)).'`';
	}

	function escapeValue($s)
	{
		if (is_null($s)) return 'NULL';
		if (is_numeric($s)) return $s;
		return "'".trim(preg_replace('/\'/','\\\'',$s))."'";
	}

	function fetchAssoc( $dbr, $res ) {
		if ( $res instanceof ResultWrapper ) {
			$res = $res->result;
		}
		@/**/$row = mysql_fetch_assoc( $res );
		if ( $dbr->lastErrno() ) {
			throw new DBUnexpectedError( $this, 'Error in fetchAssoc(): ' . htmlspecialchars( $this->lastError() ) );
		}
		return $row;
	}

#######################################################
##
##  Table definition construction, getters and cache
##
#######################################################

	var $mTableDefs=array();
	
	function makeFieldDef($table, $field, $type, $default = null, $reference = null, $reverse=null)
	{
		if (!$this->normalizeName($table) or !$this->normalizeName($field)) return false;
		
		$fieldDef=array
		(
			'field_table'		=> $table,
			'field_name' 		=> $field,
			'field_type' 		=> $type,
			'field_default'		=> $default,
			'field_reference' 	=> $reference,
			'field_reverse'		=> $reverse
		);
		return $fieldDef;
	}

	function makeTableDef($tableName,$fieldDefs) # TODO: Probably should be its own class
	{
		$tableDef = array
		(
			'rows' 				=> array(),
			'fieldsByNumber'	=> array(),
			'fieldsByName'		=> array(),
			'fieldNames'		=> array(),
			'reverseByNumber'	=> array(),
			'reverseByName'		=> array(),
			'reverseNames'		=> array(),
		);

		
		$fieldCounter=0;
		$reverseCounter=0;
		foreach ($fieldDefs as $key=>$fieldDef)
		{
			unset ($myField);
			$myField = $fieldDef;

			$tableDef['rows'][$fieldCounter]=$myField;
			
			$tableNameFromDb=$fieldDef['field_table'];
			$fieldName=$fieldDef['field_name'];
			$referenceName=$fieldDef['field_reference'];
			
			
			if ($tableNameFromDb == $tableName)
			{
				$tableDef['fieldsByNumber'][$fieldCounter]=$myField;
				$tableDef['fieldsByName'][$fieldName]=$myField;
				$tableDef['fieldNames'][$fieldCounter]=$fieldName;
				$tableDef['fieldDefaults'][$fieldName]=$fieldDef['field_default'];
				$fieldCounter++;
			}
			elseif ($referenceName == $tableName)
			{
				$reverseName=$fieldDef['field_reverse'];
				$tableDef['reverseByNumber'][$reverseCounter]=$myField;
				$tableDef['reverseByName'][$reverseName]=$myField;
				$tableDef['reverseNames'][$reverseCounter]=$reverseName;
				$reverseCounter++;
			}
			else #Error?
			{
				$this->reportError("Field {$fieldName} is not from table {$tableName}.", __METHOD__);
			}
		}
		return $tableDef;
	}

	function tableExists($tableName)
	{
	    $dbr =& $this->getDbr();
    	if (!$dbr) return false; 
		$tableName = $this->getDataTableName($tableName);
#		print "<br>$tableName:" . ( $dbr->tableExists($tableName) ? 'yes' : 'no');
		return $dbr->tableExists($tableName);
	}
	

	function getTableDef($tableName)
	{
		if (isset($this->mTableDefs[$tableName]))
		{
			return $this->mTableDefs[$tableName];
		}
		else
		{
			return $this->getTableDefFromDB($tableName);	
		}
	}
	
	function getTableDefFromDB($tableName)
	{
		$dbr = $this->getDbr();
		
		$res = $dbr->select
		(
			$this->escapeInternalTableName('fields'),
			'*',
			'field_table=' . $this->escapeValue($tableName) . ' or field_reference=' .$this->escapeValue($tableName)
		);
		
		$fieldDefs=array();
		while ($row=$this->fetchAssoc($dbr,$res))
		{
			$fieldDefs[]=$row;
		}
		if (count($fieldDefs)>0)
		{
			$tableDef = $this->makeTableDef($tableName,$fieldDefs);
			$this->mTableDefs[$tableName] = $tableDef;
			return $tableDef;
		}
		else
		{
			unset ($this->mTableDefs[$tableName]);
		}
	}
	
	function getFieldDefaults($tableName)
	{
		$tableDef=$this->getTableDef($tableName);
		$def=array();
		foreach( $tableDef['fieldDefaults'] as $k=>$v) {
			if($v) {
				$def [$k]=$v;
			}
		}
		return $def;
	}

	function getFieldNames($tableName)
	{
		$tableDef=$this->getTableDef($tableName);
		return $tableDef['fieldNames'];
	}

	
	function fieldExists($tableName,$fieldName)
	{
		$tableDef=$this->getTableDef($tableName);
		return isset($tableDef['fieldsByName'][$fieldName]);
	}
	
	function getFieldDef($tableName,$fieldName)
	{
		if (!$tableDef =&$this->getTableDef($tableName)) return false;
		if (!isset($tableDef['fieldsByName'][$fieldName])) return false;
		return $tableDef['fieldsByName'][$fieldName];
	}
	
	function getFieldProperty($tableName,$fieldName,$property)
	{
		if (!$fieldDef =&$this->getFieldDef($tableName,$fieldName)) return false;
		return $fieldDef['field_'.$property];
	}

	function getReverseDef($tableName,$fieldName)
	{
		if (!$tableDef=&$this->getTableDef($tableName)) return false;
		if (!isset($tableDef['reverseByName'][$fieldName])) return false;
		return $tableDef['reverseByName'][$fieldName];
	}
	
	function getReverseProperty($tableName,$fieldName,$property)
	{
		if (! $fieldDef=&$this->getReverseDef($tableName,$fieldName)) return false;
		return $fieldDef['field_'.$property];
	}
	
	
	function getFieldValues(&$parser, &$frame, &$args, $onMissingId = null, $useDefaults=true, $guess = false)
	{			
		$tableName   = 	$args->trimExpand(1); 
		if ( !$this->normalizeName($tableName))
		{ 
			return array('fatal'=>true,'error'=> $this->formatError ("Bad table name $tableName"));
		}
		elseif ( !$tableDef=$this->getTableDef($tableName))
		{ 
			return array('fatal'=>true,'error'=> $this->formatError ("Table not found $tableName"));
		}			
		else
		{
			if ($args->isNumbered(2)) # row name supplied
			{
				$rowName = $args->trimExpand(2);
				
				$pref = ($this->removePrefix($rowName,'#'));
				if ($rowName=='' && $pref){
					$rowName = $parser->mTitle->getDbKey().'#'. ($onMissingId ? $onMissingId : $parser->getRandomString());
				} elseif ($rowName=='' && !$pref){
					$rowName = 'guid_'.$parser->getRandomString();
				} elseif (!$this->normalizeName ($rowName)) {
					return array('fatal'=>true,'error'=> $this->formatError ("Bad row name $rowName (table $tableName)")); 
				}
				$argOffset=3;
			}
			else # row name not supplied, so we use the parameter, or a random string
			{
#				$rowName="test";
				$rowName = $parser->mTitle->getDbKey();
				$argOffset=2;
			}
			
			#start with field defaults, if needed
			
			if ($useDefaults) $fieldValues = $this->getFieldDefaults($tableName);
			$fieldDisplay = $fieldValues;
			$options=array();
			# process arguments	
		
			for ( $i=$argOffset; $i <= $args->count ; $i++ )
			{
				if (true or $args->isNamed($i)) 
				{
					$name = $args->getName($i);
					if ($this->removePrefix($name,'#'))
					{
						switch ($name)
						{
						case 'success':
						case 'failure':
							$options[$name]=$i;
							break;
						case 'debug':
						case 'ignore':
						case 'priority':
							$val = $args->trimExpandValue($i);
							$options[$name]=$val; break;
						}
					}
					else #it's a regular field
					{
						if ($args->isNamed($i)) {
							$fieldName = $args->getName($i);
						} else {
							$fieldName = $args->trimExpand($i);
						}
						if (!$this->normalizeName ($fieldName)) 
						{
							$error .= $this->formatError("Bad field name $fieldName");
							continue;
						}
						if ($this->fieldExists($tableName,$fieldName)) #we're only interested if it's a valid field
						{
							$fieldType=$this->getFieldProperty($tableName,$fieldName,'type');
							$fieldTypes[$fieldName]=$fieldType;
							if ($args->isNamed($i)) {
								$argValue = $args->cropExpandValue($i);
							} else {
								$argName = $args->trimExpand($i);
								if ($frame->parent && isset($frame->namedArgs[$argName]) ) {
									$argValue = $frame->parent->expand($frame->namedArgs[$argName]);
								} else {
									$argValue = '';
								}
							}
							$argValue = $parser->mStripState->unstripBoth($argValue);

							if($fieldType=='reference')		#TODO fix this ugly hack
							{
								$argValue=trim($argValue);
								if ($argValue{0}=="#")
								{
									#$argValue=$pageTitle->getFullText().$argValue;
								}
							}
							global $wgXooExtensions;
							$xvv =& $wgXooExtensions['Xvv'];
							if ($fieldType=='multi') {
								if ($xvv->arrExists($argValue)) {
									$arr = $xvv->arrGet($argValue);
								} elseif ($guess && Xtt::guessValue('multi',$argValue)) {
									$arr=$argValue;
								} elseif (Xtt::castValue('reference',$argValue)) {
									$arr=array($argValue);
								} else {
									$arr = array();
								}
								foreach ($arr as $v)
								{
									if (trim($v)!=='') {
										$this->addMultiRow($parser, $tableName,$fieldName,$rowName,$v);
										$fieldDisplay[$fieldName] .=  $fieldDisplay[$fieldName] ?  ", [[$v]]" : "[[$v]]";
									}					
								}
							} elseif ($guess && Xtt::guessValue($fieldType,$argValue)) {
								if ($argValue!=='')	$fieldValues[$fieldName]=$argValue;
								$fieldDisplay[$fieldName] = $argValue;
							} elseif (Xtt::castValue($fieldType,$argValue)) {
								if ($argValue!=='')	$fieldValues[$fieldName]=$argValue;
								$fieldDisplay[$fieldName] = $argValue;
							} else {
								$error .=  $this->formatError("Bad value '$argValue' for field  $fieldName ($fieldType)");
								continue;
							}
							if($fieldType=='reference') $fieldDisplay[$fieldName]='[['.$argValue.']]';
						}
						else 
						{
							$fieldSuggestion=$this->suggestField($tableName,$fieldName);
							$error .= $this->formatError("Unknown field name $fieldName. Did you mean $fieldSuggestion?");
							continue;
						}
					}
				}
				elseif ($guess) #we're guessing so pass on the arguments
				{
					$error .= $this->formatError("Unrecognized argument " . $args->trimExpand($i));
					continue;
				}
			}
		}
		return array(
			'tableName'=>$tableName,
			'rowName'=>$rowName,
			'fieldDisplay'=>$fieldDisplay,
			'fieldValues'=>$fieldValues,
			'options' => $options, 
			'error'=>$error,
			'fatal'=>$false
		);
	}
	
	function suggestField($tableName,$fieldName)
	{
		$fieldSuggestion='';
		$fieldNames = $this->getFieldNames($tableName);		
		
		if (!$fieldNames)
		{
			$dbr =& $this->getDbr();
			if (!$dbr) return true; 
			$wikiDbr =& wfGetDB( DB_MASTER );
			$res = $dbr->query ('SELECT DISTINCT field_table FROM ' . $this->escapeInternalTableName('fields'));
			$minDistance = 100000000;
			while ($row = $dbr->fetchRow($res))
			{
				$distance=levenshtein($row[0],$tableName);
				if ( $distance < $minDistance )
				{
					$tableSuggestion=$row[0];
					$minDistance = $distance;
				}
			}
			$fieldNames = $this->getFieldNames($tableSuggestion);
		}
		
		$minDistance = 100000000;
		foreach ($fieldNames as $aName)
		{
			$fieldDistance=levenshtein($aName,$fieldName);
			if ($fieldDistance<$minDistance)
			{
				$fieldSuggestion = $aName;
				$minDistance = $fieldDistance;
			}
		}
		$ret = $tableName != $tableSuggestion ? "<u>$tableSuggestion</u>" : $tableSuggestion;
		$ret .= ".";
		$ret .= $fieldName != $fieldSuggestion ? "<u>$fieldSuggestion</u>" : $fieldSuggestion;
		return $ret;
	}
	
	function getQueryArgs($type,$args)
	{
		$fields = $array;;
		$options= array();
		for($i=1;$i<=$args->count;$i++)
		{
			if ($args->isNamed($i))
			{
				$name=$args->getName($i);
				if ($this->removePrefix($name,'#'))
				{
					switch ($name)
					{
					case 'success':
					case 'failure':
						$options[$name]=$i;
						break;
					case 'debug':
					case 'from':
					case 'fromtables':
					case 'where'  : 
					case 'groupby':
					case 'having':
					case 'sort':
					case 'orderby':
					case 'ignore':
					case 'priority':
						$val = $args->trimExpandValue($i);
						$options[$name]=$val; $optionFound=true;	break;
					case 'offset':
						$val = $args->trimExpandValue($i);
						$options[$name]=(int)$val; $optionFound=true; break;
					case 'limit':
						$val = $args->trimExpandValue($i);
						$options[$name]=(int)$val > 200 ? 200 : (int)$val < 1 ? 1 : (int)$val; $optionFound=true; break;
					}
				}
				else	if (!$optionFound)	$fields[$args->getKey($i)]=html_entity_decode($args->trimExpandValue($i));
		
			}
			else if (!$optionFound)	$fields[$args->getKey($i)]=$args->trimExpandValue($i);
		};
		return array('options'=>$options,'fields'=>$fields);
	}

###################################################
##
##  Formatting functions 
##
##	TODO:Make customizable: use site css, or $this->mSettings
##
###################################################

	function formatError($errorText)
	{
		return '<span class="xss-error" style="color:red;font-weight:bold">'.$errorText.'</span>';
	}
	
	function formatTable($head, $content='')
	{
		return '<div class="xss-outer-wrap"><div class="xss-inner-wrap"><table class="xss-table" cellspacing="0" cellpadding="0">'.$head.''.$content."</table></div></div>";
	}


	function formatHeaderRow()
	{
		$args = func_get_args();
		$ret = '<tr>';
		foreach ($args as $arg)
		{
			if (is_array($arg))
			{
				foreach ($arg as $ar)
				{
					$ret.='<th><span>'.($ar?$ar:'&nbsp;')."</span></th>";
				}
			}
			else
			{
				$ret.='<th><span>'.($arg?$arg:'&nbsp;')."</span></th>";
			}
		}
		$ret.="</tr>";
		return $ret;
	}

	function formatCellRow()
	{
		static $flipFlop = false; $flipFlop=!$flipFlop; $class=$flipFlop ? 'xss-row-odd':'xss-row-even';
		$args = func_get_args();
		$ret = "<tr class=\"$class\">";
		foreach ($args as $arg)
		{
			if (is_array($arg))
			{
				foreach ($arg as $ar)
				{
					$ret.="<td>".($ar?$ar:'&nbsp;')."</td>";
				}
			}
			else
			{
				$ret.="<td>".($arg?$arg:'&nbsp;')."</td>";
			}
		}
		$ret.="</tr>";
		return $ret;
	}

############################################
##
##  Registration functions for later saving
##
############################################


	function outputTableDefExists(&$parser)
	{
		return isset($parser->mOutput->mXssOutputTableDef);
	}

	function addOutputTableDef(&$parser, &$tableTitle, $tableDef)
	{
		$parser->mOutput->mXssOutputTableDef=$tableDef;
	}

	function outputRowExists(&$parser, $rowName)
	{
		return isset($parser->mOutput->mXssOutputRowNames[$rowName]);
	}

	function makeOutputRow($pageTitle, $tableName,$rowName,$rowData)
	{
		$rowData['_page_ns']=$pageTitle->getNamespace();
		$rowData['_page_title']=$pageTitle->getDBkey();
		$rowData['_row_name']=$rowName;

		if($rowName{0}=='#')
		{
			$rowData['_row_ref'] = $pageTitle->getPrefixedDBkey().$rowName;
		}
		elseif ("0".$rowName==$rowName )
		{
			$rowData['_row_ref'] = $pageTitle->getPrefixedDBkey()."#".$rowName;
		}
		else
		{
			$rowData['_row_ref'] = $rowName;
		}
		return $rowData;
	}
	
	
	function addOutputRow($parser, $pageTitle, $tableName,$rowName,$rowData)
	{
		$parser->mOutput->mXssOutputRows[$tableName][]=$this->makeOutputRow($pageTitle,$tableName,$rowName,$rowData);
		$parser->mOutput->mXssOutputRowNames[$rowName]=true;
	}
	
	function makeMultiRow($fromRowName, $toRowName) {
		if (!$this->normalizeName($fromRowName)) return false;
		if (!$this->normalizeName($toRowName)) return false;
		
		$rowData['_multi_from'] = $fromRowName;
		$rowData['_multi_to'] = $toRowName;
		return $rowData;
	}
	
	function addMultiRow($parser, $tableName, $fieldName, $fromRowName, $toRowName) {
		$rowData = $this->makeMultiRow($fromRowName,$toRowName);
		$multiTableName = $this->escapeMultiTableName($tableName,$fieldName);
		if (is_array($rowData)) $parser->mOutput->mXssMultiRows[$multiTableName][]=$rowData;
	}

###################################
##
##  SAVING TABLES AND ROWS - from the parserOutput passed by LinksUpdate.php, which we hacked for the purpose
##
###################################

	function makeColumnLine($fieldDef, $op='CREATE')
	{	
		switch ($op)
		{
		case 'CREATE':
			return "\n\t"  
				 . $this->escapeName($fieldDef['field_name']) . " " 
				 . Xtt::getDbFieldType($fieldDef['field_type']) 
				 . ","
				 . "\n\t" 
				 . "INDEX (" . $this->escapeName($fieldDef['field_name'])
				 . ( $fieldDef['field_type']=='text' ? '(255)' : '') 
				 . ")";
		case 'ADD':
			return "\n\tADD COLUMN "  
				 . $this->escapeName($fieldDef['field_name']) . " "
				 . Xtt::getDbFieldType($fieldDef['field_type']) 
				 . ","
				 . "\n\t" 
				 . "ADD INDEX (" . $this->escapeName($fieldDef['field_name'])
				 . ( $fieldDef['field_type']=='text' ? '(255)' : '') 
				 . ")";
		case 'DROP':
			return "\n\tDROP COLUMN "  
				 . $this->escapeName($fieldDef['field_name']);
		case 'MODIFY':
			return "\n\tMODIFY COLUMN "  
					. $this->escapeName($fieldDef['field_name']) . " "
					. Xtt::getDbFieldType($fieldDef['field_type'])
					. ( $fieldDef['field_type']=='text' ? '(255)' : '') ;
		}
	}

	function makeMultiQuery($tableName,$fieldName, $op)
	{	
		$multiTableName=$this->escapeMultiTableName($tableName,$fieldName);
		
		switch ($op)
		{
		case 'ADD':
			return "CREATE TABLE " . $multiTableName . "\n" 
					. "( _multi_from CHAR(255), _multi_to CHAR(255), _multi_page_id INT(11),\n"
					. "INDEX (_multi_from), INDEX (_multi_to), INDEX(_multi_page_id));\n";				
		case 'DROP':
			return "DROP TABLE IF EXISTS " . $multiTableName . ";\n";
		}
		return "";
	}
	
	
	function saveTableData(&$parserOutput,$tableTitle,$pageId,$getSqlOnly=false)
	{
		# Create table, insert field definitions		
		if ($tableTitle->getNamespace()!=NS_XSSDATA) return false; # sanity check;
	
		$tableDef  = $parserOutput->mXssOutputTableDef;
		if (!$tableDef) return false; #sanity check

		$tableName = $tableTitle->getText();
		if (!$this->normalizeName($tableName)) die($tableName); #return false; # sanity check;
		$oldTableDef = $this->getTableDefFromDB($tableName);
		$dbr = $this->getDbr();

#			print "<pre>";
#			print $tableTitle->getText()."<br>";;
#			print_r(array('old'=>$oldTableDef,'new'=>$tableDef));
#			print "</pre>";
		if (count($oldTableDef['fieldsByName'])==0)
		{
			# insert a new table

		    $this->deleteTableData($tableTitle,$pageId); // just in case

		

			# CREATE tbl_TableName from $this->mOutpuTableDef
		
			$sql= "CREATE TABLE " . $this->escapeDataTableName($tableName) . "\n(\n";
			$sql.= "\t`_page_id`\t INT(11) NOT NULL,\n";
			$sql.= "\t`_page_ns`\tCHAR(255) NOT NULL,\n";
			$sql.= "\t`_page_title`\tCHAR(255) NOT NULL, \n";
			$sql.= "\t`_row_name`\tCHAR(255) NOT NULL,\n";
			$sql.= "\t`_row_ref` \tCHAR(255) NOT NULL PRIMARY KEY,";
			$sql.= "\n\tINDEX (`_page_id`),\n";
			$sql.= "\tINDEX (`_page_ns`),\n";
			$sql.= "\tINDEX (`_page_title`),\n";
			$sql.= "\tINDEX (`_row_name`),\n";
			$sql.= "\tINDEX (`_row_ref`)";
	
			$fieldDefs=$tableDef['fieldsByNumber'];
			$multis=array();
			foreach ($fieldDefs as $fieldDef)
			{
				if ($fieldDef['field_type'] == 'multi') {
					$multis[]= $this->makeMultiQuery($tableName,$fieldDef['field_name'],'ADD');				
				} else {
					$sql.=",\n\t" . $this->makeColumnLine($fieldDef);
				}
			}

			$sql.="\n);\n\n";
			if ($getSqlOnly) return $sql.join($multis,'\n');
			$dbr->query($sql);
			foreach($multis as $sql) {
				$dbr->query($sql);
			}
		}
		else
		{
			# ALTERing an existing table
			
			$oldFieldDefs = $oldTableDef['fieldsByName'];
			$newFieldDefs = $tableDef['fieldsByName'];
			
			$alterBits=array();
			$combinedKeys = array_keys( $oldFieldDefs + $newFieldDefs);
			
			$multis=array();
			foreach ($combinedKeys as $k) 
			{
				$new =& $newFieldDefs[$k];
				$old =& $oldFieldDefs[$k];
				
				if ($old['field_type']== 'multi' && (!$new  || $new['field_type']!='multi')) {
					$multis[]= $this->makeMultiQuery($tableName,$old['field_name'],'DROP');
					$old = null;
				} elseif ($new['field_type']=='multi' && (!$old  || $old['field_type']!='multi') ) {
					$multis[]= $this->makeMultiQuery($tableName,$new['field_name'],'ADD');
					$new = null;
				} elseif ($old['field_type']=='multi' && $new['field_type']=='multi') {
					$new = null;
					$old = null;
				} 
				if (!$new && $old) $alterBits[] = $this->makeColumnLine($old,'DROP');
				elseif (!$old && $new) $alterBits[] = $this->makeColumnLine($new,'ADD');
				elseif ($old && $new!=$old) $alterBits[] = $this->makeColumnLine($new,'MODIFY'); 
				# else nothing's changed, so we don't need to add anything
			}
			if (count($alterBits))
			{
				$sql = "ALTER IGNORE TABLE " . $this->escapeDataTableName($tableName) . "\n";
				$sql.= join(",",$alterBits);
			
				$sql.=";\n\n";

				if ($getSqlOnly) return $sql.join($multis,'\n');
				$dbr->query($sql);
#				die ("<pre>$sql</pre>");
#				print $sql;
			}
			if ($getSqlOnly) return $sql.join($multis,'\n');
			foreach($multis as $sql) {
				$dbr->query($sql);
			}
			$fieldDefs=$tableDef['fieldsByNumber'];
		}
		if ($getSqlOnly) return "";

		# INSERT field definitions INTO xss_fields
		$dbr->delete( $this->getInternalTableName('fields'), array( 'field_table' => $tableName ) );
		$dbr->insert($this->escapeInternalTableName('fields'),$fieldDefs);		
		$dbr->commit();
		return true;
	}

	function saveRowData(&$parserOutput,$pageTitle, $pageId)
	{
	 	if ($pageTitle->getNamespace()==NS_XSSDATA) return false; # sanity check;

    	$dbr =& $this->getDbr();
    	if (!$dbr) return true; 
		$wikiDbr =& wfGetDB( DB_MASTER );

		$outputRows=$parserOutput->mXssOutputRows;
		
		# insert data
		$tableCount=0;
		$rowCount=0;

		$linkTableRows=array();
		$templateLinkTableRows=array();
		
		if (is_array($outputRows)) {
			foreach( $outputRows as $tableName => $tableRows)
			{
				$tableCount++;

				# add _page_id
				foreach($tableRows as &$tableRow)
				{
					$tableRow['_page_id']=$pageId;
					$rowCount++;
				}
				#insert data rows for each table
				try {
					$dbr->insert($this->getDataTableName($tableName),$tableRows);
				} catch (Exception $e) { print $e.message; }
				#insert links from this page for each table
				$linkTableRows[]=array
				(
					'ln_from'=>$pageId,
					'ln_title'=>$tableName
				);
#				$templateLinkTableRows[]=array
#				(
#					'tl_from'=>$pageId,
#					'tl_namespace'=>NS_XSSDATA,
#					'tl_title'=>$tableName
#				);
#				#also add to wiki "what links here (include)"
			}
			$dbr->insert($this->escapeInternalTableName('links'), $linkTableRows);
		}			
#		$wikiDbr->insert('templatelinks',$templateLinkTableRows); #TODO: replace by wiki's internal mechanism
	}

	function saveMultiData(&$parserOutput,$pageTitle, $pageId)
	{
	 	if ($pageTitle->getNamespace()==NS_XSSDATA) return false; # sanity check;

    	$dbr =& $this->getDbr();
    	if (!$dbr) return true; 
		$wikiDbr =& wfGetDB( DB_MASTER );

		$outputRows=$parserOutput->mXssMultiRows;
		
		# insert data
		$linkTableRows=array();
		if (is_array($outputRows)) {
			foreach( $outputRows as $tableName => $tableRows)
			{
				# add _page_id
				foreach($tableRows as &$tableRow) {
					$tableRow['_multi_page_id']=$pageId;
				}
				
				#insert data rows for each table
				$dbr->insert($tableName,$tableRows);

				#insert links from this page for each table
				$linkTableRows[]=array
				(
					'ml_from'=>$pageId,
					'ml_table'=>$tableName
				);
			}
			$dbr->insert($this->escapeInternalTableName('multilinks'), $linkTableRows);
		}			
	}
	
	function deleteTableData(&$title,$pageId=null)  #this really only needs the title, since we can figure everything out from that
	{
		# Drop table, delete field definitions		
		if ($title->getNamespace()!=NS_XSSDATA) return false; # sanity check;
		#DROP TABLE tbl_TableName

		$dbr = $this->getDbr();
		$tableName = $title->getText();
		$tableDef  = $this->getTableDef($tableName);
	
		$dbr->begin();

		$fieldDefs = $this->getTableDefFromDB($tableName);
		
		if (is_array($fieldDefs)) {
			$multis=array();
			foreach ($fieldDefs['fieldsByNumber'] as $fieldDef)
			{
				if ($fieldDef['field_type'] == 'multi') {
					$multis[]= $this->makeMultiQuery($tableName,$fieldDef['field_name'],'DROP'); 
					print($multis);
				}
			}
			foreach($multis as $sql) {
				$dbr->query($sql);
			}
		}

		#DELETE * FROM xss_fields WHERE field_table = $tableName
		#TODO what about multilinks?
		$dbr->delete( $this->getInternalTableName('fields'), array( 'field_table' => $tableName ) );

		$sql = "DROP TABLE IF EXISTS " . $this->escapeDataTableName($tableName) . ";\n";
		$dbr->query($sql);
		
		$dbr->commit();
	}


	function deleteRowData(&$title,$pageId )
    {

    	if ($title->getNamespace()==NS_XSSDATA) return false; # sanity check;

    	$dbr =& $this->getDbr();
    	if (!$dbr) return true; 
		$wikiDbr =& wfGetDB( DB_MASTER );

		
		#get links from this page to individual tables and remove this page's rows from those tables
		$res= $dbr->query("SELECT ln_title FROM ".$this->getInternalTableName('links')." WHERE ln_from=$pageId");
#		print "fetch<br>---<br>";
		while ($res && $row=$dbr->fetchRow($res))
		{
#			print "fetched<br>---<br>";
#			print_r($row);
			$tableName=$this->escapeDataTableName($row['ln_title']);
#			print "maybe delete $tableName<br>---<br>";
			if($dbr->tableExists($tableName))
			{
#				print "delete $tableName<br>---<br>";
				$dbr->delete( $tableName, array( '_page_id' => $pageId ) );
#				print "deleted $tableName<br>---<br>";
			}
#			else print "not found $tableName<br>---<br>";
		};
#		print "done<br>---<br>";
		
		#delete all links from this page to datatables
		$dbr->delete( $this->escapeInternalTableName('links'), array( 'ln_from' => $pageId ) );
		
		#delete all template links we inserted TODO: replace by wiki's internal mechanism
		#$wikiDbr->delete('templatelinks',array('tl_from'=>$pageId,'tl_namespace'=>NS_XSSDATA));
	}
	
	function deleteMultiData(&$title,$pageId )
    {
    	if ($title->getNamespace()==NS_XSSDATA) return false; # sanity check;
    	$dbr =& $this->getDbr(); if (!$dbr) return true; 
		
		#get links from this page to individual tables and remove this page's rows from those tables
		$res= $dbr->query("SELECT ml_table FROM ".$this->getInternalTableName('multilinks')." WHERE ml_from=$pageId");
		if($res) {
			for ($i=0; $i < $dbr->numRows($res);$i++) {
					$row=$dbr->fetchRow($res);
					$tableName=$row['ml_table'];
					if($dbr->tableExists($tableName)) {
		#				print "deleting $tableName\n";
						$dbr->delete( $tableName, array( '_multi_page_id' => $pageId ) );
					}
				};
		};
		$dbr->delete( $this->escapeInternalTableName('multilinks'), array( 'ml_from' => $pageId ) );		
	}
	
	# TODO: 
	function updateRowDataOnPageMove(&$oldTitle, &$newTitle,$pageId)
    {
    	if ($newTitle->getNamespace()==NS_XSSDATA) return false; # TODO: delete row data if page moved to Data: namespace

    	$dbr =& $this->getDbr();
    	if (!$dbr) return true; 
		$wikiDbr =& wfGetDB( DB_MASTER );

		# Delete all data rows for this page and links from the page to individual tables

		#get links from this page to individual tables and remove this page's rows from those tables
		$res= $dbr->select($this->getInternalTableName('links'),'ln_title',array('ln_from'=>$pageId));
		
		$tableCounter=0;
		while ($row=$dbr->fetchRow($res))
		{
			if($dbr->tableExists($row['ln_title']))
			{
				$sql = "UPDATE ". $this->getDataTableName($row['ln_title']) . " SET "
					 . '_page_ns =' . $newTitle->getNamespace() .','
					 . '_page_title =' . $this->escapeValue($newTitle->getDbKey()) . ','
					 . '_row_ref = CONCAT(' . $this->escapeValue($newTitle->getDbKey() . '#' ) . ', _row_name'
					 . ') WHERE _page_id='.$pageId.' AND _row_ref LIKE ' . $this->escapeValue($oldTitle->getDbKey() . '#%');
				$dbr->query($sql);
				$tableCounter++;		
			}
		};
	}
	
	function updateTableDataOnPageMove(&$oldTitle, &$newTitle,$pageId)	#TODO: will probably need the old title too, or the page id should be saved in the field defs
	{
	}
	

###################################
##
##  Hooks
##
###################################
	

	function hook_ArticleDelete(&$article)
	{
		$this->mSaveArticleIdForDelete=&$article->getID(); 	# This is too messay. TODO: Fix.
		return true;
	}
	
	function hook_ArticleDeleteComplete( &$article )
    {
    	$title=$article->getTitle();
    	$pageId=$this->mSaveArticleIdForDelete;				# This is too messay. TODO: Fix.
    	
    	$this->deleteTableData($title,$pageId);
    	$this->deleteRowData($title,$pageId);
    	$this->deleteMultiData($title,$pageId);
   		return true;
	}
	

   
	function hook_LinksUpdateComplete(&$linksUpdate) # TODO: Figure out how it works
	{
		$text = $linksUpdate->getTitle()->getFullText();
						
    	$title=$linksUpdate->getTitle();
    	$pageId=$linksUpdate->mId;

    	#$this->deleteTableData($title,$pageId);
    	$this->deleteRowData($title,$pageId);
    	$this->deleteMultiData($title,$pageId);

    	$this->saveTableData($linksUpdate->mParserOutput,$title,$pageId);
    	$this->saveRowData($linksUpdate->mParserOutput,$title,$pageId);
    	$this->saveMultiData($linksUpdate->mParserOutput,$title,$pageId);
		return true;
	}

	
	#TODO: fix this for row page moves vs. table page moves, also make less destructive, especially for table moves. currently just delets old data, should get links and fix in tables instead.
	function hook_TitleMoveComplete(&$oldTitle,&$newTitle)
	{
		$article=new Article($newTitle);
		$pageId=$article->getID();

		$this->updateTableDataOnPageMove($oldTitle,$newTitle,$pageId);
		$this->updateRowDataOnPageMove($oldTitle,$newTitle,$pageId);
		return true;
	}
	
}

