<?php

class bdCloudServerHelper_Route_PrefixAdmin_Cloud implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        return $router->getRouteMatch('bdCloudServerHelper_ControllerAdmin_Cloud', $routePath, 'bdCloudServerHelper');
    }
}