<?php 

$wgTemplateProfiler = new TemplateProfiler;

class TemplateProfiler
{	
	var $active 		= false;		# for hooks to know whether to collect data or not
	var $data 			= array(null);	# for collecting profiling data
	
	var $outputTree		= array();		# tree of profiling data, for output
	var $outputList 	= array();		# summary list, for output
	var $outputSubList 	= array();		# summary sub list, for output
	var $totalTime  	= 0;			# total time, for use in output
	var $maxNetTime 	= 0;			# maximum net time, for use in output
	
	# arguments:
	# profile_show  : templates, all, or dump, defaults to templates
	# profile_min   : smallest time in ms to include in the list, defaults to 10
	# profile_hl    : total, net or dump, defaults to net
	
	var $show = 'templates';
	var $min  = 10;
	var $hl   = 'net';

	function registerHook( $name )
	{
		global $wgHooks;
	 	$wgHooks[ $name ][] = array( &$this , "hook_$name" );
	}

	function __construct()
	{
		$this->registerHook('UnknownAction');
		$this->registerHook('BeforeBraceSubstitution');
		$this->registerHook('AfterBraceSubstitution');
	}
	
################################
#
#   M A I N   F U N C T I O N
#
################################

	function hook_UnknownAction($action, $article)
	{
#		die ($action);
		if ($action!='profile') return true;
		global $wgRequest, $wgOut;
		
		# gather and fix parameters
		$this->min   = (int) $wgRequest->getVal('profile_min','10');
		if ($this->min < 0) $this->min = 0;

		switch ($this->show = $wgRequest->getText('profile_show')) {
			case 'all':	case 'dump': break;
			default: $show='templates';
		}
		
		switch ($this->hl = $wgRequest->getText('profile_hl')) {
			case 'dump': case 'total': break;
			default: $this->hl='net';
		}

		#collect profiling data for this article
		$this->collectData($article);

			$wgOut->addHTML("<style>".file_get_contents(dirname(__FILE__).'/TemplateProfiler.css').'</style>');


		# if we're just dumping, return print_r 
		if ($this->show=='dump')
		{
			$wgOut->addHTML("<pre>".print_r($this->data,true)."</pre>");
			return false; 
		}
		
		# otherwise, we need to extract data for display
		$this->processData();
		# output the results
		$wgOut->addHTML('<div id="template-profiler">');
		$this->outputHeader($article->getTitle());
		$this->outputData();
		$wgOut->addHTML('</div>');
		return false;
	}

################################
#
#   D A T A   C O L L E C T I O N
#
################################

	# profiles an article, by using hooks to collect times on braceSubstitution
	function collectData(&$article)
	{
		global $wgParser, $wgUser, $wgRequest;

		#get title for later use
		$title = $article->getTitle();

		# prepare root node
		$this->data = array
		(
			'parent' => null,
			'title' => ':'.$title->getFullText(),
			'children' => array()
		);

		# prepare the parser
		$parser =& $wgParser;
		$this->active = true;
		$options = ParserOptions::newFromUser($wgUser);

		#get text
		$text = $article->getContent();

		#parse the page, recording times for the root node 		
		$this->data['start']= microtime(true);
		$parser->parse($article->getContent(), $title, $options, false);		
		$this->data['end']= microtime(true);
	}


	function hook_BeforeBraceSubstitution(&$parser,$titleText,&$args)
	{
		# work only if active
		if (!$this->active) return true;
		$node = array
		(
			'parent' => &$this->data,
			'start' => microtime(true),
			'title' => $titleText,
			'children' => array()
		);

		$this->data['children'][] =& $node;
		$this->data =& $node;
		
		return true;
	}
	
	function hook_AfterBraceSubstitution(&$parser,$titleText)
	{
		if (!$this->active) return true;
		$end = microtime(true);
		$this->data['end']= $end;
		$this->data =& $this->data['parent'];
		return true;
	}


###################################
#
#   D A T A   P R O C E S S I N G
#
###################################

	# process collected data, producing $this->outputList and $this->outputTree
	function processData(&$node=null)
	{
		if ($node===null)
		{
			$initial=true;
			$this->outputList=array();
			$this->outputSubList=array();
			$this->maxNetTime=0;
			$theNode =& $this->data;
			$this->totalTime = $this->data['end']-$this->data['start'];
		}
		else
		{
			$initial=false;
			$theNode =& $node;
		}
		
		$newNode = array
		(
			'title'    => $theNode['title'],
			'children' => array(),
			'time'     => $theNode['end'] - $theNode['start'],
			'nettime'  => $theNode['end'] - $theNode['start']
		);
		foreach ($theNode['children'] as &$child)
		{
			$childTime = $child['end'] - $child['start'];
			
			if ($this->show='all' || $this->isTemplate($child['title']))
			{
				$childNode = $this->processData($child);
				
				if ($childTime >= $this->min/1000)
				{
					
					$newNode['children'][] = $childNode;
					$newNode['nettime'] -= $childTime;
				}
				else
				{
					$newNode['nettime'] -= $childTime;
				    $newNode['children'][-1]['nettime']+=$childTime; 
				    $newNode['children'][-1]['time']+=$childTime; 
				    $newNode['children'][-1]['title']="{other}"; 
				}
				
				list ($group,$item)=$this->splitTitle($child['title']);
#				print ("$group,$item," . $childTime - $childNode['nettime'] . "<br>");
				
				$this->outputSubList[$group][$item]['time']+=$childTime;
				$this->outputSubList[$group][$item]['nettime']+=$childNode['nettime'];
				$this->outputSubList[$group][$item]['count']++;
				$this->outputList[$group]['time']+=$childTime;
				$this->outputList[$group]['nettime']+=$childNode['nettime'];
				$this->outputList[$group]['count']++;
				if ($childNode['nettime'] > $this->maxNetTime) $this->maxNetTime = $childNode['nettime'];
			}
		}
		if (!$initial) return $newNode;
		$this->outputTree=$newNode;
		return true;
	}

