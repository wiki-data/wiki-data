<?php
#
#	Preliminaries, look lower for interesting stuff
#
if ( !defined( 'MEDIAWIKI' ) ) { die( 'This file is a MediaWiki extension, it is not a valid entry point' );}

define ('NS_TEMPLATE_FORM',2320);
define ('NS_TEMPLATE_SAVE',NS_TEMPLATE_FORM+2);

XxxInstaller::Install('XxxEditForms');
#
#  Edit forms - <editform></editform>
#  {{Template:Foo}} {{Template form:Foo}} {{Template save:Foo}}
#
#  Part of Xoo (c) 1997-2008 [[w:en:User:Zocky]], mitko.si
#  GPL3 applies
#
#########################################################################
class XxxEditForms extends Xxx
{
    var $mMessages = array
    (
		'editform_add_remove' => 
			'You tried to add or remove a protected section',
		'editform_modify' => 
			'You tried to modify protected text',
		'editform_forbidden' =>
			'Forbidden'
	);

	var $mDefaultSettings = array
	(
	);

	var $passthrough;

	function setupExtension()
	{
		global $wgMessageCache;
		global $wgGroupPermissions;
		global $wgAvailableRights;
		global $wgRequest;
		
		$wgAvailableRights[ ] = 'editform';
		$wgGroupPermissions[ 'sysop' ][ 'editform' ]  = true;
		$wgGroupPermissions['bureaucrat']['editform'] = true;
		$wgGroupPermissions['user']['editform']       = true;

		$wgAvailableRights[] = 'editform';
		$wgGroupPermissions['user']['editform']	= true;


		#$this->passthrough = (($s=$wgRequest->getText('subskin')) ? "subskin=$s" : '');
		global $wgExtraNamespaces;
		$wgExtraNamespaces[NS_TEMPLATE_FORM+0] = "Template_form";
		$wgExtraNamespaces[NS_TEMPLATE_FORM+1] = "Template_form_talk";
		$wgExtraNamespaces[NS_TEMPLATE_SAVE+0] = "Template_save";
		$wgExtraNamespaces[NS_TEMPLATE_SAVE+1] = "Template_save_talk";

	}

	function hook_EditFilter ( $editpage, $textbox1, $section )  {

		# check for partial protection 
		global $wgUser;

		if ( !$wgUser->isAllowed( 'editform' ) ) {
			$modifyProtect = false; 
			$text1 = $editpage->mArticle->getContent(true);
			$text2 = $textbox1 ;

			preg_match_all( "/<editform(.*?)>(.*?)<\/editform>/si", $text1, $list1, PREG_SET_ORDER );
			preg_match_all( "/<editform(.*?)>(.*?)<\/editform>/si", $text2, $list2, PREG_SET_ORDER );
			if( count($list1) != count($list2)) { 
				$msg = wfMsg( 'editform_add_remove'); 
				$modifyProtect = true; 
			}
			else for ( $i=0 ; $i < count( $list1 ); $i++ ) {
				if( $list1[$i][0] != $list2[$i][0]) { 
					$msg = wfMsg( 'editform_modify' );
					$modifyProtect = true; 
					break;
				}
			}

			if( $modifyProtect ) {
				global $wgOut;
				$wgOut->setPageTitle( wfMsg( 'editform_forbidden' ) );
				$wgOut->addWikiText($msg);
				return false;
			}
		}
		return true;
	}

	var $editforms=0;
	
	function tag_editform(&$text,&$args,&$parser)
	{
		global $wgOut;
#		$parser->disableCache();
		static $depth=0;

		$this->editforms++;
		$depth++;	
		$s = $parser->recursiveTagParse('{{' . trim($text) . '}}');
		$depth--;
		return $s;
	}

	var $editform;
	var $template;
	var $params;
	var $nowiki;
	var $article;
		
