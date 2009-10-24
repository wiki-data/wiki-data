<?php
/*
 * @(#)AnyWikiDraw_body.php  0.11 2008-05-23
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
 * This file contains the AnyWikiDraw special page.
 *
 * The special page displays a description of AnyWikiDraw, and it is
 * used by the applet to download and upload an image to the Wiki.
 *
 * @author Werner Randelshofer
 */

include 'SpecialUpload.php';

class AnyWikiDraw extends SpecialPage {
	/**#@+
	 * @access private
	 */
	var $mUploadDescription, $mLicense, $mUploadOldVersion;
	var $mUploadCopyStatus, $mUploadSource, $mWatchthis;
	
	function AnyWikiDraw() {
		SpecialPage::SpecialPage("AnyWikiDraw");
		wfLoadExtensionMessages('AnyWikiDraw');

		$this->mUploadDescription = '';
		$this->mLicense = '';
		$this->mUploadCopyStatus = '';
		$this->mUploadSource = '';
		$this->mWatchthis = false;
	}
	
	function execute( $par ) {
		global $wgRequest, $wgOut;
		
		if ($wgRequest->wasPosted()) {
			$this->internalProcessUpload();
		} else if (strlen($wgRequest->getVal("image","")) > 0) {
			$this->processDownload();
		} else {
			$this->setHeaders();
		
			# Get request data from, e.g.
			# $param = $wgRequest->getText('param');
				
			# Show information about AnyWikiDraw
            global $wgAnyWikiDrawVersion;
			$wgOut->addWikiText(
				wfMsg('anywikidraw_about', $wgAnyWikiDrawVersion)
			);

            # Check uploading enabled
            global $wgEnableUploads, $wgSitename;
        	if( !$wgEnableUploads ) {
    			$wgOut->addWikiText(
        			wfMsg('anywikidraw_upload_disabled', $wgSitename)
                );
            } 

            # Check file extensions enabled
            global $wgFileExtensions;
            $requiredExtensions = array("svg","svgz","png","jpg");
            $missingExtensions = array();
            foreach ($requiredExtensions as $ext) {
                if (! in_array($ext, $wgFileExtensions)) {
                    $missingExtensions[] = $ext;
                }
            }
            if (count($missingExtensions) == 1) {
    			$wgOut->addWikiText(
        			wfMsg('anywikidraw_extension_disabled', $wgSitename, ".".implode(", .", $missingExtensions) )
                );
            } else if (count($missingExtensions) > 1) {
    			$wgOut->addWikiText(
        			wfMsg('anywikidraw_extensions_disabled', $wgSitename, ".".implode(", .", $missingExtensions) )
                );
            }
		
			// Output
			// $wgOut->addHTML( $output );
		}
	}
	
