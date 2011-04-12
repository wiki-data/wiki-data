<?php
XxxInstaller::Install('XwwImage');

#
#  XwwImage - Xoo World of Wiki - Image
#
#  Part of Xoo (c) 1997-2009 [[w:en:User:Zocky]], mitko.si
#	GPL3 applies
#
#########################################################################

#
#
#	Parser functions for dealing with images

class XwwImage extends Xxx
{	
	function fl_image(&$parser,$f,$a)
	{
		return $this->fl_img($parser,$f,$a);
	}

	var $time=0;
	
	function beginTime($text)
	{
		return;
		global $wgUser;
		if ($wgUser->isLoggedIn())
		{	
			$this->time=microtime(true);
			print "\n============\n$text\n------------\n";
		}
	}

	function showTime($text='')
	{
		return;
		global $wgUser;
		if ($wgUser->isLoggedIn())
		{	
			$oldTime=$this->time;
			$this->time = microtime(true);
			print (int)(($this->time-$oldTime) * 100000 ). "\t$text\n";
		}
	}
	var $imgCommands = array(	
		'mime'=>array('mime','text'),
		'thumb'=>array('thumb','img'),
		'thumb:url'=>array('thumb','url'),
		'thumburl'=>array('thumb','url'),
		'thumb:base64'=>array('thumb','base64'),
		'thumbbase64'=>array('thumb','base64'),
		'crop'=>array('crop','img'),
		'crop:url'=>array('crop','url'),
		'cropurl'=>array('crop','url'),
		'crop:base64'=>array('crop','base64'),
		'cropbase64'=>array('crop','base64'),
		'extend'=>array('extend','img'),
		'extend:url'=>array('extend','url'),
		'extendurl'=>array('extend','url'),
		'extend:base64'=>array('extend','base64'),
		'extendbase64'=>array('extend','base64'),
		'stretch'=>array('stretch','img'),
		'stretch:url'=>array('stretch','url'),
		'stretchurl'=>array('stretch','url'),
		'stretch:base64'=>array('stretch','base64'),
		'stretchbase64'=>array('stretch','base64')
	);
	
	function fn_gradient(&$parser, $command='', $from='#000000', $to='#ffffff',$size='400x400')
	{
		if (!$this->matchDimension($size,400,400,$width,$height)) return $this->notFound();
		
		global $wgUploadDirectory,$wgUploadPath,$wgImageMagickConvertCommand;
		
		if (!preg_match('/^#([0-9A-Fa-f]{3})([0-9A-Fa-f]{3})?$/',$from) || !preg_match('/^#([0-9A-Fa-f]{3})([0-9A-Fa-f]{3})?$/',$to))
			return "bad from $from to $to";

		switch ($command)
		{
		case 'vertical':
		case 'verticalurl':
			$size = "{$width}x{$height}";
			break;
		case 'horizontal':
		case 'horizontalurl':
			$size = "{$height}x{$width}";
			$rotate = '-rotate -90';
			break;
		default:
			return $this->notFound();
		}
		$from = substr($from,1);
		$to = substr($to,1);
		
		$filePath = "gradient/" . $from{0} . $from{1} . "/" . $to{0} . $to{1} ;
		$fileDir = "$wgUploadDirectory/$filePath";
	
		$fileName = "{$from}_{$to}_{$width}_{$height}.jpg";
		$imageMagickCommand  = " -size '{$size}' gradient:'#$from-#$to' $rotate $fileDir/$fileName";
		@mkdir($fileDir,0777,true);
#		return $fileDir;
#		return ("$wgImageMagickConvertCommand $imageMagickCommand");
		exec ("$wgImageMagickConvertCommand $imageMagickCommand",$dummy,$res);
		if ($res || !file_exists("$fileDir/$fileName") ) return 'ERROR IN GRADIENT';

		switch ($command)
		{
		case 'verticalurl':
		case 'horizontalurl':
			return "$wgUploadPath/$filePath/$fileName";
		case 'vertical':
		case 'horizontal':
			$imgTag = "<img src=\"$wgUploadPath/$filePath/$fileName\" width=\"$width\" height=\"$height\"/>";
			return array($imgTag,'isHTML'=>true);
		}		
	}
	