	function hook_UnknownAction(&$action,&$article)
	{
		global $wgRequest;
		global $wgOut;
		global $wgUser;
		# we're handling action=editform
		if ($action!='editform') return true;

	
		$this->article=&$article;

		$this->editform = $wgRequest->getInt('editform');
		
		if ($wgRequest->getVal('editform_save')) $subaction='save';
		elseif ($wgRequest->getVal('editform_preview')) $subaction='preview';
		elseif ($wgRequest->getVal('editform_delete')) $subaction='delete';
		elseif ($wgRequest->getVal('editform_confirmdelete')) $subaction='confirmdelete';
		elseif ($wgRequest->getVal('editform_cancel')) $subaction='cancel';
		elseif ($wgRequest->getVal('editform_new')) $subaction='new';
		else $subaction = 'edit';
		Parser::disableCache();
		switch ($subaction)
		{
		case 'new':
			$this->editform='new';
			$this->extractFromCGI();
			$this->showEdit();
			break;
		case 'edit':
			$this->extractFromBoth();
			$this->showEdit();
			break;
		case 'preview':
			$this->extractFromBoth();
			$this->showDisplay();
			$this->showEdit();
			break;
		case 'save':
			$this->extractFromBoth();
			$this->showDisplay();
			$this->saveForm();
			global $wgTitle;
			$wgOut->redirect($wgTitle->getFullURL('action=purge'));
			break;
		case 'delete':
			$this->extractFromSource();
			$this->showDisplay();
			$this->showConfirmDelete();
			break;
		case 'confirmdelete':
			$this->deleteForm();
			break;
		case 'cancel':
			$this->extractFromSource();
			$this->showDisplay();
#			$title=Title::newFromText('special:GeoNotify');
#			$wgOut->redirect($title->getFullURL( 'notify=cancel&redir_title='. $this->article->mTitle->getPrefixedDBkey()));
			break;
		}
		return false;
	}
	
	function userCanEditForm($user,$title)
	{
		if (!$user->isAllowed( 'editform' )) return false; 
		if (!$title->exists() && $user->isAllowed( 'editform' )) return true;
		$groups=$user->getEffectiveGroups();
		if (in_array('sysop',$groups)) return true;
    	if( isset( $wgNamespacesWithSubpages[ $title->getNamespace() ] ) && $wgNamespacesWithSubpages[ $title->getNamespace() ] ) 
		{
			$parts = explode( '/', $title->getText(),2 );
			$rootPage=$parts[0];
		}
		else
		{
			$rootPage= $title->getText();
		}
		$parts = explode('-',$user->getName(),2);
		if (count($parts)==1)
		{
		    return $parts[0]==$user->getName();
		}
		else
		{
			$group = $parts[0];
			$parts = explode('-',$rootPage,2);
			return (count($parts)==2 and $parts[0]==$group);
		}
	}
	
	function hook_AbortNewAccount($user, $message) 
	{
	  global $wgUser;
	  $groups=$wgUser->getEffectiveGroups();
	  if (in_array('sysop',$groups)) return true;
	  $message=wfMsg('noname'); 
	  return (strpos($user->getName(),'-') === false);
	}

	function extractFromSource()
	{
		$text = $this->article->getContent(true);
		$parts=preg_split('/(<editform.*?>\s*)(.*?)(\s*<\/editform>)/msi',$text,-1,PREG_SPLIT_DELIM_CAPTURE);
		$content = $parts[$this->editform*4-2];
		$params = explode('\|',$content,2);
		if (count($params)<1) return false;
		
		$this->template=trim($params[0]);

		$this->params="";
		$this->nowiki="";
		$parts=explode("|",$params[1]);
		foreach($parts as $part)
		{
			$p = explode("=",$part,2);
			if(count($p)==2)
			{
				$this->nowiki.="\n|".trim($p[0]).'=<nowiki>'.trim($p[1]).'</nowiki>';
				$this->params.="\n|".trim($p[0]).'='.trim($p[1]);
			}
		}
	}	

	function extractFromBoth()
	{
		if (!$this->editform=='new')
		{
			$this->extractFromCGI();
			return;
		}
		global $wgRequest;
		$cgiparams=$wgRequest->getArray('editform_data');
		$text = $this->article->getContent(true);
		$parts=preg_split('/(<editform.*?>\s*)(.*?)(\s*<\/editform>)/msi',$text,-1,PREG_SPLIT_DELIM_CAPTURE);
		$content = $parts[$this->editform*4-2];
		$params = explode('\|',$content,2);
		if (count($params)<1) return false;
		
		$this->template=trim($params[0]);

		$this->params="";
		$this->nowiki="";
		$parts=explode("|",$params[1]);

		foreach($parts as $part)
		{
			$p = explode("=",$part,2);
			if(count($p)==2)
			{
				if (isset($cgiparams[$p[0]]))
				{
					$v=$cgiparams[$p[0]];
					if (is_array($v))
					{
				        $v=join(',',array_keys($v));
					}
				}
				else
				{
					$v=$p[1];
				}	
				$this->nowiki.="\n|".trim($p[0]).'=<nowiki>'.trim($v).'</nowiki>';
				$this->params.="\n|".trim($p[0]).'='.trim($v);
			}
		}
	}	


