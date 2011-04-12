<?php

class XssQuery
{
	var	$mXss;		# who called us?
	var	$mDbr;
	var   $mFroms;
	var	$mWhere;
	var	$mGroupBy;
	var	$mOrderBy;
	var	$mLimit;
	var	$mOffset;
	var $mDefaultTable;
	
	var		$mType;
	var 	$mTableNames		= array();	# tables for the SQL query
	var 	$mTableAliases	= array();
	var 	$mFieldNames		= array();	# fields for the SQL query
	var 	$mFieldAliases	= array();
		
	var 	$mSQL;		
	var	$mErr;
	
	function __construct(&$xss, $type, $fields, $options)
	{
		if (!$xss ) 
		{
			$this->mError="no Xoo Simple Schemas object passed to the constructor";
		}
		$this->mXss=&$xss;
		$this->mDbr=&$xss->getDbr();
		if (!$this->mDbr) 
		{
			$this->mError="no database connection";
			die;
		}
		$this->mFields 		= $fields;
		$this->mWhere 		= $options['where'] ? trim($options['where']) : '';
		$this->mGroupBy 	= $options['groupby'] ? trim($options['groupby']) : '';
		$this->mOrderBy 	= $options['orderby'] ?  trim($options['orderby']) : '';
		$this->mHaving 		= $options['having'] ? trim($options['having']) : '';
		$this->mSort  		= $options['sort'] ? trim($options['sort']) : '';
		$this->mLimit 		= $options['limit'] ? (int) $options['limit'] : 20;
		$this->mOffset 	= $options['offset'] ? (int) $options['offset'] : 0;
        $this->mDefaultTable = $options['from'] ? trim($options['from']) : '';
		$this->mFroms   = $options['fromtables'] ? array(trim($options['fromtables'])) : array();
		$this->mType=$type;
		$this->processQuery();
	}
	
	static function Make ($xss, $fields, $options)
	{
		$xssQuery = new XssQuery ($xss, 'SELECT', $fields, $options);
		return $xssQuery;
	}

	static function MakeSelect ($xss, $fields, $options)
	{
		$xssQuery = new XssQuery ($xss, 'SELECT', $fields, $options);
		return $xssQuery;
	}


	static function MakeUpdate ($xss, $fields, $options)
	{
		$xssQuery = new XssQuery ($xss, 'UPDATE', $fields, $options);
		return $xssQuery;
	}

	static function MakeDelete ($xss, $fields, $options)
	{
		$xssQuery = new XssQuery ($xss, 'DELETE', $fields, $options);
		return $xssQuery;
	}

	function getSql()
	{
		return $this->mSql;
	}

	function getError()
	{
		return $this->mError;
	}
	