	function fl_img(&$parser,$f,$a)
	{
		global $wgSharpenParameter;
	    $args=new XxxArgs($f,$a);
	    $command=$args->command;
	    list($cmd,$ret)=$this->imgCommands[$command];
	    
		if (!$cmd) return $this->notFound();
			    
	    #eat args while they look like file names, until there's a filename that actually exists
	    $i=1;
		$this->beginTime('new image');
		while($i<=$args->count)
		{
			$val=$args->trimExpand($i);
			#if it's not a valid file name, break and try something else
			if (!$this->isValidFilename($val)) break;
			{
				$title=Title::newFromText($val,NS_IMAGE);
			
				if( !$title || $title->getNamespace() != NS_IMAGE ) 
				{
					#it's not actually a valid title, let's try the next one
					$i++;
					continue;					
				}
				$img = wfFindFile( $title, false);
				if( $img ) 
				{
					#we found the image, eat it up and go on
					$i++;
					break;
				}
			};
			$i++;
		}
		$this->showTime();
		# eat up the rest of the filenames
		while($i<=$args->count)
		{
			$val=$args->trimExpand($i);
			if (!$this->isValidFilename($val)) break;
			$i++;
		}
		
		#if we have the image, let's calculate the dimensions
		$this->showTime('dropped unused filenames');
		if (!$img)
		{
			# we didn't find the image, let's skip all the dimensions
			
			#check for old style arguments
			$val = $args->trimExpand($i,0);
			if ( $val === (int)$val ) return '[[image:'.$args->trimExpand(1).']]';
			
			$dimCount=0;
			while ($dimCount<3 && $i<=$args->count)
			{
				$val = $args->trimExpand($i);
				if (!$this->matchDimension($val,100,100,$xxxx,$yyyy)) break;
				$i++;
				$dimCount++;
			}
			# return the alt text, if any, otherwise the first image name
			return $args->trimExpand($i,'[[image:'.$args->trimExpand(1).']]');
		}
				
		#we found the image
		if ($cmd=='mime') return $img->getMimeType();
		#let's look for dimensions
		
		
		#set defaults
		$thumbHeight = $scaleHeight = $img->height;
		$thumbWidth = $scaleWidth = $img->width;
		$offsetLeft = $offsetTop = 0;		
		$backgroundColor = 'transparent';
		$ratio=$img->width / $img->height;

		#are there any args left?
		if ($i <= $args->count)
		{
			$val = $args->trimExpand($i);
			#get the next argument			
			#check for old style arguments
			do
			{
				#first, thumb dimensions - if positive, it's the size, if negative, it's croping margin
				$val = $args->trimExpand($i);
				#check for old style arguments
				if ( is_numeric($val) )
				{
					$thumbWidth = $val;
					$i++;
					if ($args->exists($i)) 
					{
						$thumbHeight = (int)$args->trimExpand($i);
						$i++;
					}
					else
					{
						$thumbHeight = (int)($thumbWidth / $ratio);
					}
					$alt = $title->getText();
					
				}
				elseif ($this->matchDimension($val,$img->width,$img->height,$x,$y))
				{
					$thumbWidth = $x>0 ? $x : $img->width+2*$x;
					$thumbHeight = $y>0 ? $y : $img->height+2*$y;
					$i++;
				} 
			
				#we have thumb dimensions, fix them up

#				die ("$thumbWidth|$thumbHeight");
				switch ($cmd)
				{
				case 'thumb':
					if ($thumbWidth / $thumbHeight > $ratio )
					{
						$thumbWidth = (int)($thumbHeight * $ratio + 0.5);
					}		
					else
					{
						$thumbHeight = (int)($thumbWidth / $ratio + 0.5 );
					}
					break;
				case 'extend':
					if ($thumbWidth / $thumbHeight < $ratio )
					{
						$thumbWidth = (int)($thumbHeight * $ratio + 0.5);
					}		
					else
					{
						$thumbHeight = (int)($thumbWidth / $ratio + 0.5);
					}
				}

				$scaleWidth = $thumbWidth;
				$scaleHeight = $thumbHeight;
			
				# then, scale dimensions - if positive, it's the size, if negative, it's margin around the thumb
				# negative value might produce interesting results together with negative value for thumb size
				$val = $args->trimExpand($i);
				if ($this->matchDimension($val,$img->width,$img->height,$x,$y))
				{
					$scaleWidth = $x>0 ? $x : $thumbWidth-2*$x;
					$scaleHeight = $y>0 ? $y : $thumbHeight-2*$y;
					$i++;
				}
				
				#fix up scale dimensions
				switch ($cmd)
				{
				case 'thumb':
					if ($scaleWidth / $scaleHeight > $ratio )
					{
						$scaleWidth = (int)($scaleHeight * $ratio + 0.5);
					}		
					else
					{
						$scaleHeight = (int)($scaleWidth / $ratio + 0.5);
					}
					break;
				case 'extend':
				case 'crop':
					if ($scaleWidth / $scaleHeight < $ratio )
					{
						$scaleWidth = (int)($scaleHeight * $ratio + 0.5);
					}		
					else
					{
						$scaleHeight = (int)($scaleWidth / $ratio + 0.5);
					}
				}
	
				#last, cropping offset
				# set default
				$offsetLeft = (int)(($scaleWidth-$thumbWidth)/2);
				$offsetTop = (int)(($scaleHeight-$thumbHeight)/2);
			

				$val = $args->trimExpand($i);
				if ($this->matchDimension($val,$scaleWidth,$scaleHeight,$x,$y))
				{
					$offsetLeft = $x>0 ? $x : $scaleWidth + $x - $thumbWidth;
					$offsetTop = $y>0 ? $y : $scaleHeight + $y - $thumbHeight;
					$i++;
				} 
				else break;				
			} while(false);
			
			#we're finished with dimensions, find backgroundColor, if any
			$val = $args->trimExpand($i);
			if (preg_match('/^#([0-9A-Fa-f]{3})([0-9A-Fa-f]{3})?$/',$val))
			{
				$backgroundColor = strtolower($val);
				$i++;
			} 
			#find alt, if any
			$alt=$args->trimExpand($i,$args->trimExpand(1));
			#we're done with arguments
		}
		#we have all the settings we need, let's make the thumb
		$this->showTime('found dimensions');

		$imagePath = $img->getPath();
		$extension=$img->getExtension();
		$thumbName="cropped_{$backgroundColor}_{$scaleWidth}x{$scaleHeight}_{$thumbWidth}x{$thumbHeight}_{$offsetLeft}_{$offsetTop}.{$extension}";
		$thumbPath = $img->getThumbPath($thumbName);

		global $wgImageMagickConvertCommand;
		$thumbUrl = $img->getThumbUrl($thumbName);

		$this->showTime("got image properties: $thumbPath");


		if ($thumbWidth>2000 || $thumbHeight>2000 || $scaleWidth>2000 || $scaleHeight>2000) return "image too large";
		if(!file_exists($thumbPath) || $_GET['action']=='purge')
		{
			if($offsetLeft>=0) $offsetLeft="+{$offsetLeft}";
			if($offsetTop>=0) $offsetTop="+{$offsetTop}";		
			$imageMagickCommand  = " -resize '{$scaleWidth}x{$scaleHeight}!'"
								. " -gravity northwest"
								. " -crop '{$thumbWidth}x{$thumbHeight}{$offsetLeft}{$offsetTop}!'"
								. " -background '$backgroundColor'"
								. " -sharpen $wgSharpenParameter"
								. " -flatten";

			$dirParts = explode('/',dirname($thumbPath));
			
			@mkdir(dirname($thumbPath),0777,true);
			exec ("$wgImageMagickConvertCommand $imagePath $imageMagickCommand $thumbPath",$dummy,$res);
			if($res) $thumbUrl = $img->getUrl();
			$this->showTime('made thumb');

		};
		$this->showTime('almost done');

#		return "$wgImageMagickConvertCommand $imagePath $imageMagickCommand $thumbPath";
		switch ($ret)
		{
		case 'url':
			return $thumbUrl;
		case 'base64':
			return chunk_split(base64_encode(file_get_contents($thumbPath))); 
		case 'img':
		default:		
			$imgTag = "<img src=\"$thumbUrl\" alt=\"$alt\" width=\"$thumbWidth\" height=\"$thumbHeight\"/>";
			return array($imgTag,'isHTML'=>true);
		}	
	}	
	