	function extractFromCGI()
	{
		global $wgRequest;
		$this->template=$wgRequest->getText('editform_template');
		$params=$wgRequest->getArray('editform_data');
		$this->params="";
		$this->nowiki="";
		if($params)
		{
			foreach($params as $k=>$v)
			{
			    if (is_array($v))
				{
				    $v=join(',',array_keys($v));
				}
				$this->params.="\n|".trim($k)."=".trim($v);
				$this->nowiki.="\n|".trim($k)."=<nowiki>".trim($v)."</nowiki>";
			}
		}
	}	
	
	function showDisplay()
	{
		global $wgOut;
		$wgOut->addWikiText('{{'.$this->template.'|#action=display'.$this->params.'}}');
	}
	
	function showEdit()
	{
		global $wgOut;
		global $wgRequest;
		global $wgTitle;
		$wgOut->addHTML(  '<form method="post" enctype="multipart/form-data" id="editform_form" name="editform_form" action="'. $wgTitle->getFullURL($this->passthrough).'">'
#						. '<input name="uselang" type="hidden"' . $wgRequest->getText('uselang') . '>'
						. '<input name="action" type="hidden" value="editform">'
						. '<input name="editform" type="hidden" value="' . $this->editform . '">'
						. '<input name="editform_template" type="hidden" value="'.$this->template.'">'
						);
		
		$formTitle = Title::makeTitleSafe(NS_TEMPLATE_FORM, $this->template);
#		$wgOut->addWikiText($formTitle->getArticleId());
		if ($formTitle && $formTitle->getArticleId())
		{
			$wgOut->addWikiText('{{Template form:'.$this->template.$this->params.'}}');
		
			$wgOut->addHTML(  ''
	#						. '<small>komentar k spremembi:<br></small> 
	#						. '<input name="editform_summary" type="text" style="width:95%" value="'.$wgRequest->getText('editform_summary') . '"><br>'

							. '<input name="editform_cancel" type="submit" value="prekliči"> '
	#						. '<input name="editform_preview" type="submit" value="preview">'
							. '<input name="editform_save" type="submit" value="shrani">'
	#						. '<input name="editform_delete" type="submit" value="delete">'
							);
			$wgOut->addHTML('</form>');
		}
		else
		{
			$wgOut->addWikiText('[[Template form:'.$this->template.']]');
		}
	}
	