	function processQuery()
	{
		switch ($this->mType)
		{
		default:
		case 'SELECT':
			$fields=array();
			foreach($this->mFields as $k=>$v)
			{
				$v=trim($v);
				$check = preg_replace('/^(([\p{L}_#][\p{L}\p{N}_#]*)?(\.([\p{L}\p{N}#_]+))*)\.\*$/i','$1',$v);
				if ($check!=$v)
				{
					if (is_numeric($k)) $fieldPrefix=$check;
					else $fieldPrefix=$k;
					if ($fieldPrefix!='') $fieldPrefix.='.';
					
					$this->extractTableName($v,$tableName,$tableAlias);
					$fieldDefs = $this->mXss->getTableDef($tableName);
					$fieldNames = $fieldDefs['fieldNames'];
					$fields = array (
						"$tableAlias._row_ref AS `{$fieldPrefix}#`",
						"$tableAlias._row_ref AS `{$fieldPrefix}#ref`",
						"$tableAlias._row_name AS `{$fieldPrefix}#name`",
						"$tableAlias._page_title AS `{$fieldPrefix}#page`"
					);
					foreach ($fieldNames as $f)
					{
					   if ($fieldDefs['fieldsByName'][$f]['field_type']=='multi') continue;
						$fields[]="($tableAlias." . $this->escapeName($f) . ") AS " . $this->escapeName("$fieldPrefix$f");
					}
				}
				else
				{
					$expression= $this->processText($v,'makeFieldForSelect');
					if($v==='') break;
					$fieldName= is_numeric ($k) ? $this->escapeName($v) :	$fieldName=$this->escapeName($k);
					$fields[]="($expression) AS $fieldName";
				}
			}
			$DISTINCT= "DISTINCT";	
			$SELECT  = "SELECT $DISTINCT\n\t".join(",\n\t", $fields);
			$WHERE   = $this->mWhere   ? "\nWHERE " . $this->processText($this->mWhere,'makeFieldAlias')   :'';
			$GROUPBY = $this->mGroupBy ? "\nGROUP BY " . $this->processText($this->mGroupBy, 'makeFieldAlias') :'';
			$ORDERBY = $this->mOrderBy ? "\nORDER BY " . $this->processText($this->mOrderBy, 'makeFieldAlias') :'';
			$HAVING  = $this->mHaving ? "\nHAVING " . $this->processText($this->mHaving, 'makeFieldAlias') :'';
			$LIMIT	 = "\nLIMIT  {$this->mLimit}";		
			$OFFSET	 = "\nOFFSET {$this->mOffset}";		
			$FROM = count($this->mFroms) ? "\nFROM ".join (', ', $this->mFroms) : '';
			$sql	="$SELECT $FROM $WHERE $GROUPBY $HAVING $ORDERBY $LIMIT $OFFSET";
			if ($this->mSort) $sql = "SELECT * FROM ($sql) AS ____xss_sort ORDER BY ". $this->processText($this->mSort, 'makeFieldAlias')."";
			$this->mSql = $sql;
			return;
			return;
		case 'UPDATE':
			$fields=array();
			foreach($this->mFields as $k=>$v)
			{
				$fieldName= $this->processText($k,'makeFieldAlias');
				$expression= $this->processText($v,'makeFieldAlias');
				if($v==='') break;
				$fields[]="$fieldName = ($expression)";
			}
			$UPDATE = "UPDATE LOW_PRIORITY IGNORE";
			$SET = "\nSET " . join (', ', $fields);
			$WHERE   = $this->mWhere   ? "\nWHERE " . $this->processText($this->mWhere,'makeFieldAlias')   :'';
			$TABLES = join (', ', $this->mFroms);
			$this->mSql = "$UPDATE $TABLES $SET $WHERE";
			return;
		case 'DELETE':
			$fields=array();
			foreach($this->mFields as $k=>$v)
			{
				$fieldName= $this->makeFieldAlias(array($v.'.*'));
				if($v==='') break;
				$fields[]="$fieldName";
			}
			$DELETE = "DELETE LOW_PRIORITY IGNORE";
			$DELETES = "\n" . join (', ', $fields);
			$WHERE   = $this->mWhere   ? "\nWHERE " . $this->processText($this->mWhere,'makeFieldAlias')   :'';
			$FROM = "\nFROM " .join (', ', $this->mFroms);
			$this->mSql = "$DELETE $DELETES $FROM $WHERE";
			return;

		default:
			return 'FOOBAR';
		}
	}

	function processText($text,$method)
	{
		$chunkCounter=100;	# up to 100 chunks allowed #TODO move to settings
		
	    while ($text and $chunkCounter-- >0)
	    {	

            if (preg_match('/^(\'[^\'\\\\]*(\\\\.[^\'\\\\]*)*\')(.*)$/m',$text,$m))								# a quoted chunk, leave alone
            {
            	$outputText.=$m[1];
	            $text = $m[3];
            }
            elseif (preg_match('/^([^\']+)(.*)$/m',$text,$m))								# an unqouted chunk, record and continue
            {
					$outputText.=preg_replace_callback                                      # find candidates for replacement
					(
							'/([\p{L}_#][\p{L}\p{N}_#]*)?(\.([\p{L}_#][\p{L}\p{N}_#]*))+/i',
						array ($this,$method),
						$m[1]
					);
					$text = $m[2];
					if ($this->mError) return false;
				}
            elseif (preg_match('/^\'[^\']*$/',$text,$m))									# houston, we have an 'unclosed string
            {
            	$this->mError='Unclosed quote';
            	return false;
            }
            else																			
            {
                die ("cosmic death ray");															# houston, we have bigger problems than unclosed strings
            }
	    }
	    return $outputText;
	}
	
	function makeFieldAlias($m)
	{
		if ($this->mError) return $m[0];
		$this->makeField($m[0], $fName, $fAlias, $tAlias);
		if ($this->mError) return $m[0];
		return $this->escapeName($tAlias) . "." . $this->escapeName($fName);;
	}

	function makeFieldForSelect($m)
	{
		if ($this->mError) return $m[0];
		$this->makeField($m[0], $fName, $fAlias, $tAlias);
		if ($this->mError) return $m[0];
		return $this->escapeName($tAlias) . "." . $this->escapeName($fName);# . " AS " . $this->escapeName($fAlias);
	}


