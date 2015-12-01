<?php

$url = filter_input(INPUT_GET, 'url');
$width = filter_input(INPUT_GET, 'width', FILTER_VALIDATE_INT);
$hash = filter_input(INPUT_GET, 'hash');
if (empty($url)
    || empty($width)
    || empty($hash)
) {
    header('HTTP/1.0 406 Not Acceptable');
    exit;
}

$startTime = microtime(true);

// we have to figure out XenForo path
// dirname(dirname(__FILE__)) should work most of the time
// as it was the way XenForo's index.php does
// however, sometimes it may not work...
// so we have to be creative
$parentOfDirOfFile = dirname(dirname(__FILE__));
$scriptFilename = (isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '');
$pathToCheck = '/library/XenForo/Autoloader.php';
$fileDir = false;
if (file_exists($parentOfDirOfFile . $pathToCheck)) {
    $fileDir = $parentOfDirOfFile;
}
if ($fileDir === false AND !empty($scriptFilename)) {
    $parentOfDirOfScriptFilename = dirname(dirname($scriptFilename));
    if (file_exists($parentOfDirOfScriptFilename . $pathToCheck)) {
        $fileDir = $parentOfDirOfScriptFilename;
    }
}
if ($fileDir === false) {
    die('XenForo path could not be figured out...');
}
// finished figuring out $fileDir

// change directory to mimics the XenForo environment as much as possible
chdir($fileDir);

require($fileDir . '/library/XenForo/Autoloader.php');
XenForo_Autoloader::getInstance()->setupAutoloader($fileDir . '/library');

XenForo_Application::initialize($fileDir . '/library', $fileDir);
XenForo_Application::set('page_start_time', $startTime);

$dependencies = new XenForo_Dependencies_Public();
$dependencies->preLoadData();

if ($hash !== bdCloudServerHelper_Helper_Crypt::oneWayHash(array($url, $width))) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$thumbnailUrl = bdCloudServerHelper_ShippableHelper_Image::getThumbnailUrl($url, $width, '', 'cloud/image');
if (empty($thumbnailUrl)) {
    header('HTTP/1.0 500 Internal Server Error');
    exit;
}

if (!Zend_Uri::check($thumbnailUrl)) {
    $thumbnailUrl = sprintf('%s/%s',
        rtrim(XenForo_Application::getOptions()->get('boardUrl'), '/'),
        ltrim($thumbnailUrl, '/'));
}

header(sprintf('Location: %s', $thumbnailUrl));
exit;