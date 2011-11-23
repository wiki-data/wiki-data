<?php
$messages = array();

$messages['en'] = array(
	'custom-skin'                            => '
{{#eval:html|<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{{LANG}}}" dir="{{{DIR}}}">
	<head>
		<meta http-equiv="Content-Type" content="{{{MIMETYPE}}}; charset={{{CHARSET}}}"/>
		{{{EXTRAHEAD|<!-- -->}}}
		{{{HEADLINKS|<!-- -->}}}
		<meta http-equiv="imagetoolbar" content="no" />
		{{{VARSCRIPT|<!-- -->}}}
		<script type="{{{JSMIMETYPE}}}" src="{{{STYLEPATH}}}/common/wikibits.js"><!-- wikibits js --></script>
		<link rel="stylesheet" type="text/css" href="{{{STYLEPATH}}}/monobook/main.css">
		{{#if:{{{JSVARURL|}}}
		| <script type="{{{JSMIMETYPE}}}" src="{{{JSVARURL}}}"></script>
		|<!-- -->}}
		{{#if:{{{PAGECSS|}}}
		| <style type="text/css">{{{PAGECSS}}}</style>
		|<!-- -->}}
		{{#if:{{{USERCSS|}}}
		| <style type="text/css">{{{USERCSS}}}</style>
		|<!-- -->}}
		{{#if:{{{USERJS|}}}
		| <script type="{{{JSMIMETYPE}}}" src="{{{USERJS}}}"><!-- user js --></script>
		|<!-- -->}}
		{{#if:{{{USERJSPREV|}}}
		| <script type="{{{JSMIMETYPE}}}" src="{{{USERJSPREV}}}"><!-- user js prev --></script>
		|<!-- -->}}
		{{{TRACKBACKHTML|}}}
		{{#if:{{{USERJSPREV|}}}
		| <script type="{{{JSMIMETYPE}}}" src="{{{USERJSPREV}}}"><!-- user js prev --></script>
		|<!-- -->}}
		{{{HEADSCRIPTS|<!-- -->}}}
		<title>
			{{{TITLE}}}<!-- {{FULLPAGENAME}} -->
		</title>
	</head>
	<body
	{{#if:{{{ONDBLCLICK|}}}
	| ondblclick="{{{ONDBLCLICK}}}"
	|<!-- -->}}
	{{#if:{{{ONLOAD|}}}
	| onload="{{{ONLOAD}}}"
	|<!-- -->}}
	class="{{{NSCLASS|}}} {{{DIR|}}} {{{PAGECLASS|}}}"
	>
		<a name="top" id="top"></a>
		<div id="globalWrapper">
			<div id="column-content">
				<div id="content">
					{{#if:{{{SITENOTICE|}}}
					|<div id="siteNotice">{{{SITENOTICE}}}</div>
					|<!- -->}}
					<h1 class="firstHeading">{{{TITLE}}}</h1>
						<div id="bodyContent">
					<h3 id="siteSub">{{{TAGLINE}}}</h3>
					<div id="contentSub">{{{SUBTITLE}}}</div>
					{{{INFO}}}
					}}{{{CONTENT}}}{{#eval:html|
					{{#if:{{{CATLINKS|}}}
					|<div id="catlinks">{{{CATLINKS}}}</div>
					|<!- -->}}
					<div class="visualClear"></div>
				</div>
			</div>
		</div>
		<div id="column-one">
			{{{SITELOGO}}}
			<div id="p-cactions" class="portlet">
				<h5>Views</h5>
				<ul>{{{ACTIONS}}}</ul>
			</div>
			<div class="portlet" id="p-personal">
				<h5>Personal tools</h5>
				<div class="pBody">
					<ul>{{{PERSONAL}}}</ul>
				</div>
			</div>
			{{{SIDEBAR}}}
			<div class="portlet" id="p-tb">
				<h5>{{int:toolbox}}</h5>
				<div class="pBody">
					<ul>{{{TOOLBOX}}}</ul>
				</div>
			</div>
			{{{SEARCH}}}
			{{#if:{{{LANGLINKS|}}}
			|<div class="portlet" id="p-lang">
				<h5>{{int:otherlanguages}}</h5>
				<div class="pBody">
					<ul>{{{LANGLINKS}}}</ul>
				</div>
			</div>
			|<!-- -->}}
		</div>
		<div class="visualClear"></div>
		<script type="text/javascript"> if (window.runOnloadHook) runOnloadHook();</script>
		{{{REPORTTIME}}}
	</body>
</html>}}
'
);