	# is the title a variable?
	function isVariable($titleText)
	{
		global $wgParser;
		return $wgParser->mVariables->matchStartToEnd($titleText);
	}

	# is the title a function?
	function isFunction($titleText)
	{
		global $wgParser;
		$parts = explode(':',$titleText,2);
		return	(
			count($parts) == 2 
			&&	(
				isset( $wgParser->mFunctionSynonyms[0][strtolower($parts[0])] ) 
				||	
				isset( $wgParser->mFunctionSynonyms[1][$parts[0]] )
			)
		);
	}

	# is the title a template?
	function isTemplate($titleText)
	{
		return !($this->isVariable($titleText) || $this->isFunction($titleText) || !Title::newFromText($titleText));
	}

	# split 
	function splitTitle($titleText)
	{
		if ($this->isVariable($titleText)) return array ('variables',$titleText);

		if ($this->isFunction($titleText))
		{
			return explode(':',$titleText,2);
		}
		
		$title = Title::newFromText($titleText,NS_TEMPLATE);
		return array($title->getNsText(),$title->getText());
	}

###################################
#
#   O U T P U T
#
###################################

	function outputHeader($title)
	{
		global $wgOut;
		
		$wgOut->addHTML
		(
			'show: <a href="' . $title->getFullUrl('action=profile&profile_min=0') . '">all</a>'
			.' &middot; <a href="' . $title->getFullUrl('action=profile&profile_min=10') . '">over 10 ms</a>'
			.' &middot; <a href="' . $title->getFullUrl('action=profile&profile_min=100') . '">over 100 ms</a>'
		);
	}
	
	function outputData()
	{
		global $wgOut;

		if ($this->hl=='dump')
		{
			$wgOut->addHTML("<pre>".print_r(array('tree'=>$this->outputTree,'list'=>$this->outputList),true)."</pre>");
			return; 
		}
		$this->outputTreeData();
		$this->outputListData();
	}
	
	function outputTreeData()
	{
		global $wgOut;

		$text = $this->outputTreeNode($this->outputTree);
		$wgOut->addHTML('<ul>');
		$wgOut->addWikiText($text);
		$wgOut->addHTML('</ul></div>');
		return false;
	}
	

	function outputTreeNode(&$node)
	{
		$time = $node['time'];
		$nettime = $node['nettime'];
		
		$shade = (int)($nettime*1000);
		if ($shade>50) $shade=50;
		$shade = (50-$shade)/5;
		$shade=$shade*$shade;
		$shade = (int)(100-$shade);
		$color = "rgb($shade,$shade,0)";
						
		$ret = '<li style="background:'.$color.'" class="profiler-treeitem">';
		
		$ret .= '<span class="profiler-time">' . ($time<.001 ? (int)($time*10000)/10 : (int)($time*1000)) .'</span>';
		$ret.= '<span class="profiler-nettime">' . ($nettime<.001 ? (int)($nettime*10000)/10 : (int)($nettime*1000)) .'</span>';
		$ret.= '<span class="profiler-title">';
		
		$ret.= htmlspecialchars(substr($this->getTitle($node['title']),0,50));
		$ret.='</span>';
		if (count($node['children']))
		{
			$ret.='<ul>';
			foreach ($node['children'] as &$child)
			{
				$ret.=$this->outputTreeNode($child,$minTime);
			}
			$ret.='</ul>';
		}
		return $ret;
	}

	function compareNetTime(&$l,&$r)
	{
		if ($l['nettime']>$r['nettime']) return -1;
		elseif ($l['nettime']<$r['nettime']) return 1;
		else return 0;
	}

	function outputListData()
	{
		global $wgOut;
		$wgOut->addHTML("<table id=\"profiler-list\"><tr id=\"profiler-listheader\"><th>group / item</th><th>count</th><th colspan=\"2\">net time (ms)</th><th colspan=\"2\">total time (ms)</th></th></tr>");
		uasort($this->outputList,array($this,'compareNetTime'));
		foreach ($this->outputList as $groupName=>$group)
		{
			if ($group['time']>=$this->min/1000)
			{
				$wgOut->addHTML("<tr class=\"profiler-listgroup\"><th>".($groupName?$groupName:'(main)').":</th><td>${group['count']}</td><td>"
								.(int)($group['nettime']*1000)
								."</td><td>"
								.(int)($group['nettime']*1000/$group['count'])
								."</td><td>"
								.(int)($group['time']*1000)
								."</td><td>"
								.(int)($group['time']*1000/$group['count'])
								."</td></tr>"
								);
				uasort($this->outputSubList[$groupName],array($this,'compareNetTime'));
				foreach ($this->outputSubList[$groupName] as $itemName=>$item)
				{
					if ($item['time']>=$this->min/1000)
					{
						$wgOut->addHTML("<tr class=\"profiler-listitem\"><th>" . htmlspecialchars(substr($itemName,0,50)) . "</th><td>${item['count']}</td><td>"
										.(int)($item['nettime']*1000)
										."</td><td>"
	    							.(int)($item['nettime']*1000/$item['count'])
  									."</td><td>"
										.(int)($item['time']*1000)
										."</td><td>"
	    							.(int)($item['time']*1000/$item['count'])
										."</td></tr>");	
					}
				}
			}
		}
		$wgOut->addHTML("</table>");
		return; 
	}

	function getTitle($titleText)
	{
		if (!$this->IsTemplate($titleText)) return $titleText;
		$title = Title::newFromText($titleText,NS_TEMPLATE)->getFullText();
		return "[[$title]]";
	}
}	

