<?php
#
#	Preliminaries, look lower for interesting stuff
#
if ( !defined( 'MEDIAWIKI' ) ) { 
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}
require_once ("DataQuery.php");
XxxInstaller::Install('Xss');

#########################
#
# TODO: Make namespace settings customizable, but note that Xss::ExtensionSetup runs too late for this to work there
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
	var $mDefaultSettings = array (
		"dbsuffix"			=> '_xss', 		# will be added to the wikibase name, if no dbname is provided
		"tableprefix"		=> '',			# prefix for data tables
		"internalprefix"	=> '_xss_',		# prefix for internal tables
		"dbtype" 			=> XXX_LATER, 	# defaults to $wgDbType
		"dbname" 			=> XXX_LATER, 	# defaults to $wgDbName . dbsuffix
		"dbhost" 			=> XXX_LATER, 	# defaults to $wgDbServer
		"dbuser" 			=> XXX_LATER, 	# defaults to $wgDbUser
		"dbpass" 			=> XXX_LATER, 	# defaults to $wgDbPassword
		"nsnumber"			=> 1244, 		# namespace settings
		"nsname"			=> 'Data',
		"nstalk"			=> 'Data_talk'
	);
	
	function getSQL($which, $a1=NULL,$a2=NULL,$a3=NULL,$a4=NULL,$a5=NULL) {
	  $type = $this->S('dbtype');
	  switch ("$which|$type") {
	  case 'tables|mysql':
	    return "
        CREATE TABLE `" . $this->S('internalprefix'). "tables` (
          `table_name` char(160) NOT NULL,
          `table_prop` char(160) NOT NULL,
          `table_val` char(160) NOT NULL,
          PRIMARY KEY  (`table_name`,`table_prop`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	    ";
	  case 'tables|postgres':
	    return "
        CREATE TABLE " . $this->S('internalprefix'). "tables (
          table_name TEXT NOT NULL,
          table_prop TEXT NOT NULL,
          table_val TEXT NOT NULL,
          PRIMARY KEY  (table_name,table_prop)
        );
	    ";
	  case 'fields|mysql':
	    return "
        CREATE TABLE " . $this->S('internalprefix'). "fields (
          `field_table` char(160) NOT NULL,
          `field_name` char(160) NOT NULL,
          `field_type` char(64) NOT NULL,
          `field_default` text,
          `field_reference` char(160) default NULL,
          `field_reverse` char(160) default NULL,
          PRIMARY KEY  (`field_table`,`field_name`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
      ";
	  case 'fields|postgres':
	    return "
        CREATE TABLE " . $this->S('internalprefix'). "fields (
          field_table TEXT NOT NULL,
          field_name TEXT NOT NULL,
          field_type TEXT NOT NULL,
          field_default TEXT,
          field_reference TEXT NULL,
          field_reverse TEXT NULL,
          PRIMARY KEY  (field_table, field_name)
        );
      ";
	  case 'links|mysql':
	    return "
        CREATE TABLE " . $this->S('internalprefix'). "links (
          `ln_from` int(11) NOT NULL,
          `ln_title` char(255) NOT NULL,
          PRIMARY KEY  (`ln_from`,`ln_title`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
      ";
	  case 'links|postgres':
	    return "
        CREATE TABLE " . $this->S('internalprefix'). "links (
          ln_from INTEGER NOT NULL,
          ln_title TEXT NOT NULL,
          PRIMARY KEY (ln_from, ln_title)
        )
      ";
	  case 'multilinks|mysql':
	    return "
        CREATE TABLE " . $this->S('internalprefix'). "multilinks (
          `ml_from` int(11) NOT NULL,
          `ml_table` char(255) NOT NULL,
          PRIMARY KEY  (`ml_from`,`ml_table`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
      ";
	  case 'multilinks|postgres':
	    return "
        CREATE TABLE " . $this->S('internalprefix'). "multilinks (
          ml_from INTEGER NOT NULL,
          ml_table TEXT NOT NULL,
          PRIMARY KEY  (ml_from,ml_table)
        )
      ";
    }
	}

	function setupExtension() {
		global $wgExtraNamespaces;
		global $wgDBtype;
		global $wgDBname;
		global $wgDBserver;
		global $wgDBuser;
		global $wgDBpassword;

		$this->setDefaultSetting('dbtype',$wgDBtype);
		$this->setDefaultSetting('dbname',$wgDBname . $this->S('dbsuffix'));
		$this->setDefaultSetting('dbhost',$wgDBserver);
		$this->setDefaultSetting('dbuser',$wgDBuser);
		$this->setDefaultSetting('dbpass',$wgDBpassword);

		$dbr=$this->getDbr();

	  if (!$dbr->tableExists("{$pref}tables")) {
		  $dbr->query($this->getSql('tables'),__METHOD__,__LINE__);
	  }
	  if (!$dbr->tableExists("{$pref}fields")) {
		  $dbr->query($this->getSql('fields'),__METHOD__,__LINE__);
		}
	  if (!$dbr->tableExists("{$pref}links")) {
		  $dbr->query($this->getSql('links'),__METHOD__,__LINE__);
		}
	  if (!$dbr->tableExists("{$pref}multilinks")) {
		  $dbr->query($this->getSql('multilinks'),__METHOD__,__LINE__);
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
	var $mOutputRows		= array();	# an array of arrays of actual rows in data tables to save
										# $this->mOutputRows[tableName][rowNumber][fieldName]=value
	var $mOutputRowNames	= array();	# an array of arrays of flags for used row names
										# $this->mOutputRowNames[rowName]=true;
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
	
 	function fl_data(&$parser, $frame, $a)
	{
		static $rowCounter=0;
		$args=new xxxArgs($frame, $a);
		$cmd = $args->command;
		switch ($cmd)
		{

###################################
##
##  {#data:table
##	| field = type 
##	| field = type | default value
##	| field = #table name | name of the reverse reference
##	| field = ##table name | name of the reverse reference
##	| ...
##	}}
##
###################################

		case 'table':
			#we don't need cache on this page
			$parser->disableCache();
			
			# only allowed in the data namespace
			$pageTitle=$parser->getTitle();
			$pageNamespace=$pageTitle->getNamespace();
			
			if ($pageNamespace!=NS_XSSDATA) { 
			    return $this->formatError(
			    	"Table definitions only allowed in ".$wgExtraNamespaces[NS_XSSDATA]." namespace ($pageNamespace)"
			    );
			}
			
			# only one table per page allowed
			if ($this->outputTableDefExists($parser)) return $this->formatError("Only one data def per page allowed");
		
			# construct a table def from arguments
			
			#get the table name
			$tableName     	= 	$pageTitle->getText();
			
			# normalize or die 
			if(!$this->normalizeName($tableName)) return $this->formatError("Bad table name $tableName");
			
			$fieldRows		=	array();	# this is where we put data we gathered
			$fieldNames		=	array();	# check for duplicate names	
			$fieldTypes		=	array();	# for showing in the data grid
			$defaultValues	=	array();	# for showing in the data grid

			# loop through arguments, all the continue statements below go here
			for ($i = 1; $i<=$args->count; $i++) {
				if ($args->isNamed($i)) {
				
    				# named argument, either a field or a special argument			
					$argName  = $args->getName($i);
					$argValue = $args->trimExpandValue($i);

                    # it's a special argument, i.e. its name starts with a #
					if ($this->removePrefix($argName,'#')) {
						switch ($argName) {
							#none for now
							default: 
							$errorMessage .= $this->formatError("Unrecognized argument $key");
						}
						continue;
					}
					
					# if we're still here, it's a field definition

					#get the field's name
					$fieldName = $argName;
					
					if (!$this->normalizeName($fieldName)) {
					    # it can't be normalized
						$errorMessage .= $this->formatError("Bad field name $argName");
						continue;
					}
					
					if (isset($fieldNames[$fieldName])) { 	
					    # already exists
						$errorMessage .= $this->formatError("Duplicate field name $key");
						continue;
					}
					
					# it's a new field
					
					# register it for the grid output
					
					$fieldNames[$fieldName]		= true;
					$defaultValues[$fieldName]	= null;
					
					# setup defaults for this field
					$fieldType					= null;
					$fieldDefault   			= null;
					$fieldReference 			= null;
					$fieldReverse   			= null;
                    
                    # get the value of the current argument, i.e. the part after =
					$argValue = $args->trimExpandValue($i);

                    # does it start with a # 
					if (!$this->removePrefix($argValue,'#')) {
					    # the value doesn't start with a #, so it's the name of the simple type for this field
						
						if (!$this->normalizeName($argValue)) {
    						# the type can't be properly normalized
						    $errorMessage.=$this->formatError("Bad type name $fieldName = $argValue");
						    continue;
						}
                        
                        #lowercase the typename
                        #TODO: this is messy, fix Xtt instead
						$fieldType = strtolower($argValue);	
						#reject if if the type doesn't exist
						#TODO: add function typeExists to Xtt and use it here								
						if (!Xtt::getDbFieldType($fieldType)) {
						    $errorMessage .= $this->formatError("Unknown field type $fieldName = $fieldType");
						    continue;
						}
						$fieldTypes[$fieldName]	= $fieldType;

						# if the next argument is nameless, then it's the default value
						# if it needs to contain "=", start it with a = i.e. |field=type|=default
						if ($args->exists($i+1) and ($args->isNumbered($i+1) or $args->getKey($i+1)==''))
						{
							# the next argument is the default value
							$fieldDefault = $args->cropExpandValue($i+1);
							# fail if it fails to cast to the type
							if (!Xtt::castValue($fieldType,$fieldDefault)) {
							    $errorMessage .= $this->formatError(
							    	"Invalid default value $fieldName = $fieldType | $fieldDefault"
							    );
							    continue;
							}
							#if everything is right, register the default value and increase the counter
							$defaultValues[$fieldName]	= $fieldDefault;
							$i++;
						}
					} else {
						# the field started with a #, so it's a reference to another table
						# is it a multi-multi reference, i.e. does it have another #?						
						if ($this->removePrefix($argValue,'#')) {
							$fieldType='multi';						
						} else {
							$fieldType='reference';												
						}
						#register the field type for grid display
						$fieldTypes[$fieldName]	= $fieldType;
				
						# we're left with the related table name
						# normalize or die!
						if (!$this->normalizeName($argValue)) {
							$errorMessage .= ("Bad referenced table name $argValue");
	                        continue;
	                    }
						$fieldReference = $argValue;
				
						# check if the next field exists and is not named
						if ($args->exists($i+1) and $args->isNumbered($i+1)) {
							# the next field is the name of the reverse reference							
							#normalize or die
							$rev = $args->trimExpand($i+1);
							if (!$this->normalizeName($rev)) {
								$errorMessage .= $this->formatError("Bad reverse reference name $rev");
								continue;
							}
							#all is right
							$fieldReverse = $rev;
							$i++;
						}
					}
					$fieldRows[]=$this->makeFieldDef(
						$tableName, 
						$fieldName, 
						$fieldType, 
						$fieldDefault,
						$fieldReference, 
						$fieldReverse
					);
				} 
				# done field definition					
				else 
				{
					# it's an unexpected nameless argument				
					$errorMessage .= $this->formatError ("Unrecognized argument ".$args->trimExpand($i));
				}
			} 
			$dbr = $this->getDbr();
			$res = $dbr->select
			(
				$this->escapeInternalTableName('fields'),
				'*',
				$this->escapeValue($tableName) . ' = field_reference 
				AND field_reverse<>\'\' AND field_table <> ' . $this->escapeValue($tableName) 
			);
			while ($row=$this->fetchAssoc($dbr,$res)) {
				$fieldRows[]=$row;
			}
			$res = $dbr->select
			(
				$this->escapeInternalTableName('fields'),
				'*',
				$this->escapeValue($tableName) . " = field_reference 
				AND (field_reverse IS NULL) AND field_table <> " . $this->escapeValue($tableName) 
			);
			while ($row=$this->fetchAssoc($dbr,$res)) {
				$fieldRows[]=$row;
			}

			$tableDef=$this->makeTableDef($tableName, $fieldRows);
			$this->addOutputTableDef($parser, $pageTitle, $tableDef);
			
			$returnText = $this->formatTableDef($tableDef);
			return array(0=>$returnText.$errorMessage,'isHTML'=>true,'noParse'=>true);
###################################
##
##  {{#data:row          - set the data and display the row in a table
##  {{#data:set          - set the data and display nothing
##  {{#data:guess        - guess the data from likely wikipedia input
##  | table_name
##  | row_name           - blank or missing = {{FULLPAGENAME}}
##                         starting with #  = {{FULLPAGENAME}}{{{2}}}
##                         ending with #    = {{{2}}}{{#str:random}}
##                         #                = {{FULLPAGENAME}}#{{#str:random}}
##                         otherwise        = {{{2}}}
##	| field = value
##	| field = value
##	| field = value
##	| field = value
##	| ...
##	}}
##
###################################
# TODO: fix so that row names really work as described
		case 'ref':
		case 'row':
		case 'set':
		case 'guess':
			$pageTitle=$parser->getTitle();
			$pageNamespace=$pageTitle->getNamespace();
			
			# not allowed in data namespace
		    if ($pageNamespace==NS_XSSDATA) {
		    	return	  "<b>Data definitions not allowed in "
		    			. $wgExtraNamespaces[NS_XSSDATA]
		    			. " namespace ($pageNamespace)</b>";
		    }
		    
		    # we need at least one argument
			if (!$args->exists(1)) return $this->notFound();

			# we don't need this. TODO: fix getFieldValues so that we don't need this
			$rowCounter++;
			
			# extract fieldvalues from the arguments
			extract($this->getFieldValues($parser,$frame,$args,$rowCounter,true,$cmd=='guess'));
			if ($fatal) return $error;
			$errorMessage .= $error;

			# add a transclusion link, so data gets updated in queue jobs			
			$tableTitle=Title::makeTitle(NS_XSSDATA,$tableName);
			$parser->fetchTemplate($tableTitle);

			$pageName = $parser->mTitle->getPrefixedDBKey();
			if ($this->outputRowExists($parser,$rowName)) return $this->formatError ("Duplicate row name $rowName"); 
			# TODO: handle this more wiki way, probably display anyway, just not save

			# add to the parser output, to be saved when links are updated, i.e. also in queued jobs
			$this->addOutputRow ($parser, $pageTitle, $tableName, $rowName, $fieldValues);
			
			# if we're setting, we're done;
			if ($args->command=='set') return $errorMessage;
			if ($args->command=='ref') return $rowName;
			
			# else format the row as a pretty table
			$returnText="";
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
			$returnText .= $this->formatTable($tableHead, $tableBody,'xss-rowtable');
			$returnText .= $error;
			return "<h2>$rowName</h2>$returnText $errorMessage";

			
#############################################################################
#############################################################################
###
###  {{#data:getrow|table|rowid}} returns record array
###  {{#data:get|table|rowid|fieldname}} returns field value
###  {{#data:maprow|table|rowid|template|arg=val|...}} maps the record to the tempate
###  {{#data:evalrow|table|rowid|arg=val|...|code}} evals the code with field values as params
##############################################################################
##############################################################################
		case 'get':
			# we need at least 3 arguments for get
			if ($args->count>3) $this->notFound();			
			$fieldName = $args->trimExpand(3,''); 
			if (!$this->normalizeName($fieldName) && $fieldName{0}!='#') return $this->notFound();
		case 'getrow':
			# TODO: make it possible to add extra values to the array, as in wraprow and eval row
			# TODO: make this gracefully fail if the array extension is not installed
			# we need exactly two arguments for getrow
			# TODO: figure out why the following was commented
#			if ($args->count>2) return $this->notFound();
		case 'maprow':
		case 'evalrow':
			#we need at least two arguments for anything
			if ($args->count<2) return $this->notFound();
			#get table name, check that it exists, escape it
			$tableName=$args->trimExpand(1);
			if (!$this->normalizeName($tableName)) return $this->notFound();
			if (!$this->tableExists($tableName)) return $this->notFound();
			
			#get row ref
			$rowRef=$this->escapeValue($args->trimExpand(2));
			if (!$this->normalizeRef($rowRef)) return $this->notFound();
		
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
				$dbr=$this->getDbr();
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
			elseif ($args->command=='maprow')
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

				$customFrame = $this->newChildFrame($frame,$row,$parser->mTitle);
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

				$customFrame = $this->newExtendedFrame($frame,$row);
			    return $customFrame->expand($code);			
			}
			else return $this->notFound();
			break;
#############################################################################
#############################################################################
###
###  {#data:schema|format}}
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
		FROM $fieldTable 
	) UNION ( 
		SELECT 	field_reference AS ta, 
			field_reverse AS fi, 
			CONCAT('rev_',field_type) AS ty, 
			field_table AS re
		FROM $fieldTable 
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

#############################################################################
#############################################################################
###
###  {#data:select|query }}
###
##############################################################################
##############################################################################
			
			
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

			extract ($this->getQueryArgs('SELECT',$args));
			#we now have $fields and $options
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
				try
				{
					$now = time() + microtime();
					$res=$dbr->query($sql,__METHOD__,true);
					if ($args->command != 'grid') $returnText.=sprintf("%0.5fs<br>",time()+microtime()-$now);
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
				       $returnText = $wgXooExtensions['Xvv']->arrMake($resultArray);
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
####     END {{#data:}}
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
		global $wgDBname;
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
				$this->mDbr= wfGetDB( DB_MASTER );
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
		$s=$t->getPrefixedDbKey();
		return $s;
	}

	function normalizeRef(&$s)
	{
	  return Xtt::castValue('reference',$s);
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
		return '`'.trim(preg_replace('/`/','`',$s)).'`';
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
			'name'				=> $tableName,
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
			else if ($referenceName == $tableName)
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
	    $dbr = $this->getDbr();
    	if (!$dbr) return false; 
		$tableName = $this->getDataTableName($tableName);
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
			$this->escapeValue($tableName) . ' IN (field_reference,field_table)'
		);
		
		$fieldDefs=array();
		while ($row=$this->fetchAssoc($dbr,$res)){
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
		foreach( $tableDef['fieldsByName'] as $k=>$v) {
			if($v['field_type']!='multi') {
				$def [$k]=isset($tableDef['fieldDefaults'][$k]) ? $tableDef['fieldDefaults'][$k] : null;
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
		if (!$tableDef = $this->getTableDef($tableName)) return false;
		if (!isset($tableDef['fieldsByName'][$fieldName])) return false;
		return $tableDef['fieldsByName'][$fieldName];
	}
	
	function getFieldProperty($tableName,$fieldName,$property)
	{
		if (!$fieldDef = $this->getFieldDef($tableName,$fieldName)) return false;
		return $fieldDef['field_'.$property];
	}

	function getReverseDef($tableName,$fieldName)
	{
		if (!$tableDef= $this->getTableDef($tableName)) return false;
		if (!isset($tableDef['reverseByName'][$fieldName])) return false;
		return $tableDef['reverseByName'][$fieldName];
	}
	
	function getReverseProperty($tableName,$fieldName,$property)
	{
		if (! $fieldDef= $this->getReverseDef($tableName,$fieldName)) return false;
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
					$rowName = $parser->mTitle->getPrefixedDbKey().'#'. ($onMissingId ? $onMissingId : $parser->getRandomString());
				} elseif ($rowName=='' && !$pref){
					$rowName = 'guid_'.$parser->getRandomString();
				} elseif (!$this->normalizeRef ($rowName)) {
					return array('fatal'=>true,'error'=> $this->formatError ("Bad row name $rowName (table $tableName)")); 
				}
				$argOffset=3;
			}
			else # row name not supplied, so we use the parameter, or a random string
			{
#				$rowName="test";
				$rowName = $parser->mTitle->getPrefixedDbKey();
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
							$fieldName = $fieldName;
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
							  $argValue = $args->cropExpand($i);
/*							
								$argName = $args->trimExpand($i);
								if ($frame->parent && isset($frame->namedArgs[$argName]) ) {
									$argValue = $frame->parent->expand($frame->namedArgs[$argName]);
								} else {
									$argValue = '';
								}
*/							}
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
								//mi.tko - add empty field values! if ($argValue!=='')	
								$fieldValues[$fieldName]=$argValue;
								$fieldDisplay[$fieldName] = $argValue;
							} elseif (Xtt::castValue($fieldType,$argValue)) {
								//mi.tko if ($argValue!=='')	
								$fieldValues[$fieldName]=$argValue;
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
			$dbr = $this->getDbr();
			if (!$dbr) return true; 
			$wikiDbr = wfGetDB( DB_MASTER );
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
						$options[$name]=(int)$val > 2000 ? 2000 : (int)$val < 1 ? 1 : (int)$val; $optionFound=true; break;
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

	function formatTableDef(&$tableDef)
	{
		$tableName = $tableDef['name'];
		$pageTitle=Title::NewFromText($tableName,NS_XSSDATA);
		# done looping through arguments, display stuff
				$returnText = '<div class="xss-tabledef-heading"><a class="xss-button selected" href="' . $pageTitle->escapeFullUrl() . '">table definition</a> <a class="xss-button" href="' . $pageTitle->escapeFullUrl('command=browse') . '">browse data</a></div>';
		# display table def
		$tableHead = $this->formatHeaderRow('field name','field type','default value','referenced table.field');
		$tableBody='';
		foreach ($tableDef['fieldsByNumber'] as $fieldRow)
		{
			$cellRow = array_slice($fieldRow,1,-1);
			if ($cellRow['field_reference']) 
			{
				$refTitle=Title::newFromText($cellRow['field_reference'],NS_XSSDATA);
				$cellRow['field_reference']='<a class="'.($refTitle->exists()?'':'new').'" href="'. $refTitle->escapeFullUrl() .'">'. $refTitle->getText() .'.#</a>';
			}
			$tableBody.=$this->formatCellRow($cellRow);
#				$tableBody.=$this->formatCellRow(array_keys($cellRow));
		}
		foreach ($tableDef['reverseByNumber'] as $reverseRow)
		{
			$revTitle=Title::newFromText($reverseRow['field_table'],NS_XSSDATA);
			$cellRow = array(
				$reverseRow['field_reverse'],
				$reverseRow['field_reverse'] ? 'reverse' : 'related',
				'',
				'<a href="'. $revTitle->escapeFullUrl() .'">'. $revTitle->getText() .'.' .$reverseRow['field_name'] . '</a>'
			);
			$tableBody.=$this->formatCellRow($cellRow);
#				$tableBody.=$this->formatCellRow(array_keys($cellRow));
		}
		$returnText.=$this->formatTable($tableHead, $tableBody);

		# if the table exists, show its data with the definition parsed from text
		# this should allow friendly previews of edits to table definitions
	
		$dbr = $this->getDbr();

		if ($_GET['command']=='browse') $returnText='';
		
		if ($_GET['action'] != 'submit' && $_GET['command']!='browse') return $returnText;
		


		if ($dbr->tableExists($this->getDataTableName($tableName)))
		{
		  global $wgRequest;
		  $args = array(
  			'offset' => $wgRequest->getInt('data_offset',0),
			  'limit' => $wgRequest->getInt('data_limit',20),
			  'sort' => $wgRequest->getText('data_sort','_page_title'),
			  'dir' => $wgRequest->getText('data_dir','asc'),
			);
			
		  $base = $pageTitle->escapeFullUrl('command=browse');
			
			function browseButton($base, $args, $arg, $value, $text) {
			  $url = $base;
			  $sel = ' selected';
			  foreach ($args as $k=>$v) {
			    $url .= "&data_$k=" . urlencode($k==$arg ? $value : $v); 
			    if ($k===$arg && $v!==$value) $sel = '';
			  }
			  return "<a href=\"$url\" class=\"xss-button$sel\">$text</a>";
			}

			function sortButton($base, $args, $sort, $dir, $text) {
			  $url = $base;
			  $sel = ($sort == $args['sort'] && $dir == $args['dir']) ? ' selected' : '';
			  $url .= "&data_offset={$args['offset']}&data_limit={$args['limit']}&data_sort={$sort}&data_dir={$dir}";
			  return "<a href=\"$url\" class=\"xss-sort $dir$sel\">$text</a>";
			}


			global $wgRequest;
				$returnText = '<div class="xss-tabledef-heading"><a class="xss-button" href="' . $pageTitle->escapeFullUrl() . '">table definition</a> <a class="xss-button selected" href="'.$baseBrowseUrl.'">browse data</a></div>';
				
			


			if ($limit>2000) $limit=2000;
			$returnText 
			.=  '<div class="xss-browse-nav">' 
			.   browseButton($base,$args,'offset',max($args['offset']-$args['limit'],0),'◀')
			.   ' '
			.   browseButton($base,$args,'offset',$args['offset']+$args['limit'],'▶')
			.   '</div>'
			;

			$returnText 
			.=  '<div class="xss-browse-limit">' 
			.   browseButton($base,$args,'limit',10,'10')
			.   ' '
			.   browseButton($base,$args,'limit',20,'20')
			.   ' '
			.   browseButton($base,$args,'limit',50,'50')
			.   ' '
			.   browseButton($base,$args,'limit',100,'100')
			.   '</div>'
			;

			
			//TODO:: Use XssQuery::MakeQuery (?) for browsing table data
			
			
			$DIR = $args['dir'] == 'desc' ? 'DESC' : 'ASC'; 
			$ORDERBY = $args['sort'] ? "ORDER BY" . $this->escapeName($args['sort']) . " $DIR" : '';
			$res= $dbr->query('SELECT * FROM ' . $this->escapeDataTableName($tableName) ." $ORDERBY LIMIT {$args['limit']} OFFSET {$args['offset']}");
			
			$headerRow=array();
			$headerRow=$tableDef['fieldNames'];
			array_unshift($headerRow,'_row_ref');
			array_unshift($headerRow,'_page_title');
			array_unshift($headerRow,'');
			$missingFields=array();
			foreach ($headerRow as $k=>$fName) {
			  if ($fName) $headerRow[$k]=sortButton($base,$args,$fName,'desc',"▼") ." $fName " . sortButton($base,$args,$fName,'asc',"▲");
			}
			for ($i=5;$i<$dbr->numFields($res);$i++)
			{
				$fName=$dbr->fieldName($res,$i);
				
				if (!$tableDef['fieldsByName'][$fName]) 
				{
					$missingFields[]=$fName;
					$headerRow[]="<s>$fName</s>";
				}
			}
			$tableHead = $this->formatHeaderRow($headerRow);
			$tableBody='';
			$rowCounter = $args['offset'];
			while ($row=$dbr->fetchRow($res))
			{	
				$rowTitle=Title::makeTitle($row['_page_namespace'],$row['_page_title']);
				$rowName=preg_replace('/_/',' ',$row['_row_name']);
				$rowRef=$row['_row_ref'];
				$cellRow=array
				(
				  ++$rowCounter,
					'<a title = "'.$rowTitle->getFullText().'" href="' . $rowTitle->escapeFullUrl() .'">' . $rowTitle->getFullText() . '</a>',
					'<a title = "'.$rowRef.'" href="' . $rowTitle->escapeFullUrl() . '#' . $rowName . '">' . $rowRef . '</a>'
				);
				foreach ($tableDef['fieldsByNumber'] as $fieldNumber=>$fieldDef)
				{
					$fName=$fieldDef['field_name'];
					$fValue=$fieldDef['field_default'];
					$value=$row[$fName];
					$fixValue=$value;
					$fType=$tableDef['fieldsByName'][$fName]['field_type'];
					$cast = Xtt::castValue($fType,$fixValue);
					$displayValue=substr(htmlspecialchars($fixValue),0,255);
#						$displayValue=$fixValue;
					if (!$cast)	{
						$cellRow[]='<div class="xss-cell" title="'.$displayValue.'" style="position:relative;background:#f66;">&thinsp;'.$displayValue.'</div>';
					}	elseif ($value && $value !== $fixValue) {
						$cellRow[]='<div class="xss-cell" title="previously '.$value.'" style="position:relative;background:#fcc;">&thinsp;'.$displayValue.'</div>';
					}	elseif ($value == $fValue) {
						$cellRow[]='<div class="xss-cell" title="'.$displayValue.'" style="position:relative;background:#ffd;">&thinsp;'.$displayValue.'</div>';
					}	else {
						$cellRow[]='<div class="xss-cell" title="'.$displayValue.'" style="position:relative;">'.$displayValue.'</div>';
					}
				}
				foreach ($missingFields as $fName)
				{
					$cellRow[]="<div class=\"xss-cell\" style=\"color:red;position:relative;\"><s>{$row[$fName]}</s></div>";
				}
				$tableBody.=$this->formatCellRow($cellRow);
			};
			$returnText.=$this->formatTable($tableHead, $tableBody);
		}
		return $returnText;
	}
	
	function formatError($errorText)
	{
		return '<div class="xss-error" style="color:red;font-weight:bold">'.$errorText.'</div>';
	}
	
	function formatTable($head, $content='',$class='')
	{
		return '<div class="xss-outer-wrap"><div class="xss-inner-wrap"><table class="xss-table ' . $class.'" cellspacing="0" cellpadding="0">'.$head.''.$content."</table></div></div>";
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
		$rowData['_page_title']=$pageTitle->getPrefixedDBkey();
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

//echo "<pre>";
//print_r($rowData);
//echo "</pre>";
		return $rowData;
	}
	
	
	function addOutputRow($parser, $pageTitle, $tableName,$rowName,$rowData)
	{
		$parser->mOutput->mXssOutputRows[$tableName][]=$this->makeOutputRow($pageTitle,$tableName,$rowName,$rowData);
		$parser->mOutput->mXssOutputRowNames[$rowName]=true;
	}
	
	function makeMultiRow($fromRowName, $toRowName) {
		if (!$this->normalizeRef($fromRowName)) return false;
		if (!$this->normalizeRef($toRowName)) return false;
		
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
					. "INDEX (_multi_from), INDEX (_multi_to), INDEX(_multi_page_id)) CHARACTER SET utf8;\n";				
		case 'DROP':
			return "DROP TABLE IF EXISTS " . $multiTableName;
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
		
			$sql= "CREATE TABLE  " . $this->escapeDataTableName($tableName) . "\n(\n";
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

			$sql.="\n) CHARACTER SET utf8;\n\n";
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

    	$dbr = $this->getDbr();
    	if (!$dbr) return true; 
		$wikiDbr = wfGetDB( DB_MASTER );

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
				$insertRows = array();
				foreach($tableRows as &$tableRow)
				{
					$tableRow['_page_id']=$pageId;
					$rowCount++;
					$insertRow = array();
					foreach($tableRow as $k => $v)
					{
						$insertRow[$this->escapeName($k)]=$v;
					}
					$insertRows[] = $insertRow;
				}
				#insert data rows for each table
				try {
					$dbr->insert($this->getDataTableName($tableName),$insertRows);
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

    	$dbr = $this->getDbr();
    	if (!$dbr) return true; 
		$wikiDbr = wfGetDB( DB_MASTER );

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

    	$dbr = $this->getDbr();
    	if (!$dbr) return true; 
		$wikiDbr = wfGetDB( DB_MASTER );

		
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
    	$dbr = $this->getDbr(); if (!$dbr) return true; 
		
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

  	$dbr = $this->getDbr();
  	if (!$dbr) return true; 
		$wikiDbr = wfGetDB( DB_MASTER );

   	if ($newTitle->getNamespace()==NS_XSSDATA) return false; # TODO: delete row data if page moved to Data: namespace

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
					 . '_page_title =' . $this->escapeValue($newTitle->getPrefixedDbKey()) 
					 . ') WHERE _page_id='.$pageId;
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
		$this->mSaveArticleIdForDelete=$article->getID(); 	# This is too messay. TODO: Fix.
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

    	$this->deleteRowData($title,$pageId);
    	$this->deleteMultiData($title,$pageId);

    	$this->saveTableData($linksUpdate->mParserOutput,$title,$pageId);
    	$this->saveRowData($linksUpdate->mParserOutput,$title,$pageId);
    	$this->saveMultiData($linksUpdate->mParserOutput,$title,$pageId);
		return true;
	}

	
	function hook_TitleMoveComplete(&$oldTitle,&$newTitle)
	{
		$article=new Article($newTitle);
		$pageId=$article->getID();

		$this->updateTableDataOnPageMove($oldTitle,$newTitle,$pageId);
		$this->updateRowDataOnPageMove($oldTitle,$newTitle,$pageId);
		return true;
	}
	
}