	/** I BORROWED THIS FUNCTION FROM SpecialUpload.php!! CHECK FOR EACH VERSION OF MEDIAWIKI, IF
	 *  THIS FUNCTION STILL MAKES SENSE!
	 *
	 */
	function internalProcessUpload() {
		global $wgUser, $wgUploadDirectory, $wgRequest;
		
		$fname= "AnyWikiDraw_body::internalProcessUpload";

		// Retrieve form fields
        // FIXME - We currently do not support rendered images and image maps
        //         we handle the fields here anyway, for future extensions.
		$drawingName = $wgRequest->getText('DrawingName');
		$drawingWidth = $wgRequest->getText('DrawingWidth');
		$drawingHeight = $wgRequest->getText('DrawingHeight');
		$drawingTempPath =  $wgRequest->getFileTempName('DrawingData');
		$drawingFileSize = $wgRequest->getFileSize( 'DrawingData' );
		$drawingUploadError = $wgRequest->getUploadError('DrawingData');
		$renderedTempPath =  $wgRequest->getFileTempName('RenderedImageData');
		$renderedFileSize = $wgRequest->getFileSize( 'RenderedImageData' );
		$renderedUploadError = $wgRequest->getUploadError('RenderedImageData');
		$imageMapTempPath =  $wgRequest->getFileTempName('ImageMapData');
		$imageMapFileSize = $wgRequest->getFileSize( 'ImageMapData' );
		$imageMapUploadError = $wgRequest->getUploadError('ImageMapData');
		$uploadSummary = $wgRequest->getText('UploadSummary');
		
		// validate image dimension
		if (! is_numeric($drawingWidth) || $drawingWidth < 1) {
			$drawingWidth = null;
		}
		if (! is_numeric($drawingHeight) || $drawingHeight < 1) {
			$drawingHeight = null;
		}

		/**
		 * If there was no filename or a zero size given, give up quick.
		 */
		if (trim($drawingName) == '' || empty($drawingFileSize)) {
			wfDebug('[client '.$_SERVER["REMOTE_ADDR"].']'.
					'[user '.$wgUser->getName().'] '.
					$fname.' received bad request [DrawingName='.$drawingName.']'.
					'[fileSize(DrawingData)='.$drawingFileSize.']',
               true
			);
			unlink($drawingTempPath);
			header('HTTP/1.0 400 Bad Request');
			exit("\n\n"+'<html><body>DrawingName and DrawingData must be supplied.</body></html>');
		}


		# Chop off any directories in the given filename
		if( $drawingName ) {
			$basename = $drawingName;
		} else {
			$basename = $drawingName;
		}
		$filtered = wfBaseName( $basename );

		/**
		 * We'll want to blacklist against *any* 'extension', and use
		 * only the final one for the whitelist.
		 */
		list( $partname, $ext ) = $this->splitExtensions( $filtered );

		if( count( $ext ) ) {
			$finalExt = $ext[count( $ext ) - 1];
		} else {
			$finalExt = '';
		}

		# If there was more than one "extension", reassemble the base
		# filename to prevent bogus complaints about length
		if( count( $ext ) > 1 ) {
			for( $i = 0; $i < count( $ext ) - 1; $i++ )
				$partname .= '.' . $ext[$i];
		}

		if( strlen( $partname ) < 1 ) {
			wfDebug('[client '.$_SERVER["REMOTE_ADDR"].']'.
					'[user '.$wgUser->getName().'] '.
					$fname.' Received bad image extension [DrawingName='.$drawingName.']',
                true
            );
            if ($drawingTempPath!= null) { unlink($drawingTempPath); }
            if ($renderedTempPath!= null) { unlink($renderedTempPath); }
            if ($imageMapTempPath!= null) { unlink($imageMapTempPath); }
			header('HTTP/1.0 400 Bad Request');
    		global $wgFileExtensions;
			exit("\n\n"+'<html><body>DrawingName must have one of the following extensions: '.
					implode(',', $wgFileExtensions).
				'.</body></html>');
		}

		/**
		 * Filter out illegal characters, and try to make a legible name
		 * out of it. We'll strip some silently that Title would die on.
		 */
		$filtered = preg_replace ( "/[^".Title::legalChars()."]|:/", '-', $filtered );
		$nt = Title::makeTitleSafe( NS_IMAGE, $filtered );
		if( is_null( $nt ) ) {
			wfDebug('[client '.$_SERVER["REMOTE_ADDR"].']'.
					'[user '.$wgUser->getName().'] '.
					$fname.' Received bad image name [DrawingName='.$drawingName.']',
                true
            );
            if ($drawingTempPath!= null) { unlink($drawingTempPath); }
            if ($renderedTempPath!= null) { unlink($renderedTempPath); }
            if ($imageMapTempPath!= null) { unlink($imageMapTempPath); }
			header('HTTP/1.0 400 Bad Request');
			exit("\n\n"+'<html><body>DrawingName must contain legible characters only.</body></html>');
		}
        $localFile = wfLocalFile( $nt );
		$destName = $localFile->getName();
		
		
		/**
		 * If the image is protected, non-sysop users won't be able
		 * to modify it by uploading a new revision.
		 */
		if( !$nt->userCan( 'edit' ) || !$nt->userCan( 'create' ) ) {
			wfDebug('[client '.$_SERVER["REMOTE_ADDR"].']'.
					'[user '.$wgUser->getName().'] '.
					$fname.' image is protected [DrawingName='.$drawingName.']',
                true
            );
            if ($drawingTempPath!= null) { unlink($drawingTempPath); }
            if ($renderedTempPath!= null) { unlink($renderedTempPath); }
            if ($imageMapTempPath!= null) { unlink($imageMapTempPath); }
			header('HTTP/1.0 403 Forbidden');
			exit("\n\n"+'<html><body>You are not allowed to edit this image.</body></html>');
		}

		/**
		 * In some cases we may forbid overwriting of existing files.
		 */
		$overwrite = $this->checkOverwrite( $destName );
		if( $overwrite !== true ) {
			wfDebug('[client '.$_SERVER["REMOTE_ADDR"].']'.
					'[user '.$wgUser->getName().'] '.
					$fname.' image may not be overwritten [DrawingName='.$drawingName.']',
                true
            );
            if ($drawingTempPath!= null) { unlink($drawingTempPath); }
            if ($renderedTempPath!= null) { unlink($renderedTempPath); }
            if ($imageMapTempPath!= null) { unlink($imageMapTempPath); }
			header('HTTP/1.0 403 Forbidden');
			exit("\n\n"+'<html><body>You are not allowed to overwrite this image.</body></html>');
		}
		
		/* Don't allow users to override the blacklist (check file extension) */
		global $wgStrictFileExtensions;
		global $wgFileExtensions, $wgFileBlacklist;
		if ($finalExt == '') {
			wfDebug('[client '.$_SERVER["REMOTE_ADDR"].']'.
					'[user '.$wgUser->getName().'] '.
					$fname.' filename extension missing [DrawingName='.$drawingName.']',
                true
            );
            if ($drawingTempPath!= null) { unlink($drawingTempPath); }
            if ($renderedTempPath!= null) { unlink($renderedTempPath); }
            if ($imageMapTempPath!= null) { unlink($imageMapTempPath); }
			header('HTTP/1.0 415 Unsupported Media Type');
			exit("\n\n"+'<html><body>The drawing must have a filename extension.</body></html>');
		} elseif ( $this->checkFileExtensionList( $ext, $wgFileBlacklist ) ||
				($wgStrictFileExtensions && !$this->checkFileExtension( $finalExt, $wgFileExtensions ) ) ) {
			wfDebug('[client '.$_SERVER["REMOTE_ADDR"].']'.
					'[user '.$wgUser->getName().'] '.
					$fname.' bad filename extension [DrawingName='.$drawingName.']',
                true
            );
            if ($drawingTempPath!= null) { unlink($drawingTempPath); }
            if ($renderedTempPath!= null) { unlink($renderedTempPath); }
            if ($imageMapTempPath!= null) { unlink($imageMapTempPath); }
			header('HTTP/1.0 415 Unsupported Media Type');
			exit("\n\n"+'<html><body>The drawing has a forbidden filename extension.</body></html>');
		}

		
		/**
		 * Look at the contents of the file; if we can recognize the
		 * type but it's corrupt or data of the wrong type, we should
		 * probably not accept it.
		 */
		$veri = $this->verify( $drawingTempPath, $imageExtension );
		$fileProps = File::getPropsFromPath( $drawingTempPath, $finalExt );
		//$this->checkMacBinary(); // XXX
		$veri = $this->verify( $drawingTempPath, $finalExt );

        if( $veri !== true ) { //it's a wiki error...
				$resultDetails = array( 'veri' => $veri );
			wfDebug('[client '.$_SERVER["REMOTE_ADDR"].']'.
					'[user '.$wgUser->getName().'] '.
					$fname.' image failed verification [DrawingName='.$drawingName.'][DrawingTempFile='.$drawingTempPath.']',
                true
            );
            if ($drawingTempPath!= null) { unlink($drawingTempPath); }
            if ($renderedTempPath!= null) { unlink($renderedTempPath); }
            if ($imageMapTempPath!= null) { unlink($imageMapTempPath); }
			header('HTTP/1.0 400 Bad Request');
			exit("\n\n"+'<html><body>The image data is corrupt.</body></html>');
        }

        /**
         * Provide an opportunity for extensions to add further checks
         */
        $error = '';
        if( !wfRunHooks( 'UploadVerification',
                    array( $destName, $drawingTempPath, &$error ) ) ) {
			$resultDetails = array( 'error' => $error );
			wfDebug('[client '.$_SERVER["REMOTE_ADDR"].']'.
					'[user '.$wgUser->getName().'] '.
					$fname.' image failed extended verification [DrawingName='.$drawingName.'][DrawingTempFile='.$drawingTempPath.']',
                true
            );
            if ($drawingTempPath!= null) { unlink($drawingTempPath); }
            if ($renderedTempPath!= null) { unlink($renderedTempPath); }
            if ($imageMapTempPath!= null) { unlink($imageMapTempPath); }
			header('HTTP/1.0 400 Bad Request');
			exit("\n\n"+'<html><body>The image data is corrupt.</body></html>');
		}

		/**
		 * Try actually saving the thing...
		 * It will show an error form on failure.
		 */
		$pageText = UploadForm::getInitialPageText( $uploadSummary, 'License ???',
			'CopyrightStatus ???', 'CopyrightSource ???' );

		$status = $localFile->upload( $drawingTempPath, $uploadSummary, $pageText,
			File::DELETE_SOURCE, $fileProps );
		if ( !$status->isGood() ) {
			$resultDetails = array( 'internal' => $status->getWikiText() );
			wfDebug('[client '.$_SERVER["REMOTE_ADDR"].']'.
					'[user '.$wgUser->getName().'] '.
					$fname.' image upload failed '.
                    '[DrawingName='.$drawingName.']'.
                    '[DrawingTempFile='.$drawingTempPath.']'.
                    '[Status='.$status->getWikiText().']'
                    ,
                true
            );
            if ($drawingTempPath!= null) { unlink($drawingTempPath); }
            if ($renderedTempPath!= null) { unlink($renderedTempPath); }
            if ($imageMapTempPath!= null) { unlink($imageMapTempPath); }
			header('HTTP/1.0 500 Internal Server Error');
			exit("\n\n"+'<html><body>Uploading the image failed.</body></html>');
		}
			                                
        if ($drawingTempPath!= null) { unlink($drawingTempPath); }
        if ($renderedTempPath!= null) { unlink($renderedTempPath); }
        if ($imageMapTempPath!= null) { unlink($imageMapTempPath); }

    	 // Show success, and exit
		header('HTTP/1.0 200 OK');
		exit;
	}
	