	function saveForm()
	{
		global $wgParser;
		global $wgOut;
		global $wgRequest;
		global $wgTitle;

		$saveTitle = Title::makeTitleSafe(NS_TEMPLATE_SAVE, $this->template);
		if (!$saveTitle->getArticleId())
		{
			$text = $this->template.$this->params;
		}
		else
		{
			$source='{{Template save:'.$this->template . $this->params.'}}';
			$text=trim($wgParser->mStripState->unstripBoth($wgParser->replaceVariables($source)));

			$text=preg_replace('/&lt;save&gt;/','<save>',$text);
			$text=preg_replace('/&lt;\/save&gt;/','</save>',$text);
			$text=preg_replace('/^[\s\S]*?(<save>|$)/','',$text);
			$text=preg_replace('/<\/save>[\s\S]*?<save>/','',$text);
			$text=preg_replace('/<\/save>[\s\S]*?$/','',$text);
			$text=$this->template.$text;
		}
			
		$cur = $this->article->getContent(true);

		$newComment = $wgRequest->getText( 'editform_summary');

		$aid = $wgTitle->getArticleID( GAID_FOR_UPDATE );
		if ($this->editform=='new' && $aid)
		{
			$new=$cur . "<editform>".$text."</editform>";
			$newComment = $newComment ? $newComment : "new form ({{".$this->template."}})";
		}
		elseif ($aid)
		{
			$parts=preg_split('/(<editform.*?>\s*)(.*?)(\s*<\/editform>)/msi',$cur,-1,PREG_SPLIT_DELIM_CAPTURE);
			$parts[$this->editform*4-2]=$text;
			$new=join('',$parts);
			$newComment = $newComment ? $newComment : "edit form " . $this->editform . " ({{".$this->template."}})";
		}
		else
		{
			$new="<editform>".$text."</editform>";
		}
#			$notify='save';

		if ( 0 == $aid ) {
			// Late check for create permission, just in case *PARANOIA*
			if ( !$wgTitle->userCan( 'create' ) ) {
				$this->noCreatePermission();
				return;
			}
			$this->article->doEdit( $new, $newComment );
			$notify='new';
		}
		elseif($cur!=$new);
		{
			$this->article->doEdit( $new, $newComment );
			$notify='save';
		}
		if ($wgRequest->getText('wpUpload') && $wgRequest->getFileName( 'wpUploadFile' ))
		{
			global $IP;
			require_once( "$IP/includes/SpecialUpload.php" );
			$this->deleteThumbnails($wgRequest->getText('wpDestFile'));
			wfSpecialUpload();
		}
	}
	
	
	function showConfirmDelete()
	{
		global $wgOut;
		global $wgTitle;
		$wgOut->addHTML(  '<form name="editform_form" action="'. $wgTitle->getFullURL($this->passthrough).'">Res želite zbrisati ta obrazec?<br>'
						. '<input name="action" type="hidden" value="editform">'
						. '<input name="editform" type="hidden" value="' . $this->editform . '">'
						. '<input type="submit" name="editform_confirmdelete" value="da">'
						. '<input type="submit" name="editform_cancel" value="ne">'
						. '</form>'
						);
	}
	
	function deleteForm()
	{
		global $wgParser;
		global $wgOut;
		global $wgRequest;

		$cur = $this->article->getContent(true);
		$parts=preg_split('/(<editform.*?>\s*)(.*?)(\s*<\/editform>)/msi',$cur,-1,PREG_SPLIT_DELIM_CAPTURE);
		$parts[$this->editform*4-1]="";
		$parts[$this->editform*4-2]="";
		$parts[$this->editform*4-3]="";
		$new=join('',$parts);
		$newComment = $wgRequest->getText( 'editform_summary');
		$newComment = $newComment ? $newComment : "delete form " . $this->editform . "(" . $this ->template. ")";
		$this->article->updateArticle( $new, $newComment, 1, $this->article->mTitle->userIsWatching(), false);
		global $wgOut;
		$wgOut->addHTML("<script>top.alert('changed $name')</script>");
		$title=Title::newFromText('special:GeoNotify');
		$wgOut->redirect($title->getFullURL( 'notify=save&redir_title='. $this->article->mTitle->getPrefixedDBkey()));
	}

	function fl_input(&$parser,&$frame,&$inArgs) 
    {
        return $this->fl_control($parser, $frame, $inArgs, false);
    }

    function makeOption($cgiName, $id, $v,$d,$value,$type)
    {
        switch ($type)
        {
        case 'palette':
            $sel = ($v === $value || $v==$value && $v) ? " selected" : '';
            return "<option value=\"$v\" style=\"background:$d;color:$d\"$sel>$d</option>";        
        case 'select':
            $sel = ($v === $value || $v==$value && $v) ? " selected" : '';
            return "<option value=\"$v\"$sel>$d</option>";
        case 'radio':            
			$chk = ($v === $value || $v==$value && $v) ? " checked" : '';
	        $cls = ($v === $value || $v==$value && $v) ? ' class="opened"' : '';
			return "<span class=\"control-radiobutton-wrap\"><label for=\"$id-$v\"$cls><input id=\"$id-$v\"  type=\"radio\" class=\"control radio\" name=\"$cgiName\" value=\"$v\" $chk/>$d</label></span>";
		case 'toggles':
			$chk = isset($value[$v]) ? " checked" : '';
	        $cls = isset($value[$v]) ? ' class="opened"' : '';
			return "<label for=\"$id-$v\"$cls><input id=\"$id-$v\" type=\"checkbox\" class=\"control toggle\" name=\"{$cgiName}[$v]\" $chk/>$d</label>";
		}
    }