	function isValidFileName($name)
	{
		global $wgFileExtensions;
		if ( $name != wfStripIllegalFilenameChars( $name ) ) return false;
		$parts = explode( '.' , $name );
		if  (count( $parts )<2 || !in_array( strtolower( array_pop( $parts ) ) , $wgFileExtensions )) return 0;
		return true;
	}

	# check whether the string matches the dimension, and do as expected
	function matchDimension($string, $w, $h, &$x, &$y)
	{
		# check that it's not just an x, which would defeat our regexp
		if ($string=='x') return false;
		# is this a dimension in the format 100x100 or 50%x50% or -40x-40
		if ( !preg_match ( '/^(((-?[0-9]+)%)|(((-?[0-9]+)(%?))?\s*x\s*((-?[0-9]+)(%?))?))$/', $string, $m)) return false;
		
		list ($allall,$all, $single, $singleValue, $double, $left, $leftValue, $leftPercent, $right, $rightValue,$rightPercent) = $m;

		#reset the values;
		$x = false;
		$y = false;
		
		if ($single)
		{
			$x = (int)( $singleValue / 100 * $w);
			$y = (int)( $singleValue / 100 * $h);	
			return true;
		}
		# set the values, at least one will be set
		if ($left) $x = $leftPercent ? (int)( $leftValue / 100 * $w) : $leftValue;
		if ($right) $y = $rightPercent ? (int)( $rightValue / 100 * $h) : $rightValue;
		#check to see which one, if any, isn't set, and calculate it properly
		if ($x===false) 	$x=(int)($y/$h*$w);	
		elseif ($y===false)	$y=(int)($x/$w*$h);
#		die ("$x,$y");
		return true;
	}


	function fl_svg(&$parser,$f,$a)
	{
		global $wgUploadPath, $wgUploadDirectory, $wgImageMagickConvertCommand;
	    $args=new XxxArgs($f,$a);
	    $command=$args->command;
		if ($args->count<2) 
			return $this->notFound();	    
		if (!in_array($command,array('thumb','thumburl','extend','extendurl','crop','cropurl','stretch','stretchurl'))) 
			return $this->notFound();	    
		
		$val = $args->trimExpand(1);
		if (! $this->matchDimension($val,400,400,$x,$y))
			return $this->notFound();
			
		$width  = $x;
		$height = $y;
		if ($width <= 0 or $height <=0) return $this->notFound();
		
		if ($args->count==2)
		{
			$code = $args->cropExpand(2);
			$fileExt = 'png';
		}
		else
		{
			$code = $args->cropExpand(3);
			$fileExt = $args->cropExpand(2);
		}
		$hash = md5($code);
		$source = <<<END
<?xml version="1.0" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" 
"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">

<svg width="{$width}px" height="{$height}px" version="1.1"
xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http:///www.w3.org/1999/xlink">
$code
</svg>
END;

		$filePath = "svg/" . $hash{0} . "/" . $hash{0} . $hash{1};
		$fileDir = "$wgUploadDirectory/$filePath";
		
		$fileName = "{$width}_{$height}_{$hash}";
		
		$svgFile = "$fileDir/$fileName.svg";
		$outFile = "$fileDir/$fileName.{$fileExt}";
		$thumbUrl = "$wgUploadPath/$filePath/$fileName.{$fileExt}";
		
		if(!file_exists($svgFile) or $_GET['action']=='purge')
		{
			@mkdir($fileDir, 0777, true);
			@file_put_contents($svgFile, $source);
		}

		if(!file_exists(trim($outFile)) || $_GET['action']=='purge')
		{
			$imageMagickCommand  = 
					" $svgFile"
					. " -size '{$width}x{$height}!'"
					. " -blur 1x0.3"
					. " $outFile";
					
			@exec ("$wgImageMagickConvertCommand $imageMagickCommand",$dummy,$res);
		}

		if (!file_exists($outFile))
		{
			return "ERROR IN SVG: $wgImageMagickConvertCommand $imageMagickCommand  <br/> $res : $dummy (" . implode(", ",$dummy).")";
		}

		switch ($args->command)
		{
		case 'thumburl':
			return $thumbUrl;
		default:		
			$imgTag = "<img src=\"$thumbUrl\" alt=\"$alt\" width=\"$width\" height=\"$height\"/>";
			return array($imgTag,'isHTML'=>true);
		}
	}
}