	/** I BORROWED THIS FUNCTION FROM SpecialUpload.php!! CHECK FOR EACH VERSION OF MEDIAWIKI, IF
	 *  THIS FUNCTION STILL MAKES SENSE!
	 *
	 * Check if there's an overwrite conflict and, if so, if restrictions
	 * forbid this user from performing the upload.
	 *
	 * @return mixed true on success, WikiError on failure
	 * @access private
	 */
	function checkOverwrite( $name ) {
		$img = wfFindFile( $name );

		$error = '';
		if( $img ) {
			global $wgUser, $wgOut;
			if( $img->isLocal() ) {
				if( !UploadForm::userCanReUpload( $wgUser, $img->name ) ) {
					$error = 'fileexists-forbidden';
				}
			} else {
				if( !$wgUser->isAllowed( 'reupload' ) ||
				    !$wgUser->isAllowed( 'reupload-shared' ) ) {
					$error = "fileexists-shared-forbidden";
				}
			}
		}

		if( $error ) {
			$errorText = wfMsg( $error, wfEscapeWikiText( $img->getName() ) );
			return $errorText;
		}

		// Rockin', go ahead and upload
		return true;
	}
	/** I BORROWED THIS FUNCTION FROM SpecialUpload.php!! CHECK FOR EACH VERSION OF MEDIAWIKI, IF
	 *  THIS FUNCTION STILL MAKES SENSE!
	 *
	 * Split a file into a base name and all dot-delimited 'extensions'
	 * on the end. Some web server configurations will fall back to
	 * earlier pseudo-'extensions' to determine type and execute
	 * scripts, so the blacklist needs to check them all.
	 *
	 * @return array
	 */
	function splitExtensions( $filename ) {
		$bits = explode( '.', $filename );
		$basename = array_shift( $bits );
		return array( $basename, $bits );
	}

