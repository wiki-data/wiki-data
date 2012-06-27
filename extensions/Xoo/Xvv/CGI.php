<?php
#
#	Preliminaries, look lower for interesting stuff
#
XxxInstaller::Install('XvvCGI');

class XvvCGI extends Xxx {
 	function fl_cgi(&$parser, $f, $a)	{
      global $wgRequest;
		$args=new XxxArgs($f, $a);
	   if (!is_numeric($args->command)) $parser->disableCache();
		if ($wgRequest->getVal($args->command)!==null)	{
		    $v = $wgRequest->getArray($args->command); 
		    if (count($v)==1 && isset($v[0])) return $v;
		    else return XxxInstaller::getExtension('Xvv')->arrMake($v);
		}
		return  $args->trimExpand(1, '');
	}
}
