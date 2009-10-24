<?php
#
#	Preliminaries, look lower for interesting stuff
#
XxxInstaller::Install('XppExpression');

define ('XPP_PREC',0);
define ('XPP_ARITY',1);
define ('XPP_KIND',2);
define ('XPP_LEFTTYPE',3);
define ('XPP_RIGHTTYPE',4);
define ('XPP_EVAL',5);

define ('XPP_FAIL',null);

class XppNode
{
	var $subNodes;
	var $operator;
	function __construct()
	{
		$args = func_get_args();
		$this->operator=shift($args);
		
		if (is_array($args[1]))
		{
			$this->subNodes=$args[1];
		}
		else
		{
			$this->subNodes=$args;
		}
	}
}

class XppExpression extends Xxx
{
	
	# operator configuration
	var $mOps=array
	(
#		op					prec	arity	kind		left type	right type	eval
		'__'=> array (	9,		2, 	'left',	'string',	'string',	'$L . $R' ),
		'+'=> array (	10,	2, 	'left',	'float',		'float',  	'$L + $R' ),
		'-'=> array (	10,	2, 	'left',	'float',		'float',  	'$L - $R' ),
		'*'=> array (	11,	2, 	'left',	'float',		'float',  	'$L * $R' ),
		'/'=> array (	11,	2, 	'left',	'float',		'float',		'$R == 0 ? XPP_FAIL : $L / $R'),
		'_'=> array (	12,	2, 	'left',	'string',	'string',	'$L . $R' ),
		'('=> array (	99,	1, 	'open',	'(',			')'	),
		')'=> array (	99,	1, 	'close',	'(',			')'	)
	);
	
	function getPrec		($op)		{	return $this->mOps[$op][XPP_PREC]; 					}
	function isUnary		($op)		{	return $this->mOps[$op][XPP_ARITY]	==	1;			}
	function isBinary		($op)		{	return $this->mOps[$op][XPP_ARITY]	==	2;			}
	function isLeft		($op)		{	return $this->mOps[$op][XPP_KIND]	==	'left';	}
	function isRight		($op)		{	return $this->mOps[$op][XPP_KIND]	== 'right';	}
	function isOpen		($op)		{	return $this->mOps[$op][XPP_KIND]	== 'open';	}
	function isClose		($op)		{	return $this->mOps[$op][XPP_KIND]	== 'close';	}
	function getLeftType	($op)		{	return $this->mOps[$op][XPP_LEFTTYPE];				}
	function getRightType($op)		{	return $this->mOps[$op][XPP_RIGHTTYPE];			}
	function getEval		($op)		{	return $this->mOps[$op][XPP_EVAL];					}

###################################################################################
	
	var $mOperatorStack = array();
	var $mValueStack = array();
	var $mOuput = array();
	
	function pushOperator($op)
	{
		$this->mOperatorStack[]=$op;
	}
	function popOperator()
	{
		if (!count($this->mOperatorStack)) return null;
		return array_pop($this->mOperatorStack);
	}
	function topOperator()
	{
		if (!count($this->mOperatorStack)) return null;
		return $this->mOperatorStack[count($this->mOperatorStack)-1];
	}

	function getTopPrec()
	{
		if (!count($this->mOperatorStack)) return null;
		return $this->getPrec($this->topOperator());
	}
	
	function anyOperators()
	{
		return count($this->mOperatorStack)>0;
	}
	
	function pushValue($value)
	{
		$this->mValueStack[]=$value;
	}

	function popValue()
	{
		if (!count($this->mValueStack)) return null;
		return array_pop($this->mValueStack);
	}
	
###################################################################################

	function processValue($value)
	{
		$this->mOutput[]=array('value',$value);
		$this->pushValue($value);
	}

	function fixOperand($val,$type)
	{
		switch ($type)
		{
			case 'float': return (float)$val;
			case 'string': return (string)$val;
			default:
				die();
		}
	}
	function popAndFixLeft($op)
	{
		if (count($this->mValueStack)==0) die();
		$val = $this->popValue();
		return $this->fixOperand($val,$this->getLeftType($op));
	}

	function popAndFixRight($op)
	{
		if (count($this->mValueStack)==0) die();
		$val = $this->popValue();
		return $this->fixOperand($val,$this->getRightType($op));
	}


