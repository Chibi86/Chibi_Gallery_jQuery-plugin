<?
// Get choosed data by url and hardcoded ones
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";

$i = $_GET;
$i['cache-path'] = __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
$i['path']       = __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR;
$i['webroot']    = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/";
$i['savePath']   = NULL;
$i['saveName']   = NULL;

require('classes/CImage/CImage.php');

// Get image class
$image = new CImage($i);

if(isset($i['verbose'])){
  $pagetitle = "Bildlogg ({$i['src']})";
?>
<!doctype html>
<html lang='en' class='no-js'>
<head>
  <meta charset='utf-8' />
  <title><?=$pagetitle;?></title>
  <link type="text/css" href="image.css">
</head>
<body>
   <h1><?=$pagetitle;?></h1>
   <div id="verbose">
    <?=$image->printVerbose();?>
   </div>
</body>
</html>
<?php
}
?>