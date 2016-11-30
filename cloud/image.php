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

require ('bootstrap.php');

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