	/** I BORROWED THIS FUNCTION FROM SpecialUpload.php!! CHECK FOR EACH VERSION OF MEDIAWIKI, IF
	 *  THIS FUNCTION STILL MAKES SENSE!
	 *
	 * Perform case-insensitive match against a list of file extensions.
	 * Returns true if the extension is in the list.
	 *
	 * @param string $ext
	 * @param array $list
	 * @return bool
	 */
	function checkFileExtension( $ext, $list ) {
		return in_array( strtolower( $ext ), $list );
	}
	/** I BORROWED THIS FUNCTION FROM SpecialUpload.php!! CHECK FOR EACH VERSION OF MEDIAWIKI, IF
	 *  THIS FUNCTION STILL MAKES SENSE!
	 *
	 * Perform case-insensitive match against a list of file extensions.
	 * Returns true if any of the extensions are in the list.
	 *
	 * @param array $ext
	 * @param array $list
	 * @return bool
	 */
	function checkFileExtensionList( $ext, $list ) {
		foreach( $ext as $e ) {
			if( in_array( strtolower( $e ), $list ) ) {
				return true;
			}
		}
		return false;
	}

	/** I BORROWED THIS FUNCTION FROM SpecialUpload.php!! CHECK FOR EACH VERSION OF MEDIAWIKI, IF
	 *  THIS FUNCTION STILL MAKES SENSE!
	 *
	 * Verifies that it's ok to include the uploaded file
	 *
	 * @param string $tmpfile the full path of the temporary file to verify
	 * @param string $extension The filename extension that the file is to be served with
	 * @return mixed true of the file is verified, a WikiError object otherwise.
	 */
	function verify( $tmpfile, $extension ) {
		#magically determine mime type
		$magic=& MimeMagic::singleton();
		$mime= $magic->guessMimeType($tmpfile,false);

		#check mime type, if desired
		global $wgVerifyMimeType;
		if ($wgVerifyMimeType) {

		  wfDebug ( "\n\nmime: <$mime> extension: <$extension>\n\n");
			#check mime type against file extension
			if( !$this->verifyExtension( $mime, $extension ) ) {
				return new WikiErrorMsg( 'uploadcorrupt' );
			}

			#check mime type blacklist
			global $wgMimeTypeBlacklist;
			if( isset($wgMimeTypeBlacklist) && !is_null($wgMimeTypeBlacklist)
				&& $this->checkFileExtension( $mime, $wgMimeTypeBlacklist ) ) {
				return new WikiErrorMsg( 'filetype-badmime', htmlspecialchars( $mime ) );
			}
		}

		#check for htmlish code and javascript
		if( $this->detectScript ( $tmpfile, $mime, $extension ) ) {
			return new WikiErrorMsg( 'uploadscripted' );
		}

		/**
		* Scan the uploaded file for viruses
		*/
		$virus= $this->detectVirus($tmpfile);
		if ( $virus ) {
			return new WikiErrorMsg( 'uploadvirus', htmlspecialchars($virus) );
		}

		wfDebug( __METHOD__.": all clear; passing.\n" );
		return true;
	}

