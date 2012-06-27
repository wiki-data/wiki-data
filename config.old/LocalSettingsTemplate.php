#################################################################
##
##      G E N E R A L 
## 

## Allow raw, unchecked HTML in <html>...</html> sections.
## THIS IS VERY DANGEROUS on a publically editable site, so USE wgGroupPermissions
## TO RESTRICT EDITING to only those that you trust
#$wgRawHtml=true;

## Uncomment this to disable output compression
# $wgDisableOutputCompression = true;

# If your server is not configured for the timezone you want, you can set
# this in conjunction with the signature timezone and override the TZ
# environment variable like so: 
#$wgLocalTZoffset = date("Z") / 60;
#$wgLocalTZoffset = 12;

#$wgShowIncludeSizes = false;

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.

$wgScriptPath="/w";

#$wgArticlePath = "/$1";


#################################################################
##
##      N A M E S P A C E S
## 

## Additional namespaces. If the namespaces defined in Language.php and
## Namespace.php are insufficient, you can create new ones here, for example,
## to import Help files in other languages.
## PLEASE  NOTE: Once you delete a namespace, the pages in that namespace will
## no longer be accessible. If you rename it, then you can access them through
## the new namespace name.
##
## Custom namespaces should start at 100 to avoid conflicting with standard
## namespaces, and should always follow the even/odd main/talk pattern.
##
#$wgExtraNamespaces =
#    array(100 => "Hilfe",
#          101 => "Hilfe_Diskussion",
#          102 => "Aide",
#          103 => "Discussion_Aide"
#          );

## See also $wgNamespaceAliases in DefaultSettings.php

## Pages in namespaces in this array can not be used as templates.
## Elements must be numeric namespace ids.
## Among other things, this may be useful to enforce read-restrictions
## which may otherwise be bypassed by using the template machanism.
#$wgNonincludableNamespaces = array();

## Which namespaces should support subpages?
## See Language.php for a list of namespaces.

$wgNamespacesWithSubpages[NS_MAIN] = true;

#$wgNamespacesWithSubpages[NS_TEMPLATE] = true;


#################################################################
##
##      S K I N
## 

## Default skin: you can change the default skin. Use the internal symbolic
## names, ie 'standard', 'nostalgia', 'cologneblue', 'monobook':
$wgDefaultSkin = 'monobook';
#$wgDefaultSkin = 'customskin';


#############################################################
##
##  I M A G E S
##

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:

$wgEnableUploads              = true;

## This is the list of preferred extensions for uploading files. Uploading files
## with extensions not in this list will trigger a warning.
#$wgFileExtensions = array( 'png', 'gif', 'jpg', 'jpeg', 'pdf', 'doc' );

## If this is turned off, users may override the warning for files not covered
## by $wgFileExtensions.
#$wgStrictFileExtensions = false;

## Warn if uploaded files are larger than this (in bytes)
#$wgUploadSizeWarning = 256 * 1024;

## Path to where the uploaded files are stored
### Uncomment for single wiki setup:
#$wgUploadDirectory            = "{$IP}/images";
### Uncomment for wiki farm:
#$wgUploadDirectory            = "{$wgMwSitePath}/images";

# NOTE: this can be enabled only AFTER installation
#$wgUploadDirectory                = "{$wgMwSitePath}/images";
$wgUploadPath                     = "/images";

##  Uncomment to allow inline image pointing to other websites 
# $wgAllowExternalImages = true;

##If the above is false, you can specify an exception here. Image URLs
##that start with this string are then rendered, while all others are not.
##You can use this to set up a trusted, simple repository of images.
#$wgAllowExternalImagesFrom = 'http://127.0.0.1/';

## If you want to use image uploads under safe mode,
## create the directories images/archive, images/thumb and
## images/temp, and make them all writable. Then uncomment
## this, if it's not already uncommented:
#$wgHashedUploadDirectory = false;

## If you have the appropriate support software installed
## you can enable inline LaTeX equations:
$wgUseTeX           = false;


#############################################################
##
##  P E R M I S S I O N S
##

## Uncomment for private wiki
$wgGroupPermissions['*']['createaccount'] = false;
$wgGroupPermissions['*']['edit'] = false;
$wgGroupPermissions['devel']['userrights'] = true;


#############################################################
##
##  D E F A U L T   U S E R   O P T I O N S
##

array_merge ( $wgDefaultUserOptions, array( 
#    'quickbar'               => 1,
#    'underline'              => 2,
#    'cols'                   => 80,
#    'rows'                   => 25,
#    'searchlimit'            => 20,
#    'contextlines'           => 5,
#    'contextchars'           => 50,
#    'skin'                   => false,
#    'math'                   => 1,
#    'rcdays'                 => 7,
#    'rclimit'                => 50,
#    'wllimit'                => 250,
#    'highlightbroken'        => 1,
#    'stubthreshold'          => 0,
#    'previewontop'           => 1,
    'editsection'            => 0,
#    'editsectiononrightclick'=> 0,
    'showtoc'                => 0,
#    'showtoolbar'            => 1,
#    'date'                   => 'default',
#    'imagesize'              => 2,
#    'thumbsize'              => 2,
#    'rememberpassword'       => 0,
#    'enotifwatchlistpages'   => 0,
#    'enotifusertalkpages'    => 1,
#    'enotifminoredits'       => 0,
#    'enotifrevealaddr'       => 0,
#    'shownumberswatching'    => 1,
#    'fancysig'               => 0,
#    'externaleditor'         => 0,
#    'externaldiff'           => 0,
    'showjumplinks'          => 0,
#    'numberheadings'         => 0,
#    'uselivepreview'         => 0,
#    'watchlistdays'          => 3.0,
));


#############################################################
##
##  E X T E N S I O N S
##

## path to the extension directory
$wgExtensionPath="w/extensions";
#$wgExtensionPath="../extensions";

$wgXssSettings = array ('dbsuffix'=>'');
# NOTE: this can be enabled only AFTER installation
#require_once("{$wgExtensionPath}/Xoo/Xoo.php");
#require_once("{$wgExtensionPath}/ParserFunctions/ParserFunctions.php");
$wgAllowEvalHtmlIn[NS_MEDIAWIKI] = true;

############################################################
# DEBUG - comment out for production!
$wgShowExceptionDetails = true;
#$wgDebugRedirects=true;

