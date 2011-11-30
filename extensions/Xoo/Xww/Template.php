<?php
XxxInstaller::Install('XwwTemplate');
 
#
#  XwwTemplate - Xoo World of Wiki - Templates and transclusions
#
#  Part of Xoo (c) 1997-2008 [[w:en:User:Zocky]], mitko.si
#	GPL3 applies
#
#
#	Wikivariables and parser functions for dealing with template transclusion
#
############################################################################

class XwwTemplate extends Xxx
{	

	var $mDefaultSettings = array
	(
		"evalhtml"			=> true,
		"evalhtmlanywhere"	=> false, 		# will be added to the wikibase name, if no dbname is provided
	);

	function fl_eval(&$parser,$frame,$fnArgs)
	{
/*echo "<pre>frame template: ";
print_r($frame->getArguments());
print($frame->title);
print_r($a);
echo "</pre>";
*/
		$a = $fnArgs;
		
	    $args= new XxxArgs($frame,$fnArgs);
	    
		switch ($args->command)
		{
		case 'local':
		case 'parse':
		    $frameArgs=array();
		    $counter=count($frame->namedArgs)+count($frame->numberedArgs)+1;
		    
			for ($i=1;$i<$args->count;$i++)
			{
#				print_r($i);
#				print_r($args->getKey($i));
				if ($args->isNamed($i))
				{
					#$frameArgs[$args->getName($i)]=$args->getValue($i);
					$frameArgs[$args->getKey($i)]=$args->cropExpandValue($i);
				}
				else 
				{
					#$frameArgs[$counter]=$args->get($i);
					$frameArgs[$counter]=$args->cropExpand($i);
					$counter++;
				}
			}
			$dom = $args->get($args->count);
			$customFrame = $this->newExtendedFrame($frame,$frameArgs,$frame->title);
		    $parsedText = $customFrame->expand($dom);
		    
		    if ($args->command=='local') return $parsedText;
		    elseif ($args->command=='parse') 
		    {
		    	global$wguser;
		    	$newParser = new Parser();
				$options = ParserOptions::newFromUser($wgUser);
				$text =  $newParser->parse( $parsedText, $parser->mTitle, $options, false)->mText;
				return array($parser->mStripState->unstripBoth($text),'isHTML'=>true);
		    }
		    else die('cosmic ray');

		case 'parent':
			if ($args->count != 1) return $this->notFound();
			if ($frame->parent) $frame=&$frame->parent;
			return $frame->expand($args->get(1));#. '=['.($frame->parent !== $frame).']';
		case 'source':
			$template = $args->trimExpand(1,'');
		    $ns=NS_TEMPLATE;
		    $title = Title::newFromText( $template, $ns);
		    if (!$title) return $this->notFound();		    
		    list( $text, $title ) = $parser->fetchTemplateAndTitle( $title );
			return array(htmlspecialchars($text), 'isHTML'=>true);
		case 'wrap':
			#return "aa";
			$template = $args->trimExpand(1,'');
		    $ns=NS_TEMPLATE;
		    $title = Title::newFromText( $template, $ns);
		    if (!$title) return $this->notFound();
#		    if (!$title->exists()) return "[[".$title->getPrefixedText()."]]";
		    list( $dom, $title ) = $parser->getTemplateDom( $title );
		    if (!$dom) return "[[".$title->getPrefixedText()."]]";
		    
		    $frameArgs=array();
		    $counter=count($frame->namedArgs)+count($frame->numberedArgs)+1;
		    
			for ($i=2;$i<=$args->count;$i++)
			{
				if ($args->isNamed($i))
					$frameArgs[$args->getName($i)]=$args->getValue($i);
					#$frameArgs[$args->getName($i)]=$args->cropExpand($i);
				else 
				{
					$frameArgs[$counter]=$args->get($i);
					#$frameArgs[$counter]=$args->cropExpandValue($i);
					$counter++;
				}
			}

			$customFrame = $this->newExtendedFrame($frame,$frameArgs,$title);
		    return $customFrame->expand($dom);
		case 'html':
			#if (!$this->S('evalhtml')) return $this->notFound();
			global $wgAllowEvalHtmlIn;
			if (!isset($wgAllowEvalHtmlIn[$frame->title->getNamespace()])) return $this->notFound();
			$ret=array();
			for ($i=1;$i<=$args->count;$i++) {
				$ret[] = $args->expand($i);
			}
			$ret = implode('|',$ret);
			$ret = $parser->mStripState->unstripBoth($ret);
			$ret = htmlspecialchars_decode($ret);
			$ret =str_replace('&amp;','&',$ret);
			$ret = preg_replace('/\\s+/',' ',$ret);
			#print_r(array($ret));
			return array($ret,'isHTML'=>true,'noparse'=>false);

		case 'expand':
			#$lastArg=array_pop($args);
			$source = $args->cropExpand(1);
			return array(preg_replace('/\n/','<br/>',htmlspecialchars($args->cropExpand(1))),'isHTML'=>true);
        

		case 'wiki':
		    
		case 'parse':
			$command=array_shift($a); #
			$lastArg=array_pop($a);
			foreach ($a as $i=>$arg)
			{
				$a[$i]=$arg->node;
			}
			
			$newFrame = $frame->newChild($a);
			$text=$newFrame->expand($lastArg);
			$newParser = new Parser();
			$options = ParserOptions::newFromUser($wgUser);
			return array($newParser->parse( $text, $parser->mTitle, $options, false)->mText,'isHTML'=>true);
		case 'time':
			$before = microtime(true);
			$ret = $frame->expand($args[0]);
			$after = microtime(true);
			$time = $after - $before;
			return "<br>from ". ((int)($time*1000)/1000) . " seconds<br>$ret";
		default:
			return $this->notFound();
		}
	}
	

	function frameFunctions($fnName,&$frame,$arg=false)
	{
		switch($fnName)
		{
		case 'document':
			return "[{$frame->document}]";			
		case 'title':
			return array($frame->title->getFullText());
		case 'startpos':
			return array($frame->startPos);
		case 'endpos':
			return array($frame->endPos);
		case 'editcall':
			$editText=$arg === false ? wfMsg('edit') : $arg;
			if ($frame->parent)
				return array
				(
					'isHTML'=>true,
#					'isChildObj'=>true,
					'<span class="editcall"><a href="' . $frame->parent->title->getFullUrl("action=edit&startpos={$frame->startPos}&endpos={$frame->endPos}").'">'.$editText.'</a></span>'
				);
			return $this->notFound();

		case 'edit':
			$editText=$arg === false ? wfMsg('edit') : $arg;
			if ($frame->parent)
				return array
				(
					'isHTML'=>true,
#					'isChildObj'=>true,
					'<a href="' . $frame->title->getFullUrl("action=edit").'">'.$editText.'</a>'
				);
			return $this->notFound();
			
		default:
			return $this->notFound();
		}
	}

	function fl_template(&$parser,$frame,$inArgs)
	{
		$args=new XxxArgs($frame,$inArgs);
		return $this->frameFunctions($args->command,$frame,$args->trimExpand(1,false));
	}
	
	function fl_caller(&$parser,$frame,$inArgs)
	{
		$args=new XxxArgs($frame,$inArgs);
		$which = $args->trimExpand(1,1);

		$callerFrame=&$frame;
		for ($i=0;$i<$which && $callerFrame->parent;$i++)
		{
			$callerFrame=&$callerFrame->parent;
		}
		if ($i<$which) return $this->notFound();
		return $this->frameFunctions($args->command,$callerFrame,$args->trimExpand(2,false));
	}	
	
}	

//####### Used Hooks ######################
$wgHooks['EditPage::showEditForm:fields'][] = 'efXwwShow';
$wgHooks['EditPage::attemptSave'][] = 'efXwwSave';

function efXwwShow(&$editPage) 
{
	global $wgRequest, $wgOut;
	
	if (!$editPage->mTitle->exists()) return true;
	$startPos=$wgRequest->getInt('startpos',-1);
	$endPos=$wgRequest->getInt('endpos',-1);

	if ($startPos>-1 && $endPos>-1) {
		if (!$wgRequest->getInt('editpos',false))	{
			$editPage->textbox1=substr($editPage->textbox1,$startPos,$endPos-$startPos);
		}
		
		$wgOut->addHTML( '
	<input type="hidden" value="'.$startPos.'" name="startpos" />
	<input type="hidden" value="'.$endPos.'" name="endpos" />
	<input type="hidden" value="true" name="editpos" />
	');
	}
	return true;
}

function efXwwSave($editPage) 
{
	global $wgRequest, $wgOut;
	if ($editPage->mTitle->mArticleID==0) return true;
	$text = $editPage->mArticle->getContent(true);
	$startPos=$wgRequest->getInt('startpos',-1);
	$endPos=$wgRequest->getInt('endpos',-1);
	if ($startPos>-1 && $endPos>-1)
	{
		$editPage->textbox1=substr($text,0,$startPos) . $editPage->textbox1 . substr($text,$endPos);
	}
	return true;
}
	
/*	
	function fl_template()
	{{#template:caller}}
	{{#template:self}}
	{{#template:args}}
	
	{{{a}}}
	
	me/self/this
	caller
	args
	caller's args
	position in source
	
*/