	/** I BORROWED THIS FUNCTION FROM SpecialUpload.php!! CHECK FOR EACH VERSION OF MEDIAWIKI, IF
	 *  THIS FUNCTION STILL MAKES SENSE!
	 *
	 * Checks if the mime type of the uploaded file matches the file extension.
	 *
	 * @param string $mime the mime type of the uploaded file
	 * @param string $extension The filename extension that the file is to be served with
	 * @return bool
	 */
	function verifyExtension( $mime, $extension ) {
		$magic =& MimeMagic::singleton();

		if ( ! $mime || $mime == 'unknown' || $mime == 'unknown/unknown' )
			if ( ! $magic->isRecognizableExtension( $extension ) ) {
				wfDebug( __METHOD__.": passing file with unknown detected mime type; " .
					"unrecognized extension '$extension', can't verify\n" );
				return true;
			} else {
				wfDebug( __METHOD__.": rejecting file with unknown detected mime type; ".
					"recognized extension '$extension', so probably invalid file\n" );
				return false;
			}

		$match= $magic->isMatchingExtension($extension,$mime);

		if ($match===NULL) {
			wfDebug( __METHOD__.": no file extension known for mime type $mime, passing file\n" );
			return true;
		} elseif ($match===true) {
			wfDebug( __METHOD__.": mime type $mime matches extension $extension, passing file\n" );

			#TODO: if it's a bitmap, make sure PHP or ImageMagic resp. can handle it!
			return true;

		} else {
			wfDebug( __METHOD__.": mime type $mime mismatches file extension $extension, rejecting file\n" );
			return false;
		}
	}
	/** I BORROWED THIS FUNCTION FROM SpecialUpload.php!! CHECK FOR EACH VERSION OF MEDIAWIKI, IF
	 *  THIS FUNCTION STILL MAKES SENSE!
	 *
	 * Heuristic for detecting files that *could* contain JavaScript instructions or
	 * things that may look like HTML to a browser and are thus
	 * potentially harmful. The present implementation will produce false positives in some situations.
	 *
	 * @param string $file Pathname to the temporary upload file
	 * @param string $mime The mime type of the file
	 * @param string $extension The extension of the file
	 * @return bool true if the file contains something looking like embedded scripts
	 */
	function detectScript($file, $mime, $extension) {
		global $wgAllowTitlesInSVG;

		#ugly hack: for text files, always look at the entire file.
		#For binarie field, just check the first K.

		if (strpos($mime,'text/')===0) $chunk = file_get_contents( $file );
		else {
			$fp = fopen( $file, 'rb' );
			$chunk = fread( $fp, 1024 );
			fclose( $fp );
		}

		$chunk= strtolower( $chunk );

		if (!$chunk) return false;

		#decode from UTF-16 if needed (could be used for obfuscation).
		if (substr($chunk,0,2)=="\xfe\xff") $enc= "UTF-16BE";
		elseif (substr($chunk,0,2)=="\xff\xfe") $enc= "UTF-16LE";
		else $enc= NULL;

		if ($enc) $chunk= iconv($enc,"ASCII//IGNORE",$chunk);

		$chunk= trim($chunk);

		#FIXME: convert from UTF-16 if necessarry!

		wfDebug("SpecialUpload::detectScript: checking for embedded scripts and HTML stuff\n");

		#check for HTML doctype
		if (eregi("<!DOCTYPE *X?HTML",$chunk)) return true;

		/**
		* Internet Explorer for Windows performs some really stupid file type
		* autodetection which can cause it to interpret valid image files as HTML
		* and potentially execute JavaScript, creating a cross-site scripting
		* attack vectors.
		*
		* Apple's Safari browser also performs some unsafe file type autodetection
		* which can cause legitimate files to be interpreted as HTML if the
		* web server is not correctly configured to send the right content-type
		* (or if you're really uploading plain text and octet streams!)
		*
		* Returns true if IE is likely to mistake the given file for HTML.
		* Also returns true if Safari would mistake the given file for HTML
		* when served with a generic content-type.
		*/

		$tags = array(
			'<body',
			'<head',
			'<html',   #also in safari
			'<img',
			'<pre',
			'<script', #also in safari
			'<table'
			);
		if( ! $wgAllowTitlesInSVG && $extension !== 'svg' && $mime !== 'image/svg' ) {
			$tags[] = '<title';
		}

		foreach( $tags as $tag ) {
			if( false !== strpos( $chunk, $tag ) ) {
				return true;
			}
		}

		/*
		* look for javascript
		*/

		#resolve entity-refs to look at attributes. may be harsh on big files... cache result?
		$chunk = Sanitizer::decodeCharReferences( $chunk );

		#look for script-types
		if (preg_match('!type\s*=\s*[\'"]?\s*(?:\w*/)?(?:ecma|java)!sim',$chunk)) return true;

		#look for html-style script-urls
		if (preg_match('!(?:href|src|data)\s*=\s*[\'"]?\s*(?:ecma|java)script:!sim',$chunk)) return true;

		#look for css-style script-urls
		if (preg_match('!url\s*\(\s*[\'"]?\s*(?:ecma|java)script:!sim',$chunk)) return true;

		wfDebug("SpecialUpload::detectScript: no scripts found\n");
		return false;
	}