	function fl_control(&$parser,&$frame,&$inArgs,$arrayName='editform_data') 
	{
		$args=new XxxArgs($frame,$inArgs);
		global $wgUser;
		global $wgRequest;
		
		switch ($args->command)
		{
		case 'text':
		    $rows = 1;
		    $cols = 40;
		    if ($args->count >= 4)
		    {
		        $cols = (int)$args->trimExpand(4);
		    }
		    if ($args->count >= 3)
		    {
		        $rows = (int)$args->trimExpand(3);
		    }
		    
		case 'hidden':
		case 'submit':
		case 'checkbox':
		case 'select':
		case 'palette':
		case 'radio':
		case 'fieldset':
		case 'toggles':
		case 'password':
		case 'custom':
	        if ($args->isNamed(1))
	        {
	            $name=$args->getKey(1);
	            $label=$args->trimExpandValue(1);
	        }
	        else
	        {
			    $name = $args->trimExpand(1);
			    $label = $name;
			}
#			$default = $args->cropExpand(2,'');

			$value = trim($frame->getArgument($name));
			if (!$value) $value = $args->cropExpand(2,false);

#			if (!$value) $value = $default;
            if ($arrayName)
            {
			    $cgiName='editform_data['.htmlspecialchars($name).']';
			    $id='editform-data-'.htmlspecialchars($name);
            }
            else
            {
			    $cgiName=htmlspecialchars($name);
			    $id='control-'.htmlspecialchars($name);
            }
			$escValue=htmlspecialchars($value);
			break;
		case 'edit':
		default:
		}

		switch ($args->command)
		{
		case 'fieldset':
	        return array
	        (
			    "<fieldset id=\"$id\"><legend>$label</legend>$value</fieldset>",
	            "noparse"=>true,
	            "isHTML"=>true
	        );
		    
		case 'hidden':
			return array
			(
			    "<input class=\"hidden\" type=\"hidden\" id=\"$id\" name=\"$cgiName\" value=\"$escValue\"/>", 
			    "noparse"=>true,
			    "isHTML"=>true
			);

		case 'custom':
			return array
			(
			        "<div id=\"field-$id\" class=\"field field-custom\">".
			        "<label for=\"$cgiName\"  class=\"label custom-label\">$label</label>".
			        "<input class=\"control\" type=\"hidden\" id=\"$id\" name=\"$cgiName\" value=\"$escValue\" />".
			        $parser->mStripState->unstripBoth($args->cropExpand(3)).
			        "</div>", 
			        "noparse"=>true,
			        "isHTML"=>true
			);
		case 'submit':
			return array
			(
			    "<div id=\"field-$id\" class=\"field field-submit\">".
			    "<div class=\"field-wrap\"><div class=\"field-inner\">".
			    "<label for=\"$cgiName\" class=\"label submit-label\">$label</label>".
			    "<input class=\"control submit\" type=\"submit\" id=\"$id\" name=\"$cgiName\" value=\"$escValue\"/>".
			    "</div></div></div>", 
			    "noparse"=>true,
			    "isHTML"=>true
			);
		case 'password':
			    return array
			    (
			        "<div id=\"field-$id\" class=\"field field-text field-password\">".
			        "<label for=\"$cgiName\"  class=\"label password-label\">$label</label>".
			        "<input class=\"control password text\" type=\"password\" id=\"$id\" name=\"$cgiName\" value=\"$escValue\" />".
			        "</div>", 
			        "noparse"=>true,
			        "isHTML"=>true
			    );
		case 'text':
		    if ($rows==1)
		    {
			    return array
			    (
			        "<div id=\"field-$id\" class=\"field field-text\">".
			        "<label for=\"$cgiName\"  class=\"label text-label\">$label</label>".
			        "<input class=\"control text\" type=\"text\" id=\"$id\" name=\"$cgiName\" value=\"$escValue\" />".
			        "</div>", 
			        "noparse"=>true,
			        "isHTML"=>true
			    );
            }
            else
            {
			    return array
			    (
			        "<div id=\"field-$id\" class=\"field field-textarea\">".
			        "<label for=\"$cgiName\"  class=\"label text-label\">$label</label>".
			        "<textarea class=\"control textarea\" id=\"$id\" rows=\"$rows\" cols=\"$cols\" name=\"$cgiName\">$escValue</textarea>".
			        "</div>", 
			        "noparse"=>true,
			        "isHTML"=>true
			    );
                
            }
		case 'checkbox':
			return array
			(
			    "<div id=\"field-$id\" class=\"field field-checkbox\">".
			    "<label for=\"$cgiName\" class=\"label checkbox-label\">$label</label>".
			    "<input class=\"control checkbox\"  type=\"checkbox\" id=\"$id\" name=\"$cgiName\" value=\"1\"" . ($value?" checked " :"").  ">".
			    "</div>",  
			    "noparse"=>true,
			     "isHTML"=>true
			 );
		case 'select':
		case 'radio':
		case 'toggles':
		case 'palette':
			$selected = '';
		    $xvv =& XxxInstaller::getExtension('Xvv');
		    
			$s= "<div id=\"field-$id\" class=\"field field-{$args->command}\">".
			    "<label for=\"$cgiName\" class=\"label select-label\">$label</label>";
			switch ($args->command)
			{
		    case 'select':
		        $s .= "<select class=\"control palette\" id=\"$id\" name=\"$cgiName\">";
		        break;
		    case 'palette':
		        $s .= "<select class=\"control select\" id=\"$id\" name=\"$cgiName\">";
		        break;
		    case 'radio' : 
		        $s.="<div class=\"control radiogroup\" id=\"$id\">";
	            break;
	        case 'toggles':
	            $s.="<div class=\"control togglegroup\" id=\"$id\">";
	            if ($xvv->arrExists($value)) $value=$xvv->getArray($value);
	            else $value = array();
	        }	
			for($i = 3;$i<=$args->count;$i++)
			{
			    
			    if ($xvv->arrExists($arrayId = $args->trimExpand($i)))
			    {
			        $arr = $xvv->getArray($arrayId);
			        foreach ($arr as $key=>$arrayId2)
			        {
			            if ($xvv->arrExists($arrayId2))
			            {
			               $arr2 = $xvv->getArray($arrayId2);
			               $v = $arr2['value'];
			               $d = isset($arr2['display']) ? $arr2['display'] : $v;
			            }
			            else
			            {
			                $v = $key;;
			                $d = $arrayId2;
			            }
			            $s.=$this->makeOption($cgiName,$id,$v,$d,$value,$args->command);
				        $selected = ($v === $value) ? $v : $selected;
			        }
			    }
			    else
			    {
				    $d = $args->cropExpandValue($i);
				    $v = $args->isNamed($i) ? $args->getName($i) : trim($d);
				    $s.=$this->makeOption($cgiName, $id, $v,$d,$value,$args->command);
				    $selected = ($v === $value) ? $v : $selected;		
			    };
			}
			switch ($args->command)
			{
			case 'select':
			case 'palette':
			    $s.="</select>";
			    break;
			case 'radio':
			case 'toggles':
			    $s.="</div>";
			    break;			
			}
			$s.="</div>";
			return array(0=>$s, "noparse" => true, "isHTML"=>'true');
		case 'lselect':
			$name=$parser->mStripState->unstripBoth(array_shift($args));
			$value=$parser->mStripState->unstripBoth(array_shift($args));
			$s="<select name=\"editform_data[$name]\">";
			$options=join("|",$args);
			$parts = explode("\|",$options);
			$redlinks='';
			global $wgUser;
			global $wgLang;
			global $wgLanguageCode;
			global $wgRequest;
			$langCode=$wgLang->mCode == $wgLanguageCode ? '' : '/'.$wgLang->mCode;
			$sk=$wgUser->getSkin();
			foreach($parts as $part)
			{
				$p = explode("=",$part,2);
				if($p[0]!=='')
				{
					$v=$p[0];
					$d=end($p);
					$sel = $v==$value ? "selected" : "";
					$s .= "<option value=\"$v\" $sel>".wfMsg($d)."</option>";
					if ($wgRequest->getText('translate'))
					{
					 	$nt = Title::newFromText( "MediaWiki:{$d}{$langCode}" );
						$redlinks.="<br>".$part . ": ".$sk->makeLinkObj($nt,wfMsg($d));
					}
				}
			}
			$s.="</select>".$redlinks;
			return array(0=>$s, "noparse" => true, "isHTML"=>'true');
		case 'llabel':
			$value=$parser->mStripState->unstripBoth(array_shift($args));
			global $wgUser;
			global $wgLang;
			global $wgLanguageCode;
			global $wgRequest;
			$langCode=$wgLang->mCode == $wgLanguageCode ? '' : '/'.$wgLang->mCode;
			$sk=$wgUser->getSkin();
#			print "|before_$value|";		
			if ($value!='')
			{
				if ($wgRequest->getText('translate'))
				{
				 	$nt = Title::newFromText( "MediaWiki:{$value}{$langCode}" );
					$s .= $value . ": ".$sk->makeLinkObj($nt,wfMsg($value));
				}
				else
				{
					$s .= wfMsg($value);
				}
			}
#			print "|after_$value|$s|";		
			return array(0=>$s, "noparse" => true, "isHTML"=>'true');
		case 'textarea':
			$rows = count($args) ? $parser->mStripState->unstripBoth(array_shift($args)) : 3;
			$cols = count($args) ? $parser->mStripState->unstripBoth(array_shift($args)) : 40;
			$s="<textarea name=\"editform_data[$name]\" rows=\"$rows\" cols=\"$cols\">$value</textarea>";
			return array(0=>$s, "noparse" => true, "isHTML"=>'true');
		case 'new':
			if (!$this->userCanEditForm($wgUser, $parser->mTitle)) return array('');
			$name=$args->trimExpand(1);
			$display=$args->trimExpand(2,"new $name");
			$link.="action=editform&editform_new=true";
			$link.="&editform_template=" .wfUrlEncode($name);

			for($i = 4;$i<=$args->count;$i++)
			{
				$d = $args->cropExpandValue($i);
				$v = $args->isNamed($i) ? $args->getName($i) : trim($d);		
				$link.="&editform_data%5B". wfUrlEncode($p[0]) ."%5D=". $p[1];
			}
			$link=$parser->mTitle->getFullUrl($link);
			$s="<a href=\"$link\">$display</a>";
			return array(0=>"$s", "isHTML"=>true,"noparse"=>true);
		case 'editortools':
			if (!$this->editforms) return array('');
			if (!$this->userCanEditForm($wgUser, $parser->mTitle)) return array('');
			$text=array_shift($args);
			return array(0=>$text);

		case 'edit':
			if (!$this->userCanEditForm($wgUser, $parser->mTitle))   return array('');
			$display= $args->cropExpand(1);
			$link.="action=editform";
			$link.="&editform=".$this->editforms;
#			$link.="&uselang=" .wfUrlEncode($wgRequest->getText('uselang'));
			$link=$parser->mTitle->getFullUrl($link);
			$s="<a href=\"$link\">$display</a>";
			return array(0=>$s, "isHTML"=>true,"noparse"=>true);
		case 'delete':
			if (!$this->userCanEditForm($wgUser, $parser->mTitle)) return array('');
			$name=$parser->mStripState->unstripBoth(array_shift($args));
			$display= $name ? $name : "delete";
			$link.="action=editform";
			$link.="&editform_delete=true";
			$link.="&editform=".$this->editforms;
			$link=$parser->mTitle->getFullUrl($link.'&'.$this->passthrough);
			$s="<a href=\"$link\">$display</a>";
			return array(0=>$s, "isHTML"=>true,"noparse"=>true);
		default:
			return array('notfound'=>true);
		}
	}

