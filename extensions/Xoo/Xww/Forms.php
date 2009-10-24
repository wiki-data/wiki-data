<?php
XxxInstaller::Install('XwwForms');

#
#  XwwForms - Xoo World of Wiki - Forms
#
#  Part of Xoo (c) 1997-2008 [[w:en:User:Zocky]], mitko.si
#	GPL3 applies
#
#
#	Wikivariables and parser functions for dealing with forms
#
############################################################################

class XwwForms extends Xxx
{	
	function fl_input(&$parser,&$F,&$A)
	{
		$args=new XxxArgs ($F,$A);
		$name=$args->getKey(1);
		$id=$name;
		$value=$args->cropExpandValue(1);
		
		$style='';
		$class='';
		$cols=40;
		$rows=25;
		$options=array();
		
		foreach ($args->args as $i)
		{
			if ($args->isNamed($i) && $this->removePrefix($args->getKey($i),'#'))
			{
				switch ($args->getKey($i))
				{
					case 'style': $style ='style="' . $args->trimExpandValue($i) . '"'; break;
					case 'class': $class ='class="' . $args->trimExpandValue($i) . '"'; break;
					case 'id'   : $id    = $args->trimExpandValue($i); break;
					case 'rows' : $rows  = $args->trimExpandValue($i); break;
					case 'cols' : $cols  = $args->trimExpandValue($i); break;
				}
			}
			else
			{
				if ($args->isNamed($i))
				{
					$options[$args->getKey($i)]= $args->cropExpandValue($i);
				}
				else
				{
					$options[$args->cropExpandValue($i)]= $args->cropExpandValue($i);
				}
			}
		}
		switch($command)
		{
			case 'hidden':
				$r = "<input type=\"text\" $style $class name=\"$name\" id=\"$id\" size=\"$cols\" value=\"$value\" />";
				break;
			case 'text';
				$r = "<input type=\"text\" name=\"$name\" id=\"$id\" value=\"$value\"/>";
				break;
			case 'select':
				$r = "<select $style $class name=\"$name\" id=\"$id\" size=\"$rows\">";
				foreach ($options as $k=>$v)
				{
					$r.="<option value=\"$k\"" . ($k==$value ? 'selected' : '') .">$v</option>";
				}
				$r .="</select>"; 
				break;
			case 'radio':
				$r = "<div $style $class id=\"$id\" >";
				foreach ($options as $k=>$v)
				{
					$r.="<label for=\"$id-$v\" ($k==$value ? 'class=\"opened\"' : '')><input name=\"$name\" id=\"$id-$v\" type=\"radio\" value=\"$k\"" . ($k==$value ? 'checked' : '') ."> $v</label>";
				}
				$r .="</div>"; 
				break;
			case 'textarea':
				$r = "<textearea $style $class name=\"$name\" id=\"$id\" cols=\"$cols\" rows=\"$rows\">$value</textarea>";
				break;
		}
	}
}	