	/** I BORROWED THIS FUNCTION FROM SpecialUpload.php!! CHECK FOR EACH VERSION OF MEDIAWIKI, IF
	 *  THIS FUNCTION STILL MAKES SENSE!
	 *
	 * Generic wrapper function for a virus scanner program.
	 * This relies on the $wgAntivirus and $wgAntivirusSetup variables.
	 * $wgAntivirusRequired may be used to deny upload if the scan fails.
	 *
	 * @param string $file Pathname to the temporary upload file
	 * @return mixed false if not virus is found, NULL if the scan fails or is disabled,
	 *         or a string containing feedback from the virus scanner if a virus was found.
	 *         If textual feedback is missing but a virus was found, this function returns true.
	 */
	function detectVirus($file) {
		global $wgAntivirus, $wgAntivirusSetup, $wgAntivirusRequired, $wgOut;

		if ( !$wgAntivirus ) {
			wfDebug( __METHOD__.": virus scanner disabled\n");
			return NULL;
		}

		if ( !$wgAntivirusSetup[$wgAntivirus] ) {
			wfDebug( __METHOD__.": unknown virus scanner: $wgAntivirus\n" );
			# @TODO: localise
			$wgOut->addHTML( "<div class='error'>Bad configuration: unknown virus scanner: <i>$wgAntivirus</i></div>\n" ); 
			return "unknown antivirus: $wgAntivirus";
		}

		# look up scanner configuration
		$command = $wgAntivirusSetup[$wgAntivirus]["command"];
		$exitCodeMap = $wgAntivirusSetup[$wgAntivirus]["codemap"];
		$msgPattern = isset( $wgAntivirusSetup[$wgAntivirus]["messagepattern"] ) ?
			$wgAntivirusSetup[$wgAntivirus]["messagepattern"] : null;

		if ( strpos( $command,"%f" ) === false ) {
			# simple pattern: append file to scan
			$command .= " " . wfEscapeShellArg( $file ); 
		} else {
			# complex pattern: replace "%f" with file to scan
			$command = str_replace( "%f", wfEscapeShellArg( $file ), $command ); 
		}

		wfDebug( __METHOD__.": running virus scan: $command \n" );

		# execute virus scanner
		$exitCode = false;

		#NOTE: there's a 50 line workaround to make stderr redirection work on windows, too.
		#      that does not seem to be worth the pain.
		#      Ask me (Duesentrieb) about it if it's ever needed.
		$output = array();
		if ( wfIsWindows() ) {
			exec( "$command", $output, $exitCode );
		} else {
			exec( "$command 2>&1", $output, $exitCode );
		}

		# map exit code to AV_xxx constants.
		$mappedCode = $exitCode;
		if ( $exitCodeMap ) { 
			if ( isset( $exitCodeMap[$exitCode] ) ) {
				$mappedCode = $exitCodeMap[$exitCode];
			} elseif ( isset( $exitCodeMap["*"] ) ) {
				$mappedCode = $exitCodeMap["*"];
			}
		}

		if ( $mappedCode === AV_SCAN_FAILED ) { 
			# scan failed (code was mapped to false by $exitCodeMap)
			wfDebug( __METHOD__.": failed to scan $file (code $exitCode).\n" );

			if ( $wgAntivirusRequired ) { 
				return "scan failed (code $exitCode)"; 
			} else { 
				return NULL; 
			}
		} else if ( $mappedCode === AV_SCAN_ABORTED ) { 
			# scan failed because filetype is unknown (probably imune)
			wfDebug( __METHOD__.": unsupported file type $file (code $exitCode).\n" );
			return NULL;
		} else if ( $mappedCode === AV_NO_VIRUS ) {
			# no virus found
			wfDebug( __METHOD__.": file passed virus scan.\n" );
			return false;
		} else {
			$output = join( "\n", $output );
			$output = trim( $output );

			if ( !$output ) {
				$output = true; #if there's no output, return true
			} elseif ( $msgPattern ) {
				$groups = array();
				if ( preg_match( $msgPattern, $output, $groups ) ) {
					if ( $groups[1] ) {
						$output = $groups[1];
					}
				}
			}

			wfDebug( __METHOD__.": FOUND VIRUS! scanner feedback: $output" );
			return $output;
		}
	}
}
?>