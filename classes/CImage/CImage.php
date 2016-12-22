<?
/**
 * Chipla image-module, crop img on-the-fly, store generated images in cache
 * Made by Rasmus Berg (c) 2014-2016
 * License under MIT license
 */

Class CImage{
	
	/**
   * Constant for default values
   */
  const JPEG_QUALITY_DEFAULT = 60;		 // JPEG Quality deafult value
  const PNG_COMPRESSION_DEFAULT = -1;	 // Set PNG Compression default value
	const MAX_WIDTH_DEFAULT = 2000;			 // Set max width default value
	const MAX_HEIGHT_DEFAULT = 2000;		 // Set max height default value
	const USE_CACHE_DEFAULT = true;			 // Set cache on (true) / off (false) by deafult
	
	/**
    * Properties
    */
	
	// Hardcoded settings
	private $allowedExtensions  = array('png', 'jpg', 'jpeg', 'gif');
	
	// Settings
	private $useCache; 			// Choosed cache on (true) / off (false).
	private $cachePath;			// Choosed cache
	private $maxWidth;			// Choosed max width
	private $maxHeight;			// Choosed max height
	private $verbose;			// Choosed of verbose-mode with information or not
	
	// Existing image
	private $imageSrc;			// Image name (and possibly subpath)
	private $imagePath;			// Image path
	private $imagePathSrc;		// Image real source (path + src)
	private $fileExtension;		// Image extension
	private $width; 			// Image width
	private $height; 			// Image height
	private $filesize;			// Image filesize
	private $lastModified; 		// Image last edit
	
	// To the new image
	private $image;				// New image resource
	private	$cacheName;			// Cache name (automatic name)
	private $saveName;			// New name (picked name)
	private $savePath;			// New path (picked path)
	private $saveSrc;			// New source (path + name)
	private $saveAs;			// New extension
	private $saveWidth;			// New width
	private $saveHeight;		// New height
	private	$cropHeight;		// Crop height 
	private $cropWidth;			// Crop width
	private $cropToFit;			// Crop image to fit
	private $stretch;			// Ignore existing ratio and change width and height directly by asked ones
	private $quality;			// JPEG Quality
	private $compress;			// PNG Compression
	private $blackWhite;		// Choose to filter image into black and white
	private $sepia;				// Choose to filter image into serpia
	private $sharpen;			// Choose to sharpen image 
	private $blur;				// Choose to blur image
	private $emboss;			// Choose to emboss image
	private $notWriteable;		// If path not writeable
	
	// To log anything in verbose-mode
	private $logs = array();
	// To log error msg
	private $errorLogs = array();
	
	/**
	* Constructor
	*/
	public function __construct($i) {
	
		// Some querys has longer and shorter variants
		$i = $this->getShortQuerys($i);
		// Get file extension
		$i['ext'] = isset($i['src']) && isset(pathinfo($i['src'])["extension"]) ? pathinfo($i['src'])["extension"] : NULL;
		
		
		// Settings
		$this->useCache 		= isset($i['cache']) 												? $i['cache'] 				: self::USE_CACHE_DEFAULT; 
		$this->cachePath 		= isset($i['cache-path']) 									? $i['cache-path'] 		: NULL;
    $this->webroot      = isset($i['webroot'])                      ? $i['webroot']       : NULL; 
		$this->maxWidth 		= isset($i['max-width']) && is_numeric($i['max-width'])		? $i['max-width']			: self::MAX_WIDTH_DEFAULT;	
		$this->maxHeight 		= isset($i['max-height']) && is_numeric($i['max-height'])	? $i['max-height'] 			: self::MAX_HEIGHT_DEFAULT;	
		$this->verbose 			= isset($i['verbose']) 											? true 						: false;	
		
		// Existing image
		$this->imageSrc  			= isset($i['src']) && !is_null($i['src'])					? $i['src'] 				: NULL;
		$this->imagePath			= isset($i['path']) 										? $i['path'] 				: NULL;
		$this->imagePathSrc 	= isset($i['src']) && isset($i['path'])						? $i['path'] . $i['src']	: NULL;	
		$this->fileExtension 	= isset($i["ext"]) 											? strtolower($i["ext"]) 	: NULL;
		
		// To the new image
		$this->saveName			= isset($i['name']) 										? $i['name']				: NULL;
		$this->savePath			= isset($i['save-path']) 									? $i['save-path']			: NULL;
		$this->saveAs				= isset($i['as']) 											? strtolower($i['as'])		: $this->fileExtension;
		$this->saveWidth		= isset($i['w']) && is_numeric($i['w'])						? $i['w']					: NULL;
		$this->saveHeight		= isset($i['h']) && is_numeric($i['h']) 					? $i['h']					: NULL;
		$this->cropToFit		= isset($i['crop2fit']) 									? true						: false;
		$this->stretch			= isset($i['stretch']) 										? true						: false;
		$this->quality			= isset($i['q']) && is_numeric($i['q'])						? $i['q']					: self::JPEG_QUALITY_DEFAULT;
		$this->compress			= isset($i['c']) && is_numeric($i['c']) 					? $i['c']					: self::PNG_COMPRESSION_DEFAULT;
		$this->blackWhite		= isset($i['bw'])											? true						: false;
		$this->sepia				= isset($i['sepia'])										? true						: false;
		$this->sharpen			= isset($i['sharpen']) 										? true						: false;
		$this->blur					= isset($i['blur']) 										? true						: false;
		$this->emboss				= isset($i['emboss']) 										? true						: false;
		
		// Prepare save information
		$this->prepare();
		
		//	Validate the incoming values          
		if(!$this->validate()){
			// If validation fail abort and print error msg
			$this->processError(null, true);
		}
		else{
			// Get image information
			if(!$this->getImageInfo()){
				// If open file fail abort and print error msg
				$this->processError(null, true);
			}
			else{
				// Save new source to image
				$this->saveToLog("Given new path and filename: {$this->saveSrc}");
				// Get cached image
				$this->getCachedImage();
			}
		}
		
		
	}
	
	/**
	 * Validate the incoming values
	 *
	 */
	private function validate(){
	
		if(strlen($this->imageSrc) < 4) 
			$this->errorMessage('The file doesn\'t seem to be an image.', 'noIm', true);
		//$this->savePath = $this->cachePath = NULL;
		if(!(is_writable($this->savePath) || is_writable($this->cachePath))){
			$this->errorMessage('The saving/cache path is not writeable.', 'ntWb', true);
			$this->notWriteable = true;
		}
		else if(!is_writable($this->savePath)) $this->savePath = $this->cachePath;
		if(is_null($this->fileExtension) || !in_array($this->fileExtension, $this->allowedExtensions)) 
			$this->errorMessage('Not a valid extension on existing image', 'ext', true);
		if(!is_null($this->saveAs) && !in_array($this->saveAs, $this->allowedExtensions)) 
			$this->errorMessage('Not a valid extension to save image as', 'saEx', true);
		if(($this->saveAs == "jpg" || $this->saveAs == "jpeg") && !(is_numeric($this->quality) && $this->quality > 0 && $this->quality <= 100))
			$this->errorMessage('Quality out of range','qua', true);
		if($this->saveAs == "png" && !(is_numeric($this->compress) && $this->compress >= -1 && $this->compress <= 9))
			$this->errorMessage('PNG compress out of range','comp', true);
		if(!is_null($this->saveWidth) && !(is_numeric($this->saveWidth) && $this->saveWidth > 0 && $this->saveWidth <= $this->maxWidth)) 
			$this->errorMessage('Width out of range','wdh', true);
		if(!is_null($this->saveHeight) && !(is_numeric($this->saveHeight) && $this->saveHeight > 0 && $this->saveHeight <= $this->maxHeight)) 
			$this->errorMessage('Height out of range', 'hgt', true);
		if($this->cropToFit && !(is_numeric($this->saveWidth) && is_numeric($this->saveHeight)))
			$this->errorMessage('Crop to fit needs both width and height to work', 'c2f', true);
		if($this->stretch && !$this->saveWidth && !$this->saveHeight)
			$this->errorMessage('Stretch image not working with orginal size', 'sth', true);
			
		return (count($this->errorLogs) == 0);
		
	}
	
	/**
	 * Get information about image
	 *
	 */
	private function getImageInfo(){
		
		$file = $this->imagePathSrc;
		$info = is_file($file) ? list($this->width, $this->height, $type, $attr) = getimagesize($file) : array();
		
		if(!empty($info)){
			
			// Get information about image and save to variabels
			$mime   				= $info['mime'];
			$this->fileExtension 	= in_array(pathinfo($file)["extension"], $this->allowedExtensions) ? pathinfo($file)["extension"] : NULL;
			$this->filesize 		= filesize($file);
			$this->lastModified		= filemtime($file);
			
			// Start printing verbose log
			$this->firstLog();
			$this->saveToLog("Image file: {$file}");
			$this->saveToLog("Image information: " . print_r($info, true));
			$this->saveToLog("Image width x height (type): {$this->width} x {$this->height} ({$type}).");
			$this->saveToLog("Image file size: {$this->filesize} bytes.");
			$this->saveToLog("Image mime type: {$mime}.");
			
		}
		else{
			$this->errorMessage("The file doesn't seem to be an image.", "noIm", true);
		}
		
		return (count($this->errorLogs) == 0);
	}
	
	/**
	 * Prepare save information
	 *
	 */
	private function prepare(){
	
		if(!isset($this->saveName)) $this->makeSaveName();
		
		$this->saveAs	= !isset($this->saveAs) 	? $this->fileExtension	: $this->saveAs;
		$this->savePath = !is_null($this->savePath) ? $this->savePath 		: $this->cachePath;
		
		$this->saveSrc = "{$this->savePath}{$this->saveName}.{$this->saveAs}";
		
	}
	
	/**
	 * Get cached image
	 *
	 */	
	private function getCachedImage(){
		
		$exist = is_file($this->saveSrc);
		$cacheModifiedTime = ($exist) ? filemtime($this->saveSrc) : 0;
		
		if($cacheModifiedTime > $this->lastModified && $this->useCache && $exist){
			$this->saveToLog("Cache file is valid, output it.");
		}
		else{
				
			if(!$exist) 								 		$log = "Cached file does not exist, so creating a new image.";
			elseif($cacheModifiedTime < $this->lastModified)	$log = "Cached file was to old, so creating a new image.";
			else												$log = "Cache is off, so creating a new image";
			$this->saveToLog($log);
			
			$this->createCachedImage();
			
		}
		
		$this->outputImage();
	}
	
	/**
	 * Create cached image
	 *
	 */
	private function createCachedImage(){
			
		//open the original file 
        switch($this->fileExtension) {  
            case 'jpg':
            case 'jpeg': 
                $this->image = @imagecreatefromjpeg($this->imagePathSrc);
				$this->saveToLog("Opened the image as a JPEG image.");
            break;  
			case 'gif':
				$this->image = @imagecreatefromgif($this->imagePathSrc);
				$this->saveToLog("Opened the image as a GIF image.");
            break;   
            case 'png':  
                $this->image = @imagecreatefrompng($this->imagePathSrc); 
                $this->saveToLog("Opened the image as a PNG image.");
            break;  
        }
		
		//	
		// Apply filters and postprocessing of image
		//
		
		// Resize image
		if((isset($this->saveWidth) && $this->saveWidth != $this->width) || (isset($this->saveHeight) && $this->saveHeight != $this->height)){
			$this->resizeImage();
		}
		
		// Multi filters to make Sepia on image
		if($this->sepia) {
			$this->sepiaImage();
		}
		
		// Black and white filter on image
		if($this->blackWhite) {
			$this->blackAndWhiteImage();
		}
		
		// Sharpen image		
		if($this->sharpen) {
			$this->sharpenImage();
		}
		
		// Emboss image		
		if($this->emboss) {
			$this->embossImage();
		}
		
		// Blur image	
		if($this->blur) {
			$this->blurImage();
		}
			
		//save it to cache/save folder
		switch($this->saveAs) {
			case 'jpeg':
			case 'jpg':
				$this->saveToLog("Saving image as JPEG to cache using quality = {$this->quality}.");
				imagejpeg($this->image, $this->saveSrc, $this->quality);
			break;  
			
			case 'gif':
				$this->saveToLog("Saving image as GIF to cache.");
				imagegif($this->image, $this->saveSrc);
			break;  

			case 'png':  
				$this->saveToLog("Saving image as PNG to cache using compression = {$this->compress}.");
				// Turn off alpha blending and set alpha flag
				imagealphablending($this->image, false);
				imagesavealpha($this->image, true);
				imagepng($this->image, $this->saveSrc, $this->compress); 
			break;  
		}
		imagedestroy($this->image);
	}
	
	/**
	 * Output image
	 *
	 */
	private function outputImage(){
		$info = list($this->width, $this->height, $type, $attr) = getimagesize($this->saveSrc);
		
		if(!empty($info)){
			$mime   				= $info['mime'];
			$lastModified 			= filemtime($this->saveSrc);
			$gmdate 				= gmdate("D, d M Y H:i:s", $lastModified);
			
			$this->saveToLog("Memory peak: " . round(memory_get_peak_usage() /1024/1024) . "M");
			$this->saveToLog("Memory limit: " . ini_get('memory_limit'));
			$this->saveToLog("Time is {$gmdate} GMT.");
			if(!$this->verbose) header('Last-Modified: ' . $gmdate . ' GMT');
			
			if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModified){
				$this->saveToLog("Would send header 304 Not Modified, but its verbose mode.");
				if(!$this->verbose)header('HTTP/1.0 304 Not Modified');
			}
			else{  
				$this->saveToLog("Would send header to deliver image with modified time: {$gmdate} GMT, but its verbose mode.");
				if(!$this->verbose){
					header('Content-type: ' . $mime); 
					
					readfile($this->saveSrc);
				}
			}
		}
	}
	
	/*
	 *****************[Verbose, Logs & Error-handle]*****************
	 */
	
	/**
     * Save error message to error log.
     *
     * @param string $message the error message to display.
     * @param boolean $noImage If abort is impossibly to avoid, print msg.
     */
    private function errorMessage($message, $key, $noImage = false){
        $this->errorLogs[$key] = $message;
		if(!$noImage && $this->verbose){
			$this->processError($message);
		}
    }
	
	/**
     * Add/print error-logs to verbose or image.
     *
     * @param string  $message The error message to display.
     * @param boolean $noImage If abort is impossibly to avoid, print error-log.
     */
	private function processError($message, $noImage = false){
		if($noImage){
			header("Status: 404 Not Found");
			if($this->verbose){
				$this->saveToLog("<div class=\"error\">",true);
				$this->saveToLog(" <h3>404 - Image could not be open</h3>", true);
				foreach($this->errorLogs AS $message){
					$this->saveToLog(" - {$message}");
				}
				$this->saveToLog("</div>", true);
			}
			else $this->createErrorImage();
		}
		elseif($this->verbose){
			$this->saveToLog("Error: {$message}");
		}
	}
	
	/**
	 * Create image with error msg
	 *
	 */
	private function createErrorImage(){
	
		$vertMargin = 14;
		$horzMargin	= 11;
		$minHeight 	= (count($this->errorLogs) * 25) + 15 + ($horzMargin*2);
		$minWidth 	= 326 + ($vertMargin*2);
		$border		= 5;
		
		$this->saveHeight 	= is_numeric($this->saveHeight) && $this->saveHeight > $minHeight  	? $this->saveHeight : $minHeight;
		$this->saveWidth 	= is_numeric($this->saveWidth)  && $this->saveWidth  > $minWidth	? $this->saveWidth 	: $minWidth;
		if(!$this->notWriteable){
			$this->saveName = "error_{$this->saveHeight}_{$this->saveWidth}";
			foreach($this->errorLogs AS $key => $message){
				$this->saveName .= "_{$key}";
			}
			$this->saveSrc = $this->savePath . $this->saveName . ".png";
		}
		
		$exist = !is_file($this->saveSrc);
		
		if($exist){
			$this->image = imagecreatetruecolor($this->saveWidth , $this->saveHeight);
			
			$borderC = imagecolorallocate ($this->image, 251, 194, 196);
			imagefill($this->image, 0, 0, $borderC);
			 
			$backgroundC = imagecolorallocate($this->image, 251, 227, 228);
			imagefilledrectangle($this->image, $border, $border, ($minWidth-$border-1), ($minHeight-$border-1), $backgroundC);
			
			$i = 11;
			$textC = imagecolorallocate ( $this->image , 138, 31, 17);
			imagestring($this->image, 5, $vertMargin, $i,  "404 - Image could not be open", $textC);
			imagestring($this->image, 5, $vertMargin, $i+3,  "_____________________________", $textC);
			
			foreach($this->errorLogs AS $message){
				$i+=25;
				imagestring($this->image, 3, $vertMargin, $i,  $message, $textC);
			}
			
			if(!$this->notWriteable){
				imagepng($this->image, $this->saveSrc);
			}
			else{
				header('Content-type: image/png'); 
				imagepng($this->image);
			}
			
			imagedestroy($this->image);
		}
		
		if(!$this->notWriteable || $exist) $this->outputImage();
		
	}
	
	/**
	 * Start log in verbose, with image and url to only image
	 *
	 */
	private function firstLog(){
		$query = array();
		parse_str($_SERVER['QUERY_STRING'], $query);
		unset($query['verbose']);
		unset($query['src']);
		$url = $this->webroot . "image/{$this->imageSrc}?" . preg_replace(array("/\=\&/", "/\=$/"), array("&", NULL), http_build_query($query));
		
		$this->saveToLog("<p><a href=\"{$url}\"><img src='{$url}' /></a></p>", true);
		$this->saveToLog("<p>Image url: <a href=\"{$url}\">{$url}</a></p>", true);

	}
	
	/**
	 * Save text to log
	 *
	 */
	private function saveToLog($text, $html = false){
		$this->logs[] = !$html ? "<p>" . htmlentities($text) . "</p>" : $text;
	}
	
	/**
	 * Print verbose
	 *
	 */
	public function printVerbose(){
		$text = NULL;
		foreach ($this->logs AS $log){
			$text .= ("
	{$log}
");
		}
		return $text;
	}
	
	/*
	 *****************[Filters & postprocessing functions]*****************
	 */
	
	/**
	 * Resize image
	 *
	 */
	private function resizeImage(){
		$this->calculateNewSize();
		
		//crop and/or resize
        if($this->cropToFit) {
            $this->saveToLog("Resizing, crop to fit.");
            $cropX  = round(($this->width - $this->cropWidth) / 2);  
            $cropY  = round(($this->height - $this->cropHeight) / 2);    
            $imageResized = $this->createImageKeepTransparency();
            imagecopyresampled($imageResized, $this->image, 0, 0, $cropX, $cropY, $this->saveWidth, $this->saveHeight, $this->cropWidth, $this->cropHeight);
            $this->image = $imageResized;
        }
        else if(!($this->saveWidth == $this->width && $this->saveHeight == $this->height)) {
            $this->saveToLog("Resizing, new height and/or width.");
            $imageResized = $this->createImageKeepTransparency();
            imagecopyresampled($imageResized, $this->image, 0, 0, 0, 0, $this->saveWidth, $this->saveHeight, $this->width, $this->height);
            $this->image  = $imageResized;
        }
	}
	
	/** 
	 * Filter to make image black and white
	 *
	 */
	public function blackAndWhiteImage(){
		// Apply filter
        $this->saveTolog("Applying black and white filter to image.");
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
	}
	
	/**
     * Multi-filters to make sepia on image
     *
     */	 
	public function sepiaImage(){
		
		// Apply filters
		$this->blackAndWhiteImage();
        $this->saveTolog("Applying brigtness filter to image.");
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, -10);
		$this->saveTolog("Applying contrast filter to image.");
        imagefilter($this->image, IMG_FILTER_CONTRAST, -20);
		$this->saveTolog("Applying colorize filter to image.");
        imagefilter($this->image, IMG_FILTER_COLORIZE, 120,60,0,0);
		$this->sharpenImage();
		$this->saveTolog("Applying sepia filter to image.");
		
		// Only run ones
		$this->blackWhite = false;
		$this->sharpen = false;
		
	}
	
	/**
     * Sharpen image as http://php.net/manual/en/ref.image.php#56144
     * http://loriweb.pair.com/8udf-sharpen.html
     * 
     */
    public function sharpenImage() 
    {
        $matrix = array(
            array(-1,-1,-1,),
            array(-1,16,-1,),
            array(-1,-1,-1,),
        );

        $divisor = 8;
        $offset  = 0;
        
        imageconvolution($this->image, $matrix, $divisor, $offset);
        
		if($this->verbose) { $this->saveToLog("Sharpen image."); }

    }

    /**
     * Emboss image as http://loriweb.pair.com/8udf-emboss.html
     * 
     */
    public function embossImage() 
    {
        $matrix = array(
            array( 1, 1,-1,),
            array( 1, 3,-1,),
            array( 1,-1,-1,),
        );
    
        $divisor = 3;
        $offset  = 0;
    
        imageconvolution($this->image, $matrix, $divisor, $offset);
		
		if($this->verbose) { $this->saveToLog("Emboss image."); }
    
    }

    /**
     * Blur image as http://loriweb.pair.com/8udf-basics.html
     * 
     */
    public function blurImage() 
    {
        $matrix = array(
            array( 1, 1, 1,),
            array( 1,15, 1,),
            array( 1, 1, 1,),
        );
    
        $divisor = 23;
        $offset  = 0;
    
        imageconvolution($this->image, $matrix, $divisor, $offset);
		if($this->verbose) { $this->saveToLog("Blur image."); }
    
    }
	
	/**
	 * Create new image and keep transparency
	 *
	 */
	public function createImageKeepTransparency() {
		$image = imagecreatetruecolor($this->saveWidth, $this->saveHeight);
		imagealphablending($image, false);
		imagesavealpha($image, true);
		
		return $image;
	}
	
	/*
	 **************************[Help-functions]**************************
	 */
	
	/**
	 * Make querys to shorter variants
	 *
	 * @params $i array with querys and settings
	 * @return $i array with querys and settings
	 */
	private function getShortQuerys($i){
		$i['h']			= (!isset($i['h']) || !is_numeric($i['h'])) && isset($i['height'])	? 	$i['height'] 	: (isset($i['h']) 		? $i['h'] 		: NULL);
		$i['w'] 		= (!isset($i['w']) || !is_numeric($i['w'])) && isset($i['width'])	? 	$i['width']  	: (isset($i['w']) 		? $i['w']  		: NULL);
		$i['q'] 		= (!isset($i['q']) || !is_numeric($i['q'])) && isset($i['quality'])	? 	$i['quality']	: (isset($i['q']) 		? $i['q']  		: NULL);
		$i['c']			= (!isset($i['c']) || !is_numeric($i['c'])) && isset($i['compress'])?	$i['compress']	: (isset($i['c'])		? $i['c']		: NULL);
		$i['as']		= !isset($i['as']) 		&& isset($i['save-as'])						? 	$i['save-as']  	: (isset($i['as']) 		? $i['as'] 		: NULL);
		$i['name'] 		= !isset($i['name']) 	&& isset($i['save-name']) 					? 	$i['save-name'] : (isset($i['name'])	? $i['name']	: NULL);
		$i['crop2fit'] 	= !isset($i['crop2fit'])&& isset($i['croptofit']) 					? 	true 			: (isset($i['crop2fit'])? true			: NULL);
		$i['cache'] 	= !isset($i['cache']) 	&& isset($i['use-cache'])					?   true 			: (isset($i['cache'])	? $i['cache']	: NULL);
		$i['cache'] 	= !isset($i['cache']) 	&& isset($i['no-cache'])					?   false 			: (isset($i['cache'])	? $i['cache'] 	: NULL);
		$i['bw']		= !isset($i['bw'])		&& isset($i['blackwhite'])					?	true			: (isset($i['bw'])		? $i['bw']	 	: NULL);
		
		return $i;
	}
	
	/**
	 * Make a automatic filename to cache-file
	 *
	 */
	private function makeSaveName(){
		$i['dir']      	= preg_replace('/\//', '-', dirname($this->imageSrc)) . "-";
		$i['name']		= pathinfo($this->imageSrc)['filename'];
		$i['width']		= is_null($this->saveWidth)		? "_{$this->width}"		: "_{$this->saveWidth}";
		$i['height']	= is_null($this->saveHeight)	? "_{$this->height}"	: "_{$this->saveHeight}";
		$i['cropToFit']	= !$this->cropToFit 			? NULL 					: "_cf";
		$i['stretch']  	= !$this->stretch				? NULL					: "_sh";
	   if($this->saveAs == "jpg" || $this->saveAs == "jpeg"){
		$i['quality']  = is_null($this->quality)		? NULL 					: "_q{$this->quality}";
	   }
	   else if($this->saveAs == "png"){
		$i['compress'] = is_null($this->compress)		? NULL					: "_c{$this->compress}";
	   }
		$i['blackWhite']= !$this->blackWhite			? NULL					: "_bw";
		$i['sepia']    	= !$this->sepia					? NULL					: "_sa";
		$i['sharpen']   = !$this->sharpen 				? NULL 					: "_sn";
		$i['blur']      = !$this->blur					? NULL					: "_br";
		$i['emboss']    = !$this->emboss				? NULL					: "_es";
		
		$name = NULL;
		foreach($i AS $part){
			$name .= $part;
		}
		
		$this->saveName = preg_replace('/^a-zA-Z0-9\.-_/', '', $name);

	}
	
	/**
	 * Calculate new width and heigth
	 *
	 */
	public function calculateNewSize(){
		
		// Calculate new width and height for the image
		$aspectRatio = $this->width / $this->height;

		if($this->cropToFit && $this->saveWidth && $this->saveHeight) {
		  $targetRatio = $this->saveWidth / $this->saveHeight;
		  $this->cropWidth  = ($targetRatio > $aspectRatio ? $this->width : round($this->height * $targetRatio));
		  $this->cropHeight  = ($targetRatio > $aspectRatio ? round($this->width  / $targetRatio) : $this->height);
		  $this->saveToLog("Crop to fit into box of {$this->saveWidth}x{$this->saveHeight}. Cropping dimensions: {$this->cropWidth}x{$this->cropHeight}.");
		}
		else if($this->saveWidth && !$this->saveHeight && !$this->stretch) {
		  $this->saveHeight = round($this->saveWidth / $aspectRatio);
		  $this->saveToLog("New width is known {$this->saveWidth}, height is calculated to {$this->saveHeight}.");
		}
		else if(!$this->saveWidth && $this->saveHeight && !$this->stretch) {
		  $this->saveWidth = round($this->saveHeight * $aspectRatio);
		  $this->saveToLog("New height is known {$this->saveHeight}, width is calculated to {$this->saveWidth}.");
		}
		else if($this->saveWidth && $this->saveHeight && !$this->stretch) {
		  $ratioWidth  = $this->width  / $this->saveWidth;
		  $ratioHeight = $this->height / $this->saveHeight;
		  $ratio = ($ratioWidth > $ratioHeight) ? $ratioWidth : $ratioHeight;
		  $this->saveWidth = round($this->width  / $ratio);
		  $this->saveHeight = round($this->height / $ratio);
		  $this->saveToLog("New width & height is requested, keeping aspect ratio results in {$this->saveWidth}x{$this->saveHeight}.");
		}
		else if(!$this->saveWidth && $this->saveHeight){
		  $ratioWidth  = $this->width;
		  $ratioHeight = $this->height / $this->saveHeight;
		  $ratio = ($ratioWidth > $ratioHeight) ? $ratioWidth : $ratioHeight;
		  $this->saveToLog("New width & height is requested, stretch to fit selected aspect {$this->saveWidth}x{$this->saveHeight}.");
		}
		else{
		  $this->saveWidth  = isset($this->saveWidth) ? $this->saveWidth : $this->width;
		  $this->saveHeight = isset($this->saveHeight) ? $this->saveHeight : $this->height;
		  $this->saveToLog("New width & height is requested, stretch to fit selected aspect {$this->saveWidth}x{$this->saveHeight}.");
		}
		
	}
}
?>