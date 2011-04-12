<?php
#
#	Preliminaries, look lower for interesting stuff
#
XxxInstaller::Install('XvvCGI');

class XvvCGI extends Xxx
{

 	function fl_cgi(&$parser, $f, $a)
	{
		$parser->disableCache();
		$args=new XxxArgs($f, $a);
		if (isset($_REQUEST[$args->command]))
		{
		    global $wgRequest;
		    $v = $wgRequest->getArray($args->command); if (count($v)==1 && isset($v[0])) $v=$v[0];
		    if (is_array($v)) return XxxInstaller::getExtension('Xvv')->arrMake($v);
		    else return $v;
		}
		return  $args->trimExpand(1, '');
	}
}
