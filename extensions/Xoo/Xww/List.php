<?php
XxxInstaller::Install('XwwList');
 
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

class XwwList extends Xxx {	

  function link($title,$text=NULL,$alt=NULL) {
    if (is_array($title)) {
      $text = $title[1] ? $title[1] : $title[0];
      $alt = $title[2] ? $title[2] : $title[0];
      $title = $title[0];
    } else {
      $text = $text ? $text : $title->getFullText();
      $alt = $alt ? $alt : $title->getFullText(); 
    }
    return  '<a href="' . $title->getFullUrl() 
    .       '" class="link-list"'
    .       '" title="'.$alt.'"'
    .       '>'
    .       ($text ? $text : $title->getFullText())
    .       '</a>';

  }
	function fn_list(&$parser,$which,$a1=NULL,$a2=NULL,$a3=NULL,$a4=NULL) {
    switch($which) {
    case 'special': 
      $items = $this->getSpecials();
      $ret = $this->listGroups($items);
      break;
    case 'recent':
      $titles = $this->getRecent($a1);
      $ret = $this->listPages($titles);
      break;
    default:
      return $this->notFound();
    }
    return array($parser->mStripState->unstripBoth($ret),'isHTML'=>true);
  }
  
  function listPages($titles) {
    $ret = '';
    foreach ($titles as $t) $ret.='<li>' . $this->link($t) ."</li>";
    return $ret;
	}	
  function listGroups($groups) {
    $ret = '';
    foreach ($groups as $name=>$titles) {
      $ret.="<li><span class=\"list-heading\">$name</span><ul>";
      $ret.=$this->listPages($titles);
      $ret.='</ul></li>';
    }
    return $ret;
	}	
  
  static function getRecent($limit) {
    $dbr = wfGetDB( DB_SLAVE );
	  $res = $dbr->query('SELECT DISTINCT * FROM ('. $dbr->selectSQLText( 
	    array('recentchanges' ),
	    array('rc_namespace','rc_title'),
	    array(),
	    __METHOD__,
	    array(
	      'ORDER BY' => 'rc_timestamp DESC',
	      
	    )
	  ).") AS rc LIMIT " . ($limit ? min($limit,200) : 20));
	  $ret = array();
	  foreach ($res as $row) {
	    $ret[] = array(Title::makeTitle($row->rc_namespace, $row->rc_title));
	  }
	  return $ret;
  }
  static function getSpecials() {
		global $wgSortSpecialPages;
		global $wgUser;

		$pages = SpecialPageFactory::getUsablePages( $wgUser );

		if( !count( $pages ) ) {
			# Yeah, that was pointless. Thanks for coming.
			return false;
		}

		/** Put them into a sortable array */
		$groups = array();
		foreach ( $pages as $page ) {
			if ( $page->isListed() ) {
				$group = wfMsg("specialpages-group-".SpecialPageFactory::getGroup( $page ));
				if( !isset( $groups[$group] ) ) {
					$groups[$group] = array();
				}
				$groups[$group][] = array( $page->getTitle(), $page->getDescription() );
			}
		}

		/** Sort */
		if ( $wgSortSpecialPages ) {
			foreach( $groups as $group => $sortedPages ) {
				ksort( $groups[$group] );
			}
		}

		/** Always move "other" to end */
		if( array_key_exists( 'other', $groups ) ) {
			$other = $groups['other'];
			unset( $groups['other'] );
			$groups['other'] = $other;
		}

		return $groups;
	}
}	