	function makeField($ident, &$fieldName, &$fieldAlias, &$tableAlias)
	{
		$cParts = explode (".",$ident);
		if (count($cParts)<2) die("cosmic ray on $ident"); 
		$last=array_pop ($cParts);
		if(!$this->extractTableName($ident,$tableName,$tableAlias,$fieldName)) {
			$fieldSuggestion=$this->mXss->suggestField($tableName,$fieldName);
			$this->mError="__LINE__ No such reference or multi field <tt>$fieldName</tt> in <tt>[[Data:$tableName]]</tt>."
						. " Did you mean <tt>$fieldSuggestion</tt>?";
			return false;
		};
		#check if the field is a reference or a multi field, and force an extra .#
		#this is stupid, and still doesn't solve the problem of nonexistent related records not being returned in queries
		#TODO make this saner
		 
		
		switch ($fieldName)
		{	
		case '_multi_from':
		case '_multi_to':
			$specialField=true; break;
		case '*'	: $fieldName= '*'; $specialField=true;	break;
		case '#'	:
		case '#ref' : $fieldName = '_row_ref'; 	$specialField=true;	break;
		case '#page': $fieldName = '_page_title'; $specialField=true;	break;
		case '#name': $fieldName = '_row_name'; 	$specialField=true;	break;
		default		: 
			$fieldName = $last;
			if (!$specialField && !$this->mXss->normalizeName($fieldName))
			{
				$this->mError="Bad field name \"$last\" ($ident)";
				return false;
			}
		}
		$fieldAlias = "_{$tableAlias}__{$fieldName}";

		if($specialField) return true;
		#TODO handle multi relationships
		return $this->checkForField($tableName,$fieldName); 	
		#this will set mError if field not found, and the calling functions will fail
	}

	function extractTableName($ident,&$tableName,&$tableAlias,&$fieldName=null)
	{
		if ($ident{0}=='.') $ident = $this->mDefaultTable . $ident;
		$cParts = explode (".",$ident);
		$last = array_pop($cParts);
		$rootTable = array_shift ($cParts);	
		$cIdent = $rootTable;
		
		if (!$this->splitTableName($cIdent,$tableName,$cAlias)) return false;

		if (!isset($this->mTableNames[$cIdent]))
		{
			$this->mTableNames[$cIdent]=$tableName;
			
			$this->mFroms[$rootTable].=$this->mXss->escapeDataTableName($tableName)." AS ".$this->escapeName("__$cAlias");
		}

		foreach($cParts as $cP)
		{
			if (!$this->splitTableName($cP,$cPart,$cPartAlias)) return false;
		
			$cIdent="$cIdent.$cPart";
			
			if (isset($this->mTableNames[$cIdent]))
			{
				$tableName=$this->mTableNames[$cIdent];
			}
			elseif($subTableName=$this->getReferencedTable($tableName,$cPart)) {
				$this->mTableNames[$cIdent]=$subTableName;
				$this->mFroms[$rootTable]	.= "\nLEFT JOIN " . $this->mXss->escapeDataTableName($subTableName) 
											. " AS "
											. $this->escapeName("__{$cAlias}__{$cPartAlias}")
											. " ON ("
											. $this->escapeName("__{$cAlias}") . "." . $this->escapeName("{$cPart}")
											. " = "
											. $this->escapeName("__{$cAlias}__{$cPartAlias}")."._row_ref"
											. ")";
				$tableName = $subTableName;
			} elseif($subTableName=$this->getReverseReferencedTable($tableName,$cPart,&$fieldName)) {
				$this->mTableNames[$cIdent]=$subTableName;
				$this->mFroms[$rootTable]	.= "\nLEFT JOIN " . $this->mXss->escapeDataTableName($subTableName) 
											. " AS "
											. $this->escapeName("__{$cAlias}__{$cPartAlias}")
											. " ON ("
											. $this->escapeName("__{$cAlias}") . "._row_ref"
											. " = "
											. $this->escapeName("__{$cAlias}__{$cPartAlias}")."." . $this->escapeName($fieldName) 
											. ")";
				$tableName = $subTableName;
				$this->mError=null;
			} elseif($subTableName=$this->getMultiTable($tableName,$cPart)) {
				if (!$this->mTableNames[$cIdent.'__x'])
				{
					$multiTableName = $this->mXss->getMultiTableName($tableName,$cPart);
					$this->mTableNames[$cIdent.'__x']=$multiTableName;
					$this->mFroms[$rootTable]	.= "\nLEFT JOIN " . $this->mXss->escapeMultiTableName($tableName,$cPart) 
												. " AS "
												. $this->escapeName("__{$cAlias}__x__{$cPartAlias}")
												. " ON ("
												. $this->escapeName("__{$cAlias}") . "._row_ref"
												. " = "
												. $this->escapeName("__{$cAlias}__x__{$cPartAlias}")."._multi_from"
												. ")";
					$this->mTableNames[$cIdent.'__x'] = $multiTableName;
				}
				$this->mTableNames[$cIdent]=$subTableName;
				$this->mFroms[$rootTable]	.= "\nLEFT JOIN " . $this->mXss->escapeDataTableName($subTableName) 
											. " AS "
											. $this->escapeName("__{$cAlias}__{$cPartAlias}")
											. " ON ("
											. $this->escapeName("__{$cAlias}__x__{$cPartAlias}")."._multi_to"
											. " = "
											. $this->escapeName("__{$cAlias}__{$cPartAlias}")."._row_ref"
											. ")";
				$tableName = $subTableName;
				$this->mError=null;
			} elseif($subTableName=$this->getReverseMultiTable($tableName,$cPart,&$fieldName)) {
			
				if (!$this->mTableNames[$cIdent.'__x'])
				{
					$multiTableName = $this->mXss->getMultiTableName($subTableName,$fieldName);
					$this->mFroms[$rootTable]	.= "\nLEFT JOIN " . $this->mXss->escapeMultiTableName($subTableName,$fieldName)
												. " AS "
												. $this->escapeName("__{$cAlias}__x__{$cPartAlias}")
												. " ON ("
												. $this->escapeName("__{$cAlias}") . "._row_ref"
												. " = "
												. $this->escapeName("__{$cAlias}__x__{$cPartAlias}")."._multi_to"
												. ")";
					$this->mTableNames[$cIdent.'__x'] = $multiTableName;
				}
											
				$this->mTableNames[$cIdent]=$subTableName;
				$this->mFroms[$rootTable]	.= "\nINNER JOIN " . $this->mXss->escapeDataTableName($subTableName) 
											. " AS "
											. $this->escapeName("__{$cAlias}__{$cPartAlias}")
											. " ON ("
											. $this->escapeName("__{$cAlias}__x__{$cPartAlias}")."._multi_from"
											. " = "
											. $this->escapeName("__{$cAlias}__{$cPartAlias}")."._row_ref"
											. ")";
				$tableName = $subTableName;
				$this->mError=null;
			} else {
				return false;
			}
			$cAlias="{$cAlias}__{$cPartAlias}";
		}
		if($last{0}=='#') {
			$tableAlias = "__$cAlias";
			$fieldName=$last;
		} elseif($subTableName=$this->getMultiTable($tableName,$last)) {
			if (!$this->splitTableName($last,$cPart,$cPartAlias)) return false;
			$cIdent="$cIdent.{$cPart}__x";
			if (!$this->mTableNames[$cIdent])
			{
				$multiTableName = $this->mXss->getMultiTableName($tableName,$cPart);
				$this->mFroms[$rootTable]	.= "\nLEFT JOIN " . $this->mXss->escapeMultiTableName($tableName,$cPart) 
											. " AS "
											. $this->escapeName("__{$cAlias}__x__{$cPartAlias}")
											. " ON ("
											. $this->escapeName("__{$cAlias}") . "._row_ref"
											. " = "
											. $this->escapeName("__{$cAlias}__x__{$cPartAlias}")."._multi_from"
											. ")";
				$this->mTableNames[$cIdent]=true;
			} else {
				$multiTableName = $this->mTableNames[$cIdent];
			}
			$tableAlias = "__{$cAlias}__x__{$cPartAlias}";
			$tableName=$multiTableName;
			$fieldName="_multi_to";
		} elseif($subTableName=$this->getReverseMultiTable($tableName,$last,&$subFieldName)) {
			if (!$this->splitTableName($last,$cPart,$cPartAlias)) return false;
			$cIdent="$cIdent.{$cPart}__x";
			if (!$this->mTableNames[$cIdent])
			{
				$multiTableName = $this->mXss->getMultiTableName($subTableName,$subFieldName);
				$this->mFroms[$rootTable]	.= "\rINNER JOIN " . $this->mXss->escapeMultiTableName($subTableName,$subFieldName)
											. " AS "
											. $this->escapeName("__{$cAlias}__x__{$cPartAlias}")
											. " ON ("
											. $this->escapeName("__{$cAlias}") . "._row_ref"
											. " = "
											. $this->escapeName("__{$cAlias}__x__{$cPartAlias}")."._multi_to"
											. ")";
				$this->mTableNames[$cIdent]=$multiTableName;
			} else {
				$multiTableName = $this->mTableNames[$cIdent];
			}
			$fieldName="_multi_from";
			$tableAlias = "__{$cAlias}__x__{$cPartAlias}";
			$tableName=$multiTableName;
			$this->mError=null;
		} else { 
			$fieldName = $last;
			$tableAlias = "__$cAlias";
		}	
		return true;
	}

	function splitTableName($text,&$tableName,&$tableAlias)
	{
		$p=explode('#',$text);
		$aliasSuffix='';
		switch(count($p))
		{
		case 2:
			$second=$p[1];
			#if (!$this->mXss->normalizeName($second))
			#{
				#$this->mError="Bad table id \"$second\" ($text)";
				#return false;
			#}
			$aliasSuffix="__{$second}";
		case 1:
			$first=$p[0];
			if (!$this->mXss->normalizeName($first))
			{
				$this->mError="Bad table name \"$first\" ($text)";
				return false;
			}	
			$tableName = $first;
			$tableAlias = "{$first}{$aliasSuffix}";
			return true;
		default:
			$this->mError="Bad table name \"$text\"";
			return false;
		}
	}

	function getReverseReferencedTable($tableName,$reverseName, &$fieldName)
	{
		if (!$res=$this->mDbr->select
		(
			$this->mXss->getInternalTableName('fields'),
			array('field_table','field_name'),
			"field_reference=" . $this->mXss->escapeValue($tableName) . " AND field_reverse=" . $this->mXss->escapeValue($reverseName) . " AND field_type='reference'"
		))
		{
			$this->mError='SQL Error';
			return false;
		}
		if(!$row=$this->mDbr->fetchRow($res))
		{
			return false;
		}
		$fieldName = $row['field_name'];
		return $row['field_table'];	
	}


	function getReferencedTable($tableName,$fieldName)
	{
		if (!$res=$this->mDbr->select (
			$this->mXss->getInternalTableName('fields'),
			'field_reference',
			"field_table=" . $this->mXss->escapeValue($tableName) 
			. " AND field_name=" . $this->mXss->escapeValue($fieldName)
			. " AND field_type='reference'"
		)) {
			$this->mError='SQL Error';
			return false;
		}
		if(!$row=$this->mDbr->fetchRow($res)) {
			return false;
		}
		return $row['field_reference'];
	}

	function getReverseMultiTable($tableName,$reverseName, &$fieldName)
	{
		if (!$res=$this->mDbr->select
		(
			$this->mXss->getInternalTableName('fields'),
			array('field_table','field_name'),
			"field_reference=" . $this->mXss->escapeValue($tableName) . " AND field_reverse=" . $this->mXss->escapeValue($reverseName) . " AND field_type='multi'"
		))
		{
			$this->mError='SQL Error';
			return false;
		}
		if(!$row=$this->mDbr->fetchRow($res))
		{
			return false;
		}
		$fieldName = $row['field_name'];
		return $row['field_table'];	
	}
	
	function getMultiTable($tableName,$fieldName)
	{
		if (!$res=$this->mDbr->select (
			$this->mXss->getInternalTableName('fields'),
			'field_reference',
			"field_table=" . $this->mXss->escapeValue($tableName) 
			. " AND field_name=" . $this->mXss->escapeValue($fieldName)
			. " AND field_type='multi'"
		)) {
			$this->mError='SQL Error';
			return false;
		}
		if(!$row=$this->mDbr->fetchRow($res)) {
			return false;
		}
		return $row['field_reference'];
	}
	
	function checkForField($tableName,$fieldName) {
		if ($fieldName=='*') return true;
		if (!$res=$this->mDbr->select	(
			$this->mXss->getInternalTableName('fields'),
			'*',
			"field_table=" . $this->mXss->escapeValue($tableName) 
			. " AND field_name=" . $this->mXss->escapeValue($fieldName)
			. " AND field_type <> 'multi'"
		)) {
			$this->mError='SQL Error';
			return false;
		}
		if(!$this->mDbr->numRows($res)) {
			$fieldSuggestion=$this->mXss->suggestField($tableName,$fieldName);
			$this->mError="No such field <tt>$fieldName</tt> in <tt>[[Data:$tableName]]</tt>. Did you mean <tt>$fieldSuggestion</tt>?";
			return false;
		}
		return true;		
	}
	
	function escapeName($name)
	{
		switch ($name)
		{	
		case '*'	: 
		case '_row_ref' : 
		case '_row_name': 
		case '_page_title': 
			return $name;
		default:
			return $this->mXss->escapeName($name);
		}
	}
}
/*		
		{{#data:select
		| bill#1.amount
		| amount = bill.seller#1.contactperson
		| 			 company#1, company#2
 		| #where=
 		| #groupby=
 		| #limit=
 		| #orderby=
 		| #offset=
 		}} 				
*/		

