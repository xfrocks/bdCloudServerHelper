<?php

class bdCloudServerHelper_XenForo_BbCode_Formatter_Base extends XFCP_bdCloudServerHelper_XenForo_BbCode_Formatter_Base
{
    protected function _generateProxyLink($proxyType, $url)
    {
        $width = intval(bdCloudServerHelper_Option::get('imageProxyWidth'));
        if ($width > 0
            && $proxyType === 'image'
            && Zend_Uri::check($url)
        ) {
            $thumbnailPath = bdCloudServerHelper_ShippableHelper_Image::getThumbnailPath($url, $width, '', 'cloud/image');
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