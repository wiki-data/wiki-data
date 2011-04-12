<?php
#
#	Preliminaries, look lower for interesting stuff
#
if ( !defined( 'MEDIAWIKI' ) ) { die( 'This file is a MediaWiki extension, it is not a valid entry point' );}
XxxInstaller::Install('Xpp');
#
#
#
#########################################################################

class Xpp extends Xxx
{
	function doParse($T,$F,$debug=false) {
		$res = XppParser::Parse($T,$F);
		if ($debug) {
			if(!$res) return '<b>not found</b>';
			$ret = "<small><pre>" . $res->dump() . "</pre></small>";
		} else $ret = "";
		$ret.=(string)$res;
		return $ret;
	}
	function tag_xpp($T,$A,&$P,&$F) {
		$res=$this->doParse($T,$F,$A['debug']);
		$ret = $P->recursiveTagParse((string)$res,$F);
		return $ret;
	}
	
	function tag_pass($T,$A,$P,$F) {
		$ret = $P->recursiveTagParse((string)$T,$F);
		return $ret;
	}
	function flx_x(&$P,&$F,&$A) {
		$args = new XxxArgs($F,$A);
		$p=array($args->command);
		for ($i=1; $i<=$args->count;$i++) {
			$p[]=$args->expand($i);
		}
		$T = implode('|',$p);
		$res=$this->doParse($T,$F,$true);
		return array($res, 'noparse'=>false);
	}
	function fl_xpp(&$P,&$F,&$A) 
	{
		$args = new XxxArgs($F,$A);
		switch($args->command)
		{
		case 'temparg':
			$id=$args->trimExpand(1);
			if ($F->parent) return (string)$F->parent->xppTempArgs[$id];
			else return $this->notFound();
			break;
		case 'debug':
			$debug=true;
		case 'expr':
			$p=array();
			for ($i=1; $i<=$args->count;$i++) {
				$p[]=$args->expand($i);
			}
			$T = implode('|',$p);
			$ret=$this->doParse($T,$F,$debug);
			return array($ret, 'noparse'=>$debug);
		
		default:
			return $this->notFound();
		}
	}
}
class XppVal {
	var $val;
	function __construct($val) { $this->val = (string)$val; }
	function __toString() { return $this->val; }
}
class XppParser {
	var $text;
	var $whiteSpace='ignore';
	var $main='else';
	var $pos=0;
	var $frame=null;

	static function Parse($text,$F) {
		$p = new XppParser();
		$main = $p->main;
		$p->text=$text;
		$p->frame=$F;
		$p->empty = new XppVal('');
	 	$p->zero = new XppVal(0);
		try {
			$t = $p->$main();
			if($t->pos<strlen($t->text)) throw new Exception("syntax error");
			return $t;
		} catch (Exception $e) {
			return $e->getMessage() ." at ".substr($p->text,$p->pos);
		}
	}	
		
	function _consumeWS() {
		if (preg_match('/\\G\\s+/',$this->text,$m,0,$this->pos)) {
			$this->pos+=strlen($m[0]);
		}
	}
	function _consume($s,$ws=null) {
		if($ws===null) $ws=$this->whiteSpace;
		if ($ws=='ignore') $this->_consumeWS();
		if (substr($this->text,$this->pos,strlen($s))!==$s) return false;
		$this->pos+=strlen($s); 
		return $s;
	}
	
	function _match($re,$which=null,$ws=null) {
		if($ws===null) $ws=$this->whiteSpace;
		if ($ws=='ignore') $this->_consumeWS();
		if (preg_match('/\\G'.$re.'/',$this->text,$m,0,$this->pos)) {
			$this->pos+=strlen($m[0]);
			$ret = $which === null ? $m : $m[$which];
			return $ret;
		} else return false;
	}
	function __call($method,$args) {
		$method="_" . mb_strtoupper($method);
		$pos = $this->pos;
		if (!method_exists($this,$method)) die("No method __CLASS__::$method");
		$res = $this->{$method}();
		if($res===false) {
			$this->pos=$pos;
			return false;
		} else {
			if(!is_array($res)) {
				$res=array($res);
			}
			if ($res['error']) {
				throw new Exception($res['error']);
			}
			return $res[0];
		}
	}

	function _NUMBER() {
		$ret =  $this->_match('[0-9]+(\\.[0-9]+)?',0);
		if ($ret==='0') return $this->zero;
		return $ret;
	}
	function _IDENT() {
		$ret= $this->_match('#?[\\p{L}\\p{N}:]+',0);
		return $ret;
	}
	function _QUOTE() {
		$q = $this->_consume("'") or $q = $this->_consume('"');
		if (!$q) return false;
		$str='';
		$esc=false;
		while ($this->pos<strlen($this->text)) {
			$next=$this->_match('[\s\S]',0,'noignore');
			if ($next==$q &! $esc) {
				return $str ? $str : $this->empty;
			}
			if($esc) {
				if ($next=='n') $str.="\n";
				else $str .= $next;
				$esc = false;
			} elseif ($next=='\\') {
				$esc = true;
			} else {
				$str .= $next;
			}
		} 
		return array('error'=>'unterminated string');
	}
	function _LITERAL () {
		$lit = $this->quote() or $lit=$this->number() or $lit=$this->ident();
		if(!$lit) return false;
		else return $lit; #new XppNode($this->frame,array('literal',$lit));
	}
	function _ATOM() {
		$ret = $this->literal() 
		or $ret = $this->expression()
		or $ret = $this->var();
		return $ret;
	}
	
	function _VAR() {
		if(!$this->_consume('$')) return false;
		if(!$val=$this->atom()) return false;
		return new XppNode($this->frame,'var',array($val));
	}
	
	function _VAL() {
		$name = $this->atom();
		if (!$name) return false;
		#TODO
		$ret = $name;
		while($args=$this->args()) {
			$ARGS=$args;
			$ARGS[0]=$ret;
			$ret=new XppNode($this->frame,'template',$ARGS);
		};
		return $ret;
	}
	
	function _ARGS() {
		if(!$this->_consume('[')) return false;
		$count=0;
		$ret=array();
		while (	$left = $this->val() ) {
			if ($this->_consume('=') and $right= $this->val()) {
				$ret[$left]=$right;
			} else {
				$count++;
				$ret[$count]=$left;
			}
			$this->_consume(',');
		}
		if(!$this->_consume(']')) return array('error'=> "expecting ]");
		return array(0=>$ret);
	}
	
	function _EXPRESSION () {
		if(!$this->_consume('(')) return false;
		$ret=$this->else();
		if(!$this->_consume(')')) return array('error'=>'expecting )');
		return $ret;	
	}
	
	function _ELSE() {
		$first=$this->if();
		$args=array($first);
		$found = false;
		while($op = $this->_match('(\\|)',0) and $t=$this->if()) {
			$args[] = $t;
			$found = true;
		}
		return $found ? new XppNode($this->frame,'else',$args) : $first;
	}
	function _IF() {
		$first=$this->concat();
		$args=array($first);
		$found = false;
		while($op = $this->_match('(\\?)',0) and $t=$this->concat()) {
			$args[] = $t;
			$found = true;
		}
		return $found ? new XppNode($this->frame,'if',$args) : $first;
	}
	
	function _CONCAT() {
		$first=$this->chunk();
		$args=array($first);
		$current = is_scalar($first) ? $first : false;;
		while($op = ($this->_consume('&') ?'&' : '_') and $t=$this->chunk()) {
			$args[] = $op;
			$args[] = $t;
			$found = true;
		}
		$i=0;
		$outArgs=array();
		while($i<count($args)) {
			print "---- $i<br>";
			if (is_scalar($args[$i])) {
				$ret=$args[$i];
				$i++;
				while ($i<count($args)-1 && is_scalar($args[$i+1])) {
					$ret.= $args[$i]=='_'?' ':'';
					$ret.= $args[$i+1];
					$i+=2;
				} 
				$outArgs[]=$ret;
			} else {
				$outArgs[]=$args[$i];
				$outArgs[]=$args[$i+1];
				$i+=2;
			}
		}
		return $found ? new XppNode($this->frame,'concat',$outArgs) : $first;
	}

	function _CHUNK() {
		$chunk = $this->assign() or $chunk = $this->compare();
		return $chunk;
	}

	function _ASSIGN() {
		$this->_consume('$') and $left=$this->atom() 
		and ($op = $this->_consume('='))  and $right=$this->addsub() 
		and $found=true;
		if(!$found) return false;
		return new XppNode($this->frame,'assign',array($left,$right));
	}
	function _COMPARE() {
		$first=$this->addsub();
		$args=array($first);
		$found = false;
		while($op = $this->_match('(\\<|\\>|\\=\\=|\\>\\=|\\<\\=|\\!\\=|~)',0) and $t=$this->addsub()) {
			$args[]= $op;
			$args[]= $t;
			$found = true;
		}
		return $found ? new XppNode($this->frame,'compare',$args) : $first;
	}
	
	function _ADDSUB() {
		$first=$this->muldiv();
		$args=array($first);
		$found = false;
		while($op = $this->_match('(\\+|-)',0) and $t=$this->muldiv()) {
			$args[]= $op;
			$args[]= $t;
			$found = true;
		}
		return $found ? new XppNode($this->frame,'addsub',$args) : $first;
	}
	function _MULDIV() {
		$first=$this->concat2();
		$args=array($first);
		$found = false;
		while($op = $this->_match('(\\*|\\/)',0) and $t=$this->concat2()) {
			$args[]= $op;
			$args[]= $t;
			$found = true;
		}
		return $found ? new XppNode($this->frame,'muldiv',$args) : $first;
	}
	
	function _CONCAT2() {
		$first=$this->val();
		$args=array($first);
		$found = false;
		while($op = $this->_match('(\\_)',0) and $t=$this->val()) {
			$args[]= $op;
			$args[]= $t;
			$found = true;
		}
		return $found ? new XppNode($this->frame,'concat',$args) : $first;
	}
}

