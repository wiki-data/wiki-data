<?php
/*
 * @(#)AnyWikiDraw.i18n.php  0.11 2008-05-23
 *
 * Copyright (c) 2007-2008 by the original authors of AnyWikiDraw
 * and all its contributors.
 * All rights reserved.
 *
 * This software is the confidential and proprietary information of
 * AnyWikiDraw.org ("Confidential Information"). You shall not disclose
 * such Confidential Information and shall use it only in accordance
 * with the terms of the license agreement you entered into with
 * AnyWikiDraw.org.
 */

/**
 * --------
 * WARNING: This is an extension for MediaWiki 1.12 only. Do not use it
 * with other versions of MediaWiki without prior testing!
 * --------
 *
 * Internationalisation file for AnyWikiDraw_body.php.
 *
 * @author Werner Randelshofer
 */
$messages = array();
$messages['en'] = array(
    'anywikidraw' => 'AnyWikiDraw Drawing Extension',
    'anywikidraw_about' => '<p>On this Wiki the AnyWikiDraw Drawing Extension $1 is installed.</p>'.
        '<p>With this extension, you can edit drawings directly inside of a Wiki page.</p>'.
        '<p>To include a drawing in a page, use a tag of the form '.
        '<b><nowiki>{{#drawing:File.svg|width|height}}</nowiki></b>.</p>'.
        '<p>For example '.
        '<b><nowiki>{{#drawing:HappyFace.svg|400|300}}</nowiki></b>.</p>'.
        '<p>The following filename extensions are supported: .svg, .svgz, .png, .jpg.</p>'.
        '<p>If the file doesn\'t exist, it will be created the first time a drawing has been edited.</p>'.
        '<p>All files that have been created using this extension are listed on the [[Special:Imagelist|file list]] special page.',
    'anywikidraw_license_terms_new_work' => 'By saving your work you agree to the license terms of this Wiki. '.
        '<br>For more information see $1. ',
    'anywikidraw_license_terms_derived_work' => 'By saving your changes you agree to the terms given by the copyright holder of the original work. '.
        '<br>For more information see $2. ',
    'anywikidraw_upload_disabled' => '<p><b>You can not edit drawings using AnyWikiDraw, because File uploads are disabled on $1.</b></p>',
    'anywikidraw_extension_disabled' => '<p><b>You can not edit drawings with extension $2 using AnyWikiDraw, because this extension is disabled on $1.</b></p>',
    'anywikidraw_extensions_disabled' => '<p><b>You can not edit drawings with extensions $2 using AnyWikiDraw, because these extensions are disabled on $1.</b></p>',
);
?>