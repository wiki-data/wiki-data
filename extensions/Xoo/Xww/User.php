<?php
XxxInstaller::Install('XwwUser');

#
#  XwwUser - Xoo World of Wiki - User
#
#  Part of Xoo (c) 1997-2008 [[w:en:User:Zocky]], mitko.si
#	GPL3 applies
#
#########################################################################



#
# 	An extension for Mediawiki that allows casting of values to types
# 
# 	USAGE:
#	{{#user:function}}  				for the current user
#	{{#user:function|user name}} 	for any user
#
#																{{LOGGEDIN}}
#	{{#user:registered|zocky}}
#	{{#user:name|zocky}}      							{{USERNAME}}
#	{{#user:id|zocky}}									{{USERID}}
#	{{#user:realname|zocky}}  							{{USERREALNAME}}
#	{{#user:ismember|zocky|admin}} 					{{ISMEMBER:admin}}
#	{{#user:isadmin|zocky}}								{{ISADMIN}}
#	{{#user:isdevel|zocky}}								{{ISDEVEL}}
#	{{#user:isallowed|zocky|userrights}}			{{ISALLOWED:userrights}}
#	{{#user:can|zocky|delete}}							{{USERCAN:delete}}
#	{{#user:can|zocky|delete|Glavna stran}}		{{USERCAN:delete|Glavna stran}}
#
#
#########################################################################

class XwwUser extends Xxx
{	
	
	function fn_user(&$parser, $command="", $user=null, $arg1=null,$arg2=null)
	{
		global $wgUser;
		if(!$user)
		{
			$user=$wgUser;
		}
		else
		{
			$user=User::newFromName($user);
			if (!$user)
			{
				return array('found'=>false);
			}
			elseif (!$user->getID())
			{
				return array(0=>'');
			}
		}

		$uid=$user->getID();
		switch($command)
		{
		case 'registered':
			return 'REGISTERED';
		case 'id':
			return $uid;
		case 'name':
			return $user->getName();
		case 'namee':
			return wfUrlEncode($user->getName());
		case 'realname':
			return $user->getRealName();
		case 'realnamee':
			return $user->getRealName();
		case 'isallowed':
			if (!$arg1) return array('found'=>false);
			return $user->isAllowed($arg1) ? 'ISALLOWED' : '' ;
		case 'can':
			if (!$arg1) return $this->notFound();
			if (!$arg2) $title=$parser->mTitle;
			else $title=Title::newFromText($arg2);
			if (!$title) return $this->notFound();
			return $title->userCan($arg1) ? 'USERCAN' : '';
		case 'ismember':
			if (!$arg1) return array('found'=>false);
			$groups=$user->getEffectiveGroups();
			return in_array($arg1,$groups) ? 'ISMEMBER' : '';
/* TODO: convert to work with arrays			
		case 'groups':
			$groups=$user->getGroups();
			$sep = $arg1 ? $arg1 : ';'; 
			return array(join($sep,$groups));
		case 'forgroups':
			$groups=$user->getGroups();
			if (!$arg1) return array('found'=>false);
			$template=$arg1;
			$ret='';
			foreach($groups as $g)
			{
				$ret.= '{{'.$arg1.'|1='.$g.'}}';
			}
			return array($ret);
*/			
		case 'isadmin':
			$groups=$user->getEffectiveGroups();
			return in_array('sysop',$groups) ? 'ISADMIN' : '';
		case 'isdevel':
			$groups=$user->getEffectiveGroups();
			return in_array('devel',$groups) ? 'ISDEVEL' : '';
		}
		return array('found'=>false);
	}

	function var_USERNAME(&$parser)
	{
		return $this->fn_user($parser, 'name');
	}

	function var_USERNAMEE(&$parser)
	{
		return $this->fn_user($parser, 'namee');
	}

	function var_USERREALNAME(&$parser)
	{
		return $this->fn_user($parser, 'realname');
	}
	
	function var_USERREALNAMEE(&$parser)
	{
		return $this->fn_user($parser, 'realnamee');
	}
	
	function fx_ISALLOWED(&$parser,$action=null)
	{
		return $this->fn_user($parser, 'isallowed',null,$action);
	}

	function fx_USERCAN(&$parser,$action=null, $title=null)
	{
		return $this->fn_user($parser, 'can', null, $action, $title);
	}


	function fx_ISMEMBER(&$parser,$group=null)
	{
		return $this->fn_user($parser, 'ismember',null,$group);
	}
/*
	function fx_GROUPS(&$parser,$separator=null)
	{
		return $this->fn_user($parser, 'groups',null,$separator);
	}
	function fx_FORGROUPS(&$parser,$template=null)
	{
		return $this->fn_user($parser, 'forgroups',null,$template);
	}
*/

	function var_USERID(&$parser)
	{
		global $wgUser;
		return $this->fn_user($parser, 'id');
	}
	function var_ISADMIN(&$parser)
	{
		global $wgUser;
		return $this->fn_user($parser, 'isadmin');
	}

	function var_ISDEVEL(&$parser)
	{
		return $this->fn_user($parser, 'isdevel');
	}

	function var_LOGGEDIN(&$parser)
	{
		global $wgUser;
		return $wgUser->isLoggedIn()?'LOGGEDIN':'';
	}
}
