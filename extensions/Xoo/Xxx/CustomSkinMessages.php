<?php
$messages = array();

$messages['en'] = array(
	'custom-skin'                            => <<<END
__NOTOC__ __NOEDITSECTION__{{#eval:html
|<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{{LANG}}}"  {{#if:{{LOGGEDIN}}|style="margin-top:20px"}}>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{#skin:title}}</title>
    <link rel="icon" href="/static/icon19.png" type="image/png"/>
    <link rel="shortcut icon" href="/static/icon19.png" type="image/png"/>    
    {{{HEADLINKS}}}
{{#if:{{LOGGEDIN}}|
    <script type="text/javascript" src="/w/skins/common/wikibits.js"><!-- wikibits js --></script>
    <link rel="stylesheet" type="text/css" href="/w/index.php?title=mediawiki:Custom-tools.css&action=raw&ctype=text/css&usemsgcache=true">
}}
    <link rel="stylesheet" type="text/css" href="/w/index.php?title=mediawiki:Custom-skin.css&action=raw&ctype=text/css">
    <script type="text/javascript" src="/static/jquery.min.js"><!-- jQuery --></script>
    <script type="text/javascript" src="/w/index.php?title=mediawiki:Custom-skin.js&action=raw&ctype=text/js"><!-- custom-skin.js --></script>
  </head>
  <body class="xedit-fullscreen" onload="
            document.addEventListener && document.addEventListener('dblclick',function(ev) {
              if(ev.pageX + ev.pageY<20) top.location.href='/Special:UserLogin';
            },false);
          ">
    <div id="canvas">
      <header>{{MediaWiki:Custom-header}}</header>
      <h1>{{SUBPAGENAME}}</h1>
      <div id="main">
        <div id="content-wrap">
          <div id="content">{{{CONTENT}}}</div>
        </div><div id="sidebar-wrap">
          <div id="sidebar">{{MediaWiki:Custom-sidebar}}</div>
        </div>
      </div>
      <footer>{{MediaWiki:Custom-footer}}</footer>
    </div>
    {{#if:{{LOGGEDIN}}|{{Mediawiki:Custom-tools}}}}
  </body>
</html>}}
END
, 'custom-tools' => <<<END
<noinclude><div style="position:relative;width:100%;height:200px"></noinclude><div id="wiki-tools" <noinclude>style="position:absolute"</noinclude>>
{{#ifeq:{{NAMESPACE}}|Special
| {{Mediawiki:custom-menu
  |id=menu-page
  |<b>{{FULLPAGENAME}}</b>
  |{{#title:link|{{FULLPAGENAME}}||Reload {{FULLPAGENAME}}}}
  }}
| {{Mediawiki:custom-menu
  |id=menu-page
  |<b>{{FULLPAGENAME}}</b>
  | {{#if:{{#cgi:oldid|}}
    | {{#title:link|{{FULLPAGENAME}}|action=edit|oldid={{#cgi:oldid}}|Edit this old revision}}
    | {{#title:link|{{FULLPAGENAME}}|action=edit|Edit {{FULLPAGENAME}}}}
    }}
  | {{#title:link|{{FULLPAGENAME}}|action=history|Revision history}}
  | {{#title:link|{{FULLPAGENAME}}|action=purge|Purge page cache}}
  |-
  | {{#title:link|special:MovePage/{{FULLPAGENAME}}||Move page}}
  | {{#title:link|{{FULLPAGENAME}}|action=delete|Delete page}}
  |-
  | {{#title:link|special:WhatLinksHere/{{FULLPAGENAME}}||What links here}}
  | {{#title:link|special:RecentChangesLinked/{{FULLPAGENAME}}||Related changes}}
  |-
  | {{#title:link|{{FULLPAGENAME}}|action=src|format=highlight|Highlight syntax}}
  | {{#title:link|{{FULLPAGENAME}}|action=profile|Profile page}}
  }}
  {{MediaWiki:Custom-button
  |{{#title:link|{{FULLPAGENAME}}|action=edit|Edit}}
  }}
}}
{{Mediawiki:custom-menu
| Wiki
| {{#title:link|{{mediawiki:mainpage}}||Main page}}
| {{#title:link|special:recentchanges||Recent changes}}
| {{#title:link|special:allMessages||MediaWiki messages}}
| {{#title:link|special:statistics||Statistics}}
| {{#title:link|special:version||Version}}
|-
| <span>Special pages</span><ul>{{#list:special}}</ul>
| {{#title:link|special:Configure||Configure wiki}}
| {{#title:link|special:Extensions||Configure extensions}}
|-
| {{#eval:html|<a href="http://www.mediawiki.org">Go to <u>mediawiki.org</u></a>}}
}}
{{Mediawiki:custom-menu
| Skin
| {{#title:link|mediawiki:Custom-skin|action=edit|backlink={{FULLPAGENAME}}|Edit main skin template}}
| {{#title:link|mediawiki:Custom-header|action=edit|backlink={{FULLPAGENAME}}|Edit header}}
| {{#title:link|mediawiki:Custom-footer|action=edit|backlink={{FULLPAGENAME}}|Edit footer}}
| {{#title:link|mediawiki:Custom-mainmenu|action=edit|backlink={{FULLPAGENAME}}|Edit site menu}}
| {{#title:link|mediawiki:Custom-sidebar|action=edit|backlink={{FULLPAGENAME}}|Edit sidebar}}
|-
| {{#title:link|mediawiki:Custom-skin.css|action=edit|backlink={{FULLPAGENAME}}|Edit CSS}}
| {{#title:link|mediawiki:Custom-skin.js|action=edit|backlink={{FULLPAGENAME}}|Edit JS}}
|-
| {{#title:link|mediawiki:Custom-tools|action=edit|backlink={{FULLPAGENAME}}|Edit this menu}}
| {{#title:link|mediawiki:Custom-tools.css|action=edit|backlink={{FULLPAGENAME}}|Edit Custom-tools.css}}
| -
| {{#title:link|special:prefixIndex|from=Custom-|namespace=8|List custom messages}}
}}
{{Mediawiki:custom-menu
|Recent
|	{{#list:recent|20}}
|-
|<b>{{#title:link|special:recentchanges||Recent changes}}</b>
}}
{{Mediawiki:custom-menu
| Pages
| {{#title:link|special:AllPages||All pages}}
| {{#title:link|special:NewPages||New pages}}
|-
| {{#title:link|special:prefixIndex|from=|namespace=0|Content pages}}
| {{#title:link|special:prefixIndex|from=|namespace={{#title:nsnumber|project:$}}|Project pages}}
| {{#title:link|special:prefixIndex|from=|namespace={{#title:nsnumber|template:$}}|Templates}}
| {{#title:link|special:prefixIndex|from=|namespace={{#title:nsnumber|data:$}}|Data tables}}
}}
{{Mediawiki:custom-menu
|Data
|	{{#array:dataloop
	|	{{#data:select
  		| title=`page`.`page_title`
 		| #fromtables =  `page` 
 		| #where = `page`.`page_namespace` = {{#title:nsnumber|data:foo}}
 		| #limit = 10
 		| #orderby = `page_title` DESC
 		}}
	| each = <li>{{#title:link
		|	data:{{{title}}}
		|	command=browse
		|	{{{title}}}
		}}
	}}
}}
{{Mediawiki:custom-menu
| Files
| {{#title:link|special:NewFiles||Recently uploaded files}}
| {{#title:link|special:FileList||List of files}}
| {{#title:link|special:DuplicateFileSearch||Search for duplicate files}}
|-
| {{#title:link|special:Upload||Upload a file}}
}}
{{Mediawiki:custom-menu
| Users
| {{#title:link|special:ListUsers||List users}}
| {{#title:link|special:UserRights||User rights management}}
| {{#title:link|special:ListGroupRights||List user group rights}}
| {{#title:link|special:UserLogin|type=signup|Create user}}
}}
<div class="menu-wrap" id="wiki-tools-personal">
<div class="menu-head">User:{{#user:name}}</div>
<ul class="menu-body">
{{#skin:personal}}
</ul></div>
</div>
<noinclude></div></noinclude>
<noinclude>[[Category:EXPORT_SKIN]]</noinclude>
END
, 'custom-tools.css' => <<<END
/******************************/
/*        WIKITOOLS           */
/*                            */

body
{
  margin:0;
  border-top:24px solid transparent;
}


#wiki-tools
{
  font-family:Trebuchet MS, Verdana, arial;
  color:#999;
  line-height:130%;
  border-bottom-left-radius: 2px;
  border-bottom-right-radius: 2px;
  background:#fff;
  box-shadow: 0 0 4px 1px rgba(0,0,0,0.3);
  border-top:0px;
  font-size:12px;
  position:fixed;
  top:0px;
  height:25px;
  left:50%;
  margin-left:-500px;
  width:1000px;
  z-index:100;
  padding:2px 2px 0;
}

#wiki-tools .menu-head
{
  margin:0px;
  padding:2px 6px 2px; 
  cursor:default;
  border-radius: 2px;
  text-transform:uppercase;
  font-weight:bold;
}

#menu-page .menu-head
{
  border-top-left-radius: 0px;
  xbackground:rgba(255,0,0,0.6);
  text-transform:none;
}

#wiki-tools b {
  font-weight:normal;
  color:black;
  font-weight:bold;
  xtext-shadow:0 -1px 0.5px #222;
}

#wiki-tools .menu-wrap:hover b {
  color:white;
}

#wiki-tools .menu-wrap:hover .menu-head
{
  background:#999;
  color:#fff;
  border-top-right-radius: 2px;
}



#wiki-tools .menu-button
{
  display:inline-block;
  margin:1px 0;
  padding:2px 6px; 
  cursor:default;
  border-radius:2px; 
}

#wiki-tools .menu-button:hover
{
  background:#999;
  color:#fff;
}

#wiki-tools .menu-button a
{
  text-decoration:none;
  color:#999;
  text-transform:uppercase;
  font-weight:bold
}

#wiki-tools .menu-button:hover a
{
  color:#fff;
}

#wiki-tools .menu-wrap
{
  display:inline-block;
  position:relative;
}

#wiki-tools .menu-body hr
{
  margin:2px 8px;
  border:none;
  background:#999;
  padding:0;
  height:1px;
  display:block;
}



#wiki-tools .menu-body
{
  left:0px;
  clear:both;
  list-style:none;
  background:#fdfdfd;
  box-shadow: 0 0 4px 1px rgba(0,0,0,0.3);
  padding:2px 0 2px;
  margin:0;
  display:none; 
  position:absolute;
  z-index:-1;
  border-radius:2px;
  border-top-left-radius:0px;
}
#wiki-tools .menu-body,
#wiki-tools .menu-body ul {
  clear:both;
  list-style:none;
  background:#fdfdfd;
  box-shadow: 0 0 4px 1px rgba(0,0,0,0.3);
  padding:2px 0 2px;
  margin:0;
  display:none; 
  border-radius:2px;
  border-top-left-radius:0px;
}
#wiki-tools .menu-body li { 
  position:relative;
}
#wiki-tools .menu-body ul { 
  list-style:none;
  display:none;
  position:absolute;
  margin-top:-24px;
  left:100%;
}
#wiki-tools .menu-body li:hover > ul {
  display:inline-block;
}

#wiki-tools .menu-body a,
#wiki-tools .menu-body li > span
{
  display:block;
  text-decoration:none;
  padding:2px 8px;
  margin:0 2px;
  color:#222;
  cursor:default;
  white-space:nowrap;
  border-radius:2px;
  border:solid transparent 1px;
}

#wiki-tools .menu-body a:hover,
#wiki-tools .menu-body li:hover > span 
{
  background:#ddd;
  color:#000;
  text-decoration:none;
}

#wiki-tools .menu-body li > span:after {
  content: '▸';
  float:right;
  color:#999;
  margin-left:8px;
}


#wiki-tools .menu-body a:focus,
#wiki-tools .menu-body a:active
{
  border:solid #448dae 1px;  
  background:#79c9ec url(/images/nav/bg-green.png) 100%;
  color:white;
}


#wiki-tools .menu-wrap:hover .menu-body
{
  display:block;
  box-shadow: 0 0 4px 1px rgba(0,0,0,0.3);
}

#wpTextbox1 
{
  width:100%;
}


/**** 
== SYNTAX HIGHLIGHTING ==
****/

.wiki-root
{
  white-space:pre; 
  font-family:monospace;
  font-weight:bold; 
  background:white;
  display:block;
  line-height:150%;
}

span.wiki-tplarg
{
  color:red;
  background:#ffd;
  font-weight:bold; 
}

span.wiki-template
{
  font-weight:bold; 
}

span.color-0 {
  color:navy!important;
}
span.color-1 {
  color:darkpurple!important;
}
span.color-2 {
  color:darkgreen!important;
}
span.color-3 {
  color:darkyellow!important;
}
span.color-4 {
  color:darkred!important;
}
span.color-5 {
  color:green!important;
}
span.color-6 {
  color:brown!important;
}
span.color-7 {
  color:teal!important;
}
span.color-8 {
  color:orange!important;
}
span.color-9 {
  color:blue!important;
}


span.wiki-value
{
  font-weight:normal; 
  margin-left:1em;
}



.wiki-name
{
  font-weight:bold; 
  margin-right:1em;
  margin-left:1em;
}

.wiki-title
{
  font-weight:bold;
}



.editsection 
{
  x-display:none;
}
.xss-table {
  background:white;
  border-collapse:collapse;
  line-height:120%;
  width:100%;
  font-size:12px;
}
.xss-inner-wrap {
  width:100%;
  overflow:auto;
}
.xss-table td {
  border:solid 1px #ddd;
  vertical-align:top;
  padding:4px 4px;
  white-space:nowrap;
  max-width:24em;
  overflow:hidden;
  text-overflow:ellipsis;
  color:#333;
}
.xss-table td a {
  text-decoration:none;
  color:#669;
}
.xss-table td a:hover {
  text-decoration:underline;
  color:#339;
}
#content .xss-sort a {
 color:#ccc;
}
.xss-cell {
}

.xss-cell:hover {

}

.xss-table th {
  border:solid 1px silver;
  vertical-align:top;
  padding:2px;
  max-width:200px;
  background:#ddd;
  white-space:nowrap;
}
#content a.xss-sort {
  color:#aaa;
}
#content a.xss-sort.selected {
  color:#333;
}

.xss-table.xss-rowtable td:first-child {
  width:20%;
}
.xss-tabledef-heading {
 float:left;
 margin-right:24px;
 padding-bottom:6px;
}
.xss-browse-nav, .xss-browse-limit {
  float:right;
  margin-left:24px;
  padding-bottom:6px;
}
.xss-outer-wrap {
  clear:both;
}
.xss-button {
  background:#ddd;
  padding:2px 6px;
  border-radius:2px;
  font-weight:normal;
}
#content .xss-button.disabled {
  color:#ccc;
  background:#f8f8f8;
}
#content .xss-button.disabled:hover {
  color:#ccc;
  background:#f8f8f8;
}

#content .xss-button.selected {
  font-weight:bold;
  color:#333;
}
#content .xss-button:hover {
  text-decoration:none;
  color:black;
}

.admin-tool a {
  display:block;
  text-align:center;
  background:#0078AE;
  font-weight:bold;
  padding:2px 0;
  margin:6px 20px 6px 0;
  border: 1px solid #79C9EC;
  -moz-border-radius:30px;
  color:#79C9EC;
}
.admin-tool a:hover {
  text-decoration:none;
  background:#79C9EC;
  border: 1px solid #0078AE;
  color:#0078AE;
}

.section-Posebno\:PrefixIndex table {
 width:100%;
}

div.menu-wrap#wiki-tools-personal {
  float:right;
}
.edit-fullscreen #editform {
  z-index:1;
  font-family:sans-serif;
  font-size:12px;
  position:fixed;
  padding:25px 5px 5px 5px;
  top:0;
  left:0;
  right:0;
  bottom:0;
}
.edit-fullscreen #editpage-copywarn {
  display:none;
}
.edit-fullscreen #wpTextbox1 {
  position:absolute;
  top:25px;
  left:5px;
  right:5px;
  width:auto;
  bottom:40px;
  background:rgba(255,255,255,0.5);
  transition:opacity 2s linear;
}
.edit-fullscreen #wpTextbox1:focus {
  background:rgba(255,255,255,0.95);
}
.edit-fullscreen .editOptions {
  position:absolute;
  background:#def;
  color:#444;
  bottom:0px;
  left:0px;
  right:0px;
  padding:5px;
  text-align:right;
}
.edit-fullscreen .editOptions > div {
  display: inline-block;
  margin:0 12px;
}
.edit-fullscreen .editButtons {
  position:absolute;
  left:0px;
  bottom:5px;
}

x.edit-fullscreen #wikiPreview {
  position:fixed;
  z-index:2;
  top:50px;
  bottom:70px;
  overflow:auto;
  left:50px;
  right:50px;
  background:white;
  border:rgba(0,0,0,0.5) solid 6px;
  border-radius:8px;
  padding:20px;
  box-shadow:0 0 8px 2px rgba(0,0,0,0.5);
}
END
, 'custom-menu' => <<<END
<div class="menu-wrap menu-{{{class|menu}}} menu-{{{align|left}}}" {{#if:{{{id|}}}|id="{{{id}}}"}}><div class="menu-head">{{{1}}}</div>
<ul class="menu-body">{{#switch:{{{2|}}}|=|-=<hr/>|#default=<li>{{{2}}}</li>
}}{{#switch:{{{3|}}}|=|-=<hr/>|#default=<li>{{{3}}}</li>
}}{{#switch:{{{4|}}}|=|-=<hr/>|#default=<li>{{{4}}}</li>
}}{{#switch:{{{5|}}}|=|-=<hr/>|#default=<li>{{{5}}}</li>
}}{{#switch:{{{6|}}}|=|-=<hr/>|#default=<li>{{{6}}}</li>
}}{{#switch:{{{7|}}}|=|-=<hr/>|#default=<li>{{{7}}}</li>
}}{{#switch:{{{8|}}}|=|-=<hr/>|#default=<li>{{{8}}}</li>
}}{{#switch:{{{9|}}}|=|-=<hr/>|#default=<li>{{{9}}}</li>
}}{{#switch:{{{10|}}}|=|-=<hr/>|#default=<li>{{{10}}}</li>
}}{{#switch:{{{11|}}}|=|-=<hr/>|#default=<li>{{{11}}}</li>
}}{{#switch:{{{12|}}}|=|-=<hr/>|#default=<li>{{{12}}}</li>
}}{{#switch:{{{13|}}}|=|-=<hr/>|#default=<li>{{{13}}}</li>
}}{{#switch:{{{14|}}}|=|-=<hr/>|#default=<li>{{{14}}}</li>
}}{{#switch:{{{15|}}}|=|-=<hr/>|#default=<li>{{{15}}}</li>
}}</ul></div>
END
, 'custom-button' => <<<END
<div class="menu-button">{{{1}}}</div>
END
);