	function processOperator($op)
	{
		$this->mOutput[]=array('operator',$op);
		$L=null;
		$R=null;
		if($this->isUnary($op))
		{
			if ($this->isLeft())
			{
				$R=$this->popAndFixRight($op);
			}
			elseif ($this->isRight())
			{
				$L=$this->popAndFixLeft($op);
			}
			else die();
		}
		elseif($this->isBinary($op))
		{
			$R=$this->popAndFixRight($op);
			$L=$this->popAndFixLeft($op);
		}
		else
		{
			die();
		}
		$res = call_user_func(create_function('$L,$R','return ('.$this->getEval($op).');'),$L,$R);
		$this->pushValue($res);
	}

#####################################################################################

	function processGreaterOps($until)
	{
		$prec = $this->getPrec($until);
		while ($this->anyOperators())
		{
			if ($this->isOpen($this->topOperator())) return true;
			if ($this->getTopPrec() <= $prec) return true;

			$op=$this->popOperator();
			$this->processOperator($op);
		}
		return true;
	}

	function processCloseBracket($close)
	{
		$until = $this->getLeftType($close);
		while ($this->anyOperators())
		{
			$op = $this->popOperator();
			if ($this->isOpen($op))
			{
				return $op==$until;
			}
			$this->processOperator($op);
		}
		return false;
	}

	function processAllOps()
	{
		if ($close)	$until = $this->getLeftType($close);
		while ($this->anyOperators())
		{
			$op = $this->popOperator();
			if ($this->isOpen($op))
			{
				return false;
			}
			$this->processOperator($op);
		}
		return true;
	}


#######################################################################################	

	#regexp to search for operators	
	var $mOperatorPattern;
	
	function extractValue(&$text)
	{
		if 
		(
			$value = $this->extractStart('(\d+(\.\d+)?)',$text) or
			$value = $this->extractStart('\'([^\']*)\'',$text,1) or
			$value = $this->extractStart('\"([^\"]*)\"',$text,1) or
			$value = $this->extractStart('\p{L}[\p{L}\p{N}]*',$text)
		) return $value;
		else return false;
	}

	function extractOperator(&$text)
	{
		if ( $op = $this->extractStart($this->mOperatorPattern,$text))	return $op;
		else return false;
	}
	function setupExtension()
	{
		$this->mOperatorPattern=$this->makeOrPattern(array_keys($this->mOps));
	}

########################################################################################3
	
	function fl_x(&$parser,&$frame,&$inArgs)
	{
		$args=new XxxArgs($frame,$inArgs);
		$parts = array($args->command);
		foreach ($args->args as $i)
		{
			$parts[] = $args->getRaw($i);
		}
		$text = join ('|',$parts);

		$outputStack=array();
		$opStack=array();

#		[1] While there is more input continue to [2] otherwise go to [6]
		$expect = 'value';
	   while ($text)
    	{
    		$text=trim($text);
#				[2] Scan in the next character
#				[3] Determine whether it is a bracket, operand, or operator
			if ($value=$this->extractValue($text))
			{
				# if expecting an operator, presume concat
				if ($expect=='operator') {	$text= '__'.$value.$text; continue; }

#				[4a] If it is an operand, push it to the output
				$this->processValue($value);
				$expect = 'operator';
			}
			elseif ($op = $this->extractOperator($text))
			{
				if ($this->isOpen($op))
				{
					# if expecting an operator, presume concat
					if ($expect=='operator') {	$text= '__'.$op.$text; continue; }
					
#					[4b] If it is a left bracket, push onto stack
					$this->pushOperator($op);
					$expect = 'value';
				}
				elseif ($this->isClose($op))
				{
	            # if expecting a value,syntax error
					if ($expect=='value') return "expecting value, $op found"; 

					if (!$this->processCloseBracket($op)) 
					{
						return "mismatched '$op' at '$op$text'";
					}
					$expect = 'operator';
				}
				elseif ($this->isBinary($op))
				{
					if ($expect=='value') return "expecting value, $op found"; 
					if (!$this->processGreaterOps($op)) return "cosmic ray";
					$this->pushOperator($op);
					$expect = 'value';
				}
				else
				{
					return "not implemented yet";
				}
			}
         else																			
         {
        		return "expecting $expect at $text"; $this->notFound();
            die ("cosmic ray");
         }
#				[5] Go back to [1]
		}	
#			[6] Flush the stack into the output 
#		return "result: ".$this->popValue().$this->dumpVar($this->mOutput).$this->dumpVar($this->mValueStack);
		if (!$this->processAllOps()) return "unclosed bracket";
		return $this->popValue();
	}
}
