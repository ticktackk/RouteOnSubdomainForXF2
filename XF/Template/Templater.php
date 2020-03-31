<?php

namespace TickTackk\RouteOnSubdomain\XF\Template;

use XF\Pub\App as PubApp;

/**
 * Class Templater
 *
 * @package TickTackk\RouteOnSubdomain\XF\Template
 */
class Templater extends XFCP_Templater
{
    /**
     * Extends 'base_url' template helper to take care of the base url(?)
     *
     * @param self        $templater
     * @param bool        $escape
     * @param null|string $url
     * @param bool        $full
     *
     * @return string
     */
    public function fnBaseUrl($templater, &$escape, $url = null, $full = false)
    {
        $app = $this->app;
        $urlPrefix = '';

        if ($app instanceof PubApp)
        {
            $baseHost = $app->container('router.public.baseHost');
            if (
                $app->container('router.public.hasRoutesOnSubdomain') &&
                $app->request()->getHost() !== $baseHost
            )
            {
                $full = false;
                $urlPrefix = $app->request()->getProtocol() . '://' . $baseHost;
            }
        }

        return $urlPrefix . parent::fnBaseUrl($templater, $escape, $url, $full);
    }
}