class XppNode{
	var $args=array();
	var $frame=null;
	var $type='';
	function __construct($frame,$type,$args) {
		$this->frame=$frame;
		$this->args=$args;
		$this->type=$type;
	}
	function dump($n=0) {
		$sp = " ";
		$spp = str_repeat($sp,$n);
		$ret="[{$this->type}]";
		foreach($this->args as $k=>$v) {
			$ret.="\n".$spp.$sp;
			if(true or $this->type == 'template') {
				$ret.="$k = ";
			}
			if (!$v instanceof XppNode) $ret.=$v;
			else $ret.=$v->dump($n+1);
		}
		return $ret;
	}
	function __toString() {
		$fail = '<i>f</i>';
		$true = '<b>t</b>';
		$args =& $this->args;
		switch($this->type)
		{
		case 'literal':
			return $this->args[0];
		case 'else':
			for($i=0;$i<count($args);$i++) {
				$ret=(string)$args[$i];
				if($ret!==$fail) return $ret;
			}
			return $fail;
		case 'if':
			$ret=$fail;
			for($i=0;$i<count($args);$i++) {
				$ret=(string)$args[$i];
				if($ret===$fail) return $fail;
			}
			return $ret;
		case 'concat':
			$res = (string)$args[0];
			if ($res==$true) $res="";
			elseif ($res===$fail) return $fail;
			for($i=1;$i<count($args);$i+=2) {
				$op=trim($args[$i]);
				$val=(string)$args[$i+1];
				if($val===$fail) return $fail;
				elseif($val===$true) continue;
				elseif($op=='_') $res.=" ".$val;
				elseif($op=='&') $res.=$val;
			}
			return $res;
		case 'addsub':
			$res = (string)$args[0];
			if ($res==$true) $res=0;
			elseif ($res===$fail) return $fail;
			else $res=(float)$res;
			for($i=1;$i<count($args);$i+=2) {
				$op=trim($args[$i]);
				$val=(string)$args[$i+1];
				if($val===$fail) return $fail;
				elseif(!is_numeric($val)) continue;
				elseif($op=='+') $res+=(float)$val;
				elseif($op=='-') $res-=(float)$val;
			}
			return (string)$res;
		case 'muldiv':
			$res = (string)$args[0];
			if ($res===$true) $res=1;
			elseif ($res===$fail) return $fail;
			else $res=(float)$res;
			for($i=1;$i<count($args);$i+=2) {
				$op=trim($args[$i]);
				$val=(string)$args[$i+1];
				if($val===$fail) return $fail;
				elseif(!is_numeric($val)) continue;
				elseif($op=='*') $res*=(float)$val;
				elseif($op=='/') {
					if(!(float)$val) return $fail;
					else $res/=(float)$val;
				};
			}
			return (string)$res;
		case 'compare':

			$cur = (string)$args[0];
			if($cur===$fail) return $fail;
			for($i=1;$i<count($args);$i+=2) {
				$op=trim($args[$i]);
				$val=(string)$args[$i+1];
				if($val===$fail) return $fail;
				switch($op) {
				case '==':
					if($cur!==$val) return $fail;
					break;
				case '!=':
					if($cur===$val) return $fail;
					break;
				case '<':
					if ($cur>=$val) return $fail;
					break;
				case '>':
					if ($cur<=$val) return $fail;
					break;
				case '>=':
					if ($cur<$val) return $fail;
					break;
				case '<=':
					if ($cur>$val) return $fail;
					break;
				case '~':
					if (!preg_match('/'.$val.'/',$cur)) return $fail;
					$val=$cur;
					break;
				default:
					die("unknown operator '$op' at $i in __METHOD__");
				}
				$cur=$val;
			}
			return $true;
		case 'assign':
			$name=(string)$args[0];
			$val=(string)$args[1];
			$this->frame->xppVars[$name]=$val;
			return $true;
		case 'var':
			$name=(string)$args[0];
			if($name===$fail) return $fail;
			if(isset($this->frame->xppVars[$name])) return $this->frame->xppVars[$name];
			if($this->frame->parent) {
				$val = $this->frame->expand($this->frame->getArgument($name));
				if($val===false) return $fail;
			} else {
				return $fail;
			}
			$this->frame->xppVars[$name]=$val;
			return $val;
		case 'template':
			$name=$args[0];
			unset($args[0]);
			$ret="{{{$name}";
			$colon = mb_substr($name,-1)==':';
			$first=true;
			foreach($args as $k=>$v) {
				if(!$colon || !$first) {
					$ret.='|';
				} 
				$first = false;
				if (is_scalar($v)) {
					$ret.="$k=$v";
				} else {
					$id = Parser::getRandomString();
					$this->frame->xppTempArgs[$id]=$v;				
					$ret .= "$k={{#xpp:temparg|$id}}";
				}
			}
			$ret.='}}';
			return $ret;
		default:
			return $fail;
		}
	}
}

?>