	function fn_paramtype(&$parser)
	{
		$args=func_get_args();
		array_shift($args);
		if (count($args)) $type    =  array_shift($args); else return array('notfound'=>true);
		if (count($args)) $name    =  array_shift($args); else return array('notfound'=>true);
		if (count($args)) $default =  array_shift($args); else $default="";
		if (count($args)) $group   =  array_shift($args); else $group = null;
		if (count($args)) $other   = '|'.join('|',$args); else $other='';
		

		global $wgUser;
		$groups=$wgUser->getEffectiveGroups();
		$type=ucfirst($type);
		$args=end($parser->mArgStack);
		$action = isset($args['#action']) ? $args['#action'] : 'display';
		
		if ($action=='edit' && $group && !in_array($group,$groups))
		{
			$action='display';
		}
		$value = isset($args[$name]) ? $args[$name] : $default;
		$s = $parser->replaceVariables("{{value:$type|name=$name|value=$value|action=$action$other}}");
#		$s = $parser->mStripState->unstripBoth($s);
		return array(0=>$s);
	}

    function hook_parserAfterTidy(&$parser, &$text)
    {
        if ($parser->mTitle->getNamespace() == NS_TEMPLATE_FORM)
        {
            $text = "<form action=\"?\">$text$</form>";
        } 
    } 
}
?>
