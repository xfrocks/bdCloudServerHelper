<?php

class bdCloudServerHelper_XenForo_BbCode_Formatter_Base extends XFCP_bdCloudServerHelper_XenForo_BbCode_Formatter_Base
{
    protected function _generateProxyLink($proxyType, $url)
    {
        $firstEight = strtolower(substr($url, 0, 8));

        if (bdCloudServerHelper_Option::get('imageProxyIgnoreHttps') > 0
            && $firstEight === 'https://'
        ) {
            return $url;
        }

        if (substr($firstEight, 0, 5) === 'data:') {
            return $url;
        }

        $width = intval(bdCloudServerHelper_Option::get('imageProxyWidth'));
        if ($width > 0
            && $proxyType === 'image'
            // http://stackoverflow.com/questions/417142/what-is-the-maximum-length-of-a-url-in-different-browsers
            && strlen($url) < 2000
            && Zend_Uri::check($url)
        ) {
            if (defined('BDIMAGE_IS_WORKING')) {
                return bdImage_Integration::buildThumbnailLink($url, $width, bdImage_Integration::MODE_STRETCH_HEIGHT);
            }

            $thumbnailPath = bdCloudServerHelper_ShippableHelper_Image::getThumbnailPath($url, $width, '',
                'cloud/image');
            if (file_exists($thumbnailPath) && filesize($thumbnailPath) > 0) {
                return XenForo_Link::convertUriToAbsoluteUri(
                    bdCloudServerHelper_ShippableHelper_Image::getThumbnailUrl($url, $width, '', 'cloud/image'), true);
            } else {
                return sprintf('cloud/image.php?url=%s&width=%d&hash=%s', urlencode($url), $width,
                    urlencode(bdCloudServerHelper_Helper_Crypt::oneWayHash(array($url, $width))));
            }
        }

        return parent::_generateProxyLink($proxyType, $url);
    }

}