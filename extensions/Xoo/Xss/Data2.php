<?php
class DataDriver {
	var $tables = array();
	function getTable( $table ) {
		#only read from the database 
		if ( $this->tables[ $table ] ) return $this->tables[ $table ];
		$fields = $this->_selectFields( $table );
		$props = $this->_selectProps( $table );
		$this->tables[ $table ] = new DataTable( $this, $table , $fields , $props );
		return $this->tables[ $table ];
	};
	function addTable( $table , $new ) {
		$this->_dropTable( $table ); // just to make sure
		$this->_dropFields( $table ); // just to make sure
		$this->_dropProps( $table ); // just to make sure

		$this->_createTable( $table , $new );
		foreach ( $new->fields as $k=>$f ) {
			$this->_createColumn( $table , $k , $f );
		}
		$this->_insertFields ( table , $props );
		$this->_insertProps( $table , $props );
	};
	function changeTable( $table , $new ) {
		$old = $this->get( $table );
		foreach ( $new->fields as $k=>$f ) {
			if ( !$old->fields[ $k ] ) {
				$this->_createColumn( $table , $k , $f );
			} elseif ( $old->fields[ $k ] != $new->fields[ $k ] ) {
				$this->_alterColumn( $table , $k , $f );
			}
		}
		foreach ( $old->fields as $k=>$f ) {
			if ( !$new->fields[ $k ] ) {
				$this->_dropColumn( $table , $k );
			} 
		}
		$this->_dropFields( $table );
		$this->_insertFields ( $table , $fields );
		$this->_dropProps( $table ); 
		$this->_insertProps( $table , $props );
	}
	function deleteTable( $table ) {
		$this->_dropFields( $table );
		$this->_dropProps( $table ); 
		$this->_dropTable( $table );
	}
	function _select
}
class DataTable {
	var $driver;
	var $name;
	var $fields;
	var $props;
	function __construct($driver,$name);
	function addField($field,$opt) {
		if isset($this->fields[$field]) throw("duplicate field {$this->name}.$field");
		$type = $opt['type'] ? $opt['type'] : 'text';
		$f = new DataField($this,$field)
		if (substr($type,0,2)=='##') {
			$f->type  = 'multi';
			$f->ref   = $this->normalize(substr($type,2));
			$f->multi = $this->driver->multiTable($this->name,$f->name,$f->ref);
			$f->rev  = $this->normalize($opt['rev']);
		} elseif (substr($type,0,1)=='#') {
			$f->type  = 'reference';
			$f->ref   = $this->normalize(substr($type,1));
			$f->rev  = $this->normalize($opt['rev']);
		} else {
			$f->type = $this->getType($type);
		}
		$this->fields[$name]=$f;
	}
	function addProp();
	function hasField();
	function hasProp();
}
class DataField {
	var $table;
	var $name;
	var $type;
	var $def;
	var $ref;
	var $def;
	var $multi;
	
	function getDbType() {
		$this->table->driver->dbtype($this->type);
	}
	function db2wiki($val) {
		$this->dbValue = $val;
		$this->wikiValue = $this->table->driver->db2wiki($this->type,$val);
	};
	function wiki2db($val) {
		$this->dbValue = $val;
		$this->wikiValue = $this->table->driver->wiki2db($this->type,$val);
	};
}

function datapage_field(&$P,$F,$A,$T) {
	$opt = $this->extractArgs($A);
	$name = $opt[1]; 
	if (!$name) throw("Missing field name");
	$f = $P->mOutput->xssTable->addField($name,$opt);
	return $f->describe();
}

function datapage_prop(&$P,$F,$A,$T) {
	$props = array();
	for ($i = 1; $i<$A->count; $i++) {
		$name = $A->getName($i);
		$value = $A->getValue($i)->getDOM();
		if ($P->mOutput->xssTable->hasProp($name)) return $this->error("Duplicate prop name", $name);
		$props[$name] = $value;
		$P->mOutput->xssTable->addProp($name,$value);
	}
	return $this->dumpArray($props);
}
function datapage_table(&$P,$F,$A,$T) {
	$i = 1;
	$ret=$P->mOutput->XssTable->dumpHeaderRow();
	while($A->exists($i)) {
		if ($A->isNumbered($i)) return $this->error("Missing field name");
		$name = $A->getName($i);
		$opt=array();
		$opt['type'] = $A->trimExpandValue($i++);
		if ($A->isNumbered($i) || !$A->getName($i)) {
			$opt['def']=$A->cropExpandValue($i++);
		} else continue;
		if ($A->isNumbered($i))
			$opt['rev']=$a->cropExpandValue($i++);
		};
		$f = $P->mOutput->XssTable->addField($name,$opt);
		$ret.=$f->dumpRow();
	}
	return $this->html('<table class="xss-table">'.$ret.'</table>');
}

function data_set(&$P,$F,$A,$T) {
	$warn = '';
	$i = 1;
	$opt = $this->extractArgs($A);
	
	if (!$A->isNumbered(1) return $this->error('missing field name');
	$table = $A->trimExpand($i++);
	$t = $this->driver->getTable($table);
	if (!$t) return "<nowiki>{{</nowiki>[[data:$table]]<nowiki>}}</nowiki>";
	
	if ($A->isNumbered($i)) {
		$id = $A->trimExpand($i++);
	} else $id = $this->randomString();
	for (;$i<$A->count;$i++) {
		if ($A->isNumbered($i) return $this->error('missing field name');
		$field = $A->getName($i);
		$value = $A->trimExpandValue($i);
		if (!$t->hasField($field)) {
			$warn.=$this->warn("no such field",$field); 
		} else {
			$f = $t->getField($field);
			if ($f->type=='multi') {
				if (isset($P->mOutput->xssRows[$f->multi][$id][$field])) {
					$warn.=$this->warn("duplicate ref",$field);
				} else {
					$P->mOutput->xssRows[$f->multi][$id]=array(
						'from'=>$id,
						'to'=>$value
					);
				}
			} else {
				if (isset($P->mOutput->xssRows[$table][$id][$field])) {
					$warn.=$this->warn("duplicate field name",$field);
				} else {
					$P->mOutput->xssRows[$table][$id][$field]=$f->dbValue();
				}
			}
		}
	}
}
function hook_beforeParse(&$P) {
	if($P->mTitle->getNamespace()!=NS_DATA) return true; 
	$P->mOutput->xssTable = new DataTable($P->mTitle->getDbKey());
	return true;
}

function hook_afterSave(&$P) {
	if($P->mTitle->getNamespace()==NS_DATA) { 
		$table = $P->mTitle->getDbKey();
		$this->driver->saveTable($table,$P->mOutput->xssTable);
	}
	return true;
}
function hool_linkUpdate($T,...,&$O) {
	$this->driver->deleteRows($T->getFullDBKey());
	$this->driver->insertRows($T->getFullDBKey(),$O->xssRows);
}
function hook_afterDelete(&$T) {
	$table = $T->getDBKey();
	$this->driver->deleteTable($table);
	return true;
}
