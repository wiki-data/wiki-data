<?php
#
#	Preliminaries, look lower for interesting stuff
#
if ( !defined( 'MEDIAWIKI' ) ) { die( 'This file is a MediaWiki extension, it is not a valid entry point' );}
if (defined('XUU_LOADED')) return; 
define ('XUU_LOADED', true);
XxxInstaller::Install('Xuu');
#
#  Xuu - Xoo Useful Utilities
#
#  Part of Xoo (c) 1997-2008 [[w:en:User:Zocky]], GPL3 applies
#
#########################################################################



class Xuu extends Xxx
{

	function setupExtension()
	{
		wfSetupSession();
		if (!isset($_SESSION['XuuMailTokenKey'])) $_SESSION['XuuMailTokenKey'] = Parser::getRandomString();
		if (!isset($_SESSION['XuuMailTokenVal'])) $_SESSION['XuuMailTokenVal'] = Parser::getRandomString();
	}

	function fn_header(&$parser,$command) {
		header($command);
		return "";
    }
	
	function tag_ref() { return ""; }
 	function fl_str($parser, $f, $a)
	{
		$args=new XxxArgs($f, $a);

		if(!$args->exists(1) && !$args->command=='random' &&!$args->command=='nl') return $this->notFound();
		$str = $args->cropExpand(1);

		switch($args->command)
		{
		case 'nl':
			return array(0=>"\n",'isHTML'=>true);
		case 'random':
			return $parser->getRandomString();
		case 'lc':
		case 'lower':
		case 'tolower':
			return mb_strtolower($str);

		case 'uc':
		case 'upper':
		case 'toupper':
			return mb_strtoupper($str);

		case 'cap':
		case 'capitalize':
		case 'firstcap':
			return mb_strtoupper(mb_substr($str,0,1)).mb_substr($str,1);

		case 'trim':
			return trim($str);

		case 'addslashes':
			return addslashes($str);
		case 'escquotes':
			return preg_replace('/"/','&#34;',$str);
		case 'htmlencode':
			return htmlspecialchars($str);
		case 'urlencode':
			return wfUrlEncode($str);
		case 'urldecode':
			return urldecode($str);
			
		case 'qpencode':
			$maxlen=76;
			$chunks=mb_split('/\r\n?/',$str);
			$str=join("\n", $chunks);

            $linebreak = chr(10);                        // Default line break for Unix systems.

            $newString = '';
            $theLines = mb_split('\n',$str);        // Split lines
            
            foreach ($theLines as $val)
            {
                $newVal = '';
                $theValLen = strlen($val);
                $len = 0;
                for ($index=0; $index < $theValLen; $index++)        
                {        // Walk through each character of this line
                    $char = substr($val,$index,1);
                    $ordVal = ord($char);
                    if ($len>($maxlen-4) || ($len>(($maxlen-10)-4)&&$ordVal==32))        
                    {
                        $newVal.='='.$linebreak;        // Add a line break
                        $len=0;                        // Reset the length counter
                    }
		            if (($ordVal>=33 && $ordVal<=60) || ($ordVal>=62 && $ordVal<=126) || $ordVal==9 || $ordVal==32)       
					{
                        $newVal.=$char;                // This character is ok, add it to the message
                        $len++;
                    } 
                    else 
                    {
                        $newVal.=sprintf('=%02X',$ordVal);        // Special character, needs to be encoded
                        $len+=3;
                    }
                }
                $newVal = preg_replace('/'.chr(32).'$/','=20',$newVal);
                // Replaces a possible SPACE-character at the end of a line
                $newVal = preg_replace('/'.chr(9).'$/','=09',$newVal);
                // Replaces a possible TAB-character at the end of a line
                $newString.=$newVal.$linebreak;
            }
            return preg_replace('/'.$linebreak.'$/','',$newString);                // Remove last newline
 		
		case 'base64':
			return  chunk_split(base64_encode(  $str  ));
		case 'substr':
			if(!$args->exists(2)) return $this->notFound();
			if(!$args->exists(3)) return substr($str,(int)$args->trimExpand(2));
			return substr($str,(int)$args->trimExpand(2),(int)$args->trimExpand(3));

		case 'substrw':
			if(!$args->exists(2)) return $this->notFound();
   	   $start = (int)$args->trimExpand(2);
			if(!$args->exists(3)) {
			   if ($start!=0) return preg_replace('/^\S*\s*/','',substr($str,$start-1));
			   return preg_replace('/^\s*/','',substr($str,$start));
			}
   	   $len = (int)$args->trimExpand(3);
		   if ($start!=0) $str = preg_replace('/^\S*\s*/','',substr($str,$start-1,$len+1));   	   
			else $str = preg_replace('/^\s*/','',substr($str,$start,$len+1));
			return preg_replace('/\s*\S*$/','',$str);

		case 'strpos':
		case 'instr':
			if(!$args->exists(2)) return $this->notFound();
			$pos = strpos($str,(int)$args->trimExpand(2));
			return ($pos!==false) ? $pos : $this->notFound();

		case 'collapse':
			$out=array();
			for($i=2; $i<=$args->count;$i++)
			{
				$val = $args->cropExpand($i);
				if (trim($val)) $out[]=$val;
			}
#			die(print_r($out,true));
			return join($str,$out);

		case 'fullcollapse':
			$out=array();
			for($i=2; $i<=$args->count;$i++)
			{
				$val = $args->cropExpand($i);
				if (trim($val)) $out[$val]=true;
			}
#			die(print_r($out,true));
			return join($str,array_keys($out));

		case 'join':
			$out=array();
			for($i=2; $i<=$args->count;$i++)
			{
				$val = $args->cropExpand($i);
				$out[]=$val;
			}
#			die(print_r($out,true));
			return join($str,$out);

		
		case 'len':
		case 'length':
			return mb_strlen($str);
		
		case 'replace':
			if($args->count < 3) return $this->notFound();
			
			$replaceWith = $args->cropExpand($args->count);
			$replaceWith=str_replace('\n',chr(10),$replaceWith);
			$replaceWith=str_replace('\t',chr(9),$replaceWith);

			$replaceParts=array();
			for ($i=2;$i<$args->count;$i++)
			{
				$replaceParts[] = $args->cropExpand($i);
			}
			$replacePattern=join('|',$replaceParts);
			$replacePattern=str_replace('/','\x2f',$replacePattern);
			try {
//echo "replacePattern: $replacePattern";
				return preg_replace("/$replacePattern/",$replaceWith,$str);
			} catch(Exception $e) {
				return $this->notFound();
			};
		case 'pad':
			if($args->count < 3) return $this->notFound();
			$pad_length=$args->trimExpand(2);
			$pad_string=$args->trimExpand(3);
			if($args->exists(4))
			{
				$direction = $args->trimExpand(4);
				switch ($direction)
				{
				case 'left':
					$pad_type = STR_PAD_LEFT;
					break;
				case 'both':
					$pad_type = STR_PAD_BOTH;
					break;
				default:
					$pad_type = STR_PAD_RIGHT;
				}
				#die ($direction);
			}
			else
			{
				$pad_type = STR_PAD_RIGHT;
			}
			return str_pad  ( $str , $pad_length , $pad_string , $pad_type );
		case 'split':
			if($args->count < 3) return $this->notFound();
			$delim=$args->cropExpand(2);
			$index=$args->cropExpand(3)-1;
			if($args->exists(4))
			{
				$count=(int)$args->trimExpand(4);
				$parts = explode($delim,$str,$count);
			}
			else
			{
				$parts = explode($delim,$str);
			}
			if (!isset($parts[$index]))	return '';
			return $parts[$index];
		case 'match':
			if($args->count < 3) return $this->notFound();
			
			$match = (int)$args->trimExpand($args->count)-1;
			$matchParts=array();
			for ($i=2;$i<$args->count;$i++)
			{
				$matchParts[] = $args->expand($i);
			}
			$matchPattern=join('|',$matchParts);
			$matchPattern=trim(str_replace('/','\x2f',$matchPattern));
			try {
				preg_match_all("/$matchPattern/",$str,$m,PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
				return isset($m[$match]) ? $m[$match][isset($m[$match][1]) ? 1 : 0] : '';
			} catch(Exception $e) {
				return $this->notFound();
			};

		case 'rpl':
			if($args->count < 3) return $this->notFound();
			
			$replaceWith = $args->get($args->count);
			$replaceParts=array();
			for ($i=2;$i<$args->count;$i++)
			{
				$replaceParts[] = $args->trimExpand($i);
			}
			$replacePattern=join('|',$replaceParts);
			$replacePattern=trim(str_replace('/','\x2f',$replacePattern));
#			print $this->dumpVar(array($replacePattern));
			try {
				preg_match_all("/$replacePattern/",$str,$m,PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
				$lastEnd=0;
				$ret='';
				foreach ($m as $k=>$v)
				{
					$wholeMatch = $v[0][0];
					$thisStart = $v[0][1];
					
					$ret.=substr($str,$lastEnd,$thisStart-$lastEnd);
					$newFrame=clone $f;
					foreach ($v as $index=>$match)
					{
					   $newFrame->namedArgs['$'.$index] = $match[0]; 
						//$this->addFrameArg($newFrame,'$'.$index,$match[0]);
					}
					$res = ($newFrame->expand($replaceWith));
					$ret.=$res;XxxArgs::cropSpace($res);
					$lastEnd = $thisStart+strlen($wholeMatch);
				}
				$ret.=substr($str,$lastEnd);
				return $ret;
			} catch(Exception $e) {
				return $this->notFound();
			};
		case 'rpl2':
			if($args->count < 3) return $this->notFound();
			
			$replaceWith = $args->get($args->count);
			$replaceParts=array();
			for ($i=2;$i<$args->count;$i++)
			{
				$replaceParts[] = $args->trimExpand($i);
			}
			$replacePattern=join('|',$replaceParts);
			$replacePattern=trim(str_replace('/','\x2f',$replacePattern));
			try {
				preg_match_all("/$replacePattern/",$str,$m,PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
				$lastEnd=0;
				$ret='';
				foreach ($m as $k=>$v)
				{
					$wholeMatch = $v[0][0];
					$thisStart = $v[0][1];
					#print_r(array('start'=>$thisStart));
					$ret.=substr($str,$lastEnd,$thisStart-$lastEnd);
					$newFrame=clone $f;
					foreach ($v as $index=>$match)
					{
						//$this->addFrameArg($newFrame,'$'.$index,$match[0]);
					}
					$res = ($newFrame->expand($replaceWith));
					$ret.=$res;
					$lastEnd = $thisStart+strlen($wholeMatch);
					#return $this->dumpVar(array(get_class($f),$newFrame->namedArgs));
				}
				$ret.=substr($str,$lastEnd);
				return $ret;
			} catch(Exception $e) {
				return $this->notFound();
			};			
		case 'hash':
		case 'hash16':
		case 'hash8':
		case 'hash4':
			foreach ($args->args as $i)
			{
				$strs[]=$args->trimExpand($i);
			}
			$str = join('|',$strs);
			switch ($args->command)
			{
			case 'hash16':
				return substr(md5($str),0,16);
			case 'hash8':
				return substr(md5($str),0,8);
			case 'hash4':
				return substr(md5($str),0,4);
			default:
				return md5($str);
			}
		   
		case 'time':
			$format = $args->cropExpand(2);
		   echo $out = "$str => ".print_r(date_parse_from_format($format,$str),1)."</br>";
			return strtotime($out);
		default:
			return $this->notFound();
		}
		return $this->notFound(); # cosmic ray
	}
    
	function fl_number($parser,$f,$a)
	{
		$args = new XxxArgs($f,$a);
		switch($args->command)
		{
		case 'max':
		case 'atleast':
			foreach($args->args as $i)
			{
				if(!isset($max)) $max=(float)$args->trimExpand($i);
				else
				{
					$val = (float)$args->trimExpand($i);
					if ($val>$max) $max=$val;
				}
			}
			return $max;
		case 'min':
		case 'atmost':
			foreach($args->args as $i)
			{
				if(!isset($min)) $min=(float)$args->trimExpand($i);
				else
				{
					$val = (float)$args->trimExpand($i);
					if ($val<$min) $min=$val;
				}
			}
			return $min;
/*
	{{#number:fit|15|10|20}} -> 15
	{{#number:fit|30|10|20}} -> 20
	{{#number:fit| 5|10|20}} -> 10
	{{#number:fit|15|20|10}} -> 15
	{{#number:fit|30|20|10}} -> 20
	{{#number:fit| 5|20|10}} -> 10
*/			
		case 'fit':
			if($args->count!=3) return $this->notFound();
			$val = (float)$args->trimExpand(1);
			$min = (float)$args->trimExpand(2);
			$max = (float)$args->trimExpand(3);
			if ($min>$max) list($min, $max) = array($max, $min);
			if ($min>$val) $val = $min;			
			if ($max<$val) $val = $max;
			return $val;			
/*
	{{#number:between|15|10|20}} -> BETWEEN
	{{#number:between|30|10|20}} -> 
	{{#number:between| 5|10|20}} -> 
	{{#number:between|15|20|10}} -> BETWEEN
	{{#number:between|30|20|10}} -> 
	{{#number:between| 5|20|10}} -> 
*/			
		case 'between':
			if($args->count!=3) return $this->notFound();
			$val = (float)$args->trimExpand(1);
			$min = (float)$args->trimExpand(2);
			$max = (float)$args->trimExpand(3);
			if ($min>$max) list($min, $max) = array($max, $min);
			if ($min<=$val and $val<=$max) return "BETWEEN";
			return '';			
		case 'random':
			switch($args->count)
			{
			case 2: 
				$from = $args->trimExpand(1);
				$to = $args->trimExpand(2);
				break;
			case 1:
				$from = 1;
				$to = $args->trimExpand(1);
				break;
			default:
				return $this->notFound();
			}
			return rand($from,$to);
		}
		return $this->notFound();			
    }

	function fl_format(&$parser,&$f,$a)
	{
		$args = new XxxArgs($f,$a);
		switch($args->command)
		{
		case 'currency':
			switch($args->count)
			{
			case 2: 
				$val = $args->trimExpand(2);
				$frm = $args->trimExpand(1);
				break;
			case 1:
				$val = $args->trimExpand(1);
				$frm = '%n';
				break;
			default:
				return $this->notFound();
			}
			return money_format($frm,$val);
		case 'number':
			switch($args->count)
			{
			case 1: 
				$dec = 0;
				$decsep = null;
				$thosep = null;
				$val = (int)$args->trimExpand(1);
				break;
			case 2: 
				$dec = (int)$args->trimExpand(1);
				$decsep = null;
				$thosep = null;
				$val = $args->trimExpand(2);
				break;
			case 3: 
				$dec = (int)$args->trimExpand(1);
				$decsep = $args->trimExpand(2);
				$thosep = null;
				$val = $args->trimExpand(3);
				break;
			case 4:
				$dec = (int)$args->trimExpand(1);
				$decsep = $args->trimExpand(2);
				$thosep = $args->trimExpand(3);
				$val = $args->trimExpand(4);
				break;
			default:
				return $this->notFound();
			}
			return number_format($val,$dec,$decsep,$thosep);
		}
		return $this->notFound();
	}
/*
	function fl_token(&$parser,&$f,$a)
	{
		global $wgUser;
		$args = new XxxArgs($f,$a);
		switch($args->command)
		{
		
		case 'get':
			switch($args->count)
			{
			case 0:
				$salt = 'fl_token|zglajz';
			case 1: 
				$salt = 'fl_token|šklj|'.$args->trimExpand(1);
				break;
			default:
				return $this->notFound();
			}
			$_SESSION['xuuTokenOnce'] = md5(mt_rand() . mt_rand().$salt); 
			return $_SESSION['xuuTokenOnce'];
		case 'show':
			switch($args->count)
			{
			case 0:
				$salt = 'fl_token|zglajz';
			case 1: 
				$salt = 'fl_token|šklj|'.$args->trimExpand(1);
				break;
			default:
				return $this->notFound();
			} 
			if (!isset($_SESSION['xuuTokenOnce'])) $_SESSION['xuuTokenOnce'] = md5(mt_rand() . mt_rand().$salt); 
			return $_SESSION['xuuTokenOnce'];
		case 'check':
			switch($args->count)
			{
			case 1: 
				$salt = 'fl_token|zglajz';
				$val = $args->trimExpand(1);
				break;
			case 2: 
				$salt = 'fl_token|šklj|'.$args->trimExpand(1);
				$val = $args->trimExpand(2);
				break;
			default:
				return '';
			}
			
			$ret = ($val && $_SESSION['xuuTokenOnce'] == $val) ? 'CHECK' : '';  
			$_SESSION['xuuTokenOnce'] = md5(mt_rand() . mt_rand().$salt); 
			return $ret;
		}
		return $this->notFound();
	}
	
*/
########################
#
#   {{#mail:token}} - produce a hidden input for inclusion in mail forms
#	{{#mail:send|to|from|subject|name=file|name=file|body}} - interface to UserMailer::send()
#	{{#mail:sendhtml|to|from|subject|name=file|name=file|text|html}}
#
########################
	
	# a flag to make sure that we're not checking the token that just got set
	var $mailTokenSet = false;
/*	
	function makeAttachments(&$args, $start=1, $end=100)
	{
		$attachments=array();
		for ($i=$start;$i<=$end && $i<=$args->count;$i++)
		{
			if ($args->isNamed($i))
			{
				$fileName = "$wgUploadPath/" . $args->trimExpandValue($i);
				if (file_exists($fileName))
				{
					$attachments[$args->getName($i)] = chunk_split(base64_encode(file_get_contents($fileName)));
				} 
			}
		}
		return $attachments;
	}
	
	function makeMail(&$args)
	{
		if ($args->count < 4) return false;
		$to = $args->trimExpand(1);
		$from = $args->trimExpand(2);
		$subject = $args->trimExpand(3);
		$message = $args->cropExpand($args->count);
		$attachments = $this->makeAttachments($args,4,$args->count-1);
		
		$random_hash = md5(date('r', time())); 
		$headers = "From: $from\r\nReply-To: $from";
		$headers .= "\r\nContent-Type: multipart/mixed; boundary=\"PHP-mixed-$random_hash\""; 
		$body = <<<END
--PHP-mixed-$random_hash 
Content-Type: multipart/alternative; boundary="PHP-alt-$random_hash"

--PHP-alt-<?php echo $random_hash; ?> 
Content-Type: text/plain; charset="utf8"
Content-Transfer-Encoding: quoted-printable
END;
		$body.=$message;

		return array($to,$from,$subject,$header,$body);
	}

	function fl_mail(&$parser,&$f,$a)
	{
		$args = new XxxArgs($f,$a);

		switch($args->command)
		{
		case 'token':
			wfSetupSession();
			$key = $parser->getRandomString();
			$val = $parser->getRandomString();
			$_SESSION['XuuMailTokenKey'] = $key;
			$_SESSION['XuuMailTokenVal'] = $val;
			$this->mailTokenSet=true;
			return array(
				0=> "<input type=\"hidden\" name=\"$key\" value=\"$val\">",
				'isHTML' => true
			);
		case 'sendonce':	
		case 'send':
			if ($args->count<4) 
				return $this->notFound();

			$to = new MailAddress($args->trimExpand(1));
			$from = new MailAddress($args->trimExpand(2));
			$title=$parser->mTitle->getFullText();

			if ($args->command=='send')
			{				
				# make sure that we're not comparing 
				if ($this->mailTokenSet) return '';
							
				#check token
				wfSetupSession();
				#if ($_POST[$_SESSION['XuuMailTokenKey']]!= $_SESSION['XuuMailTokenVal']) return '';
				$_SESSION['XuuMailTokenKey'] = Parser::getRandomString();
				$_SESSION['XuuMailTokenVal'] = Parser::getRandomString();
			}
			elseif($args->command=='sendonce')
			{
				if ($_SESSION['XuuMailAlreadySent']["$title|$to"]) return '';
			}
			$subject = $args->trimExpand(3);
			$body = $parser->mStripState->unstripBoth($args->trimExpand(4));
			$contentType=$args->trimExpand(5,null);
			
			$result = UserMailer::send($to,$from,$subject,$body,null,$contentType);
			if ($result===true)
			{
				if($args->command=='sendonce')
				{
						$_SESSION['XuuMailAlreadySent']["$title|$to"] = 'foobarbaz';
				}
				return "SENT";
			};
			return "";
		}
	}
	*/
	
	function fl_iftest(&$parser,$frame,$args) {
		$test = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';
		if ( $test !== '' ) {
			return isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : '';
		} else {
			return isset( $args[3] ) ? trim( $frame->expand( $args[3] ) ) : '';
		}
	}
	function fl_test(&$parser,$f,$a)
	{
		global $wgUser;
		$args = new XxxArgs($f,$a);
		switch($args->command)
		{
		case 'if': 
		  if ($args->trimExpand(1,'')) return $args->trimExpand(2,'');
		  else return $args->trimExpand(3,'');
		case 'or':
		  foreach($args->args as $i)
		  {
		  	if ($args->isNamed($i))
		  	{
		  		if ($args->getName($i) == $args->trimExpandValue($i)) return 'OR';
		  	}
		  	else
		  	{
		  		$val=$args->trimExpand($i);
		  		
		  		if ($val !== '') return $val;
		  	} 
		  }
		  return '';
		case 'and':
		  foreach($args->args as $i)
		  {
		  	if ($args->isNamed($i)) 
		  	{
		  		if ($args->getName($i) != $args->trimExpandValue($i)) return '';
		  	}
		  	elseif ($args->trimExpand($i) == '') return ''; 
		  }
		  return 'AND';
		}
		return $this->notFound();
    }
    
	function tag_xqq ($input,$args,&$parser,&$frame) {
	
		$text = XqqParser::Parse($input);
		return $parser->recursiveTagParse($text,$frame);
	} 
}
class XqqParser {

	var $stack = array();
	var $current = '';
	var $next= '';
	var $counter=0;
	var $end=false;
	var $text;
	var $len;
	
	function consume ($r=false) {
		if($r) {
			$this->counter=0;
			$this->len=mb_strlen($this->text);
		} else {
			$this->counter++;
		}
   		if($this->counter>=$this->len) {
   			$this->end = true;
   			$this->current='';
   			$this->next='';
   		} else {
   			$this->current=mb_substr($this->text,$this->counter,1);
   			$this->next = $this->counter<$this->len ? mb_substr($this->text,$this->counter,1) : '';
   		}
	}
	
	static function Parse($text) {
		$p=new XqqParser();
		$p->text=$text;
		return $p->doParse();
	}
	
    function doParse () {
    	$out='';
    	$this->consume(true);
    	$i=0;
    	while(!$this->end && $i<1000) {
    		$i++;
    		$top = $this->stack[count($this->stack)-1];
    		
    		switch($this->current) {
    		case '\\':
    			$this->consume();
    			switch ($this->current) {
    			case 'n':
    				$out.="\r\n";
    				break;
    			case 't':
    				$out.="\t";
    				break;
    			default:
    				$out.=$this->current;
    			}
    			$this->consume();
    			break;
    		case '{':
    			$this->consume();
    			$out.='{{';
    			$this->stack[]='{';
    			break;
    		case '(':
    			$this->consume();
    			$out.='{{#expr:';
    			$this->stack[]='(';
    			break;
			case '$':
				$this->consume();
				if ($this->current=='{') {
					$this->consume();
					$this->stack[]='${';
					$out.='{{{';
				} else {
					$arg='';
					while(!$this->end && preg_match('/\\w/',$this->current)) {
						$arg.=$this->current;
						$this->consume();
					}
					$out.='{{{'.$arg.'}}}';
				}
				break;
			case " ":
			case "\n":
			case "\t":
				$this->consume();
				$out.=" ";
				while(!$this->end && preg_match('/\\s/',$this->current)) {
					$this->consume();
				}
				break;
			case ")":
				$this->consume();
				if ($top=='(') {
					array_pop($this->stack);
					$out.='}}';
				} else {
					$out.=")";
				}
				break;
			case "|":
				$this->consume();
				$out.='|';
				break;
			case "}":
				$this->consume();
				if ($top=='{') {
					array_pop($this->stack);
					$out.='}}';
				} elseif ($top=='${') {
					array_pop($this->stack);
					$out.='}}}';
				} else {
					$out.='}';
				}
				break;
			default:
				$out.=$this->current;
				$this->consume();
    		}
    	}
    	return $out;
    }
    
}
