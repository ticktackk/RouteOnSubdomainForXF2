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
     * @param self        $templater
     * @param bool        $escape
     * @param null|string $url
     * @param bool        $full
     *
     * @return mixed
     */
    public function fnBaseUrl($templater, &$escape, $url = null, $full = false)
    {
        $app = $this->app;
        $urlPrefix = '';

        if ($app instanceof PubApp)
        {
            $primaryHost = $app->container('router.public.primaryHost');
            if (
                $app->container('router.public.allowRoutesOnSubdomain') &&
                $primaryHost !== null &&
                $app->request()->getHost() !== $primaryHost
            )
            {
                $full = false;
                $urlPrefix = $app->request()->getProtocol() . '://' . $primaryHost;
            }
        }

        return $urlPrefix . parent::fnBaseUrl($templater, $escape, $url, $full);
    }
}