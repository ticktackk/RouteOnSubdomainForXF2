<?php

namespace TickTackk\RouteOnSubdomain\XF\Mvc;

use XF\Http\Request;
use XF\Mvc\RouteMatch;
use XF\Pub\App as PubApp;

/**
 * Class Router
 *
 * @package TickTackk\RouteOnSubdomain\XF\Mvc
 */
class Router extends XFCP_Router
{
    /**
     * This will be set to true if all required configuration is setup and there are routes available to be routed to a
     * subdomain.
     *
     * @var bool
     */
    protected $subDomainSupportEnabled = false;

    /**
     * This is the primary host that will be used for checking if the current host has a subdomain which is a valid route
     *
     * @var null|string
     */
    protected $primaryHost;

    /**
     * Router constructor.
     *
     * Sets $subDomainSupportEnabled and $primaryHost values.
     *
     * @param null  $linkFormatter
     * @param array $routes
     */
    public function __construct($linkFormatter = null, array $routes = [])
    {
        parent::__construct($linkFormatter, $routes);

        $app = \XF::app();

        if ($app instanceof PubApp)
        {
            $this->subDomainSupportEnabled = $app->container('router.public.allowRoutesOnSubdomain');
            if ($this->subDomainSupportEnabled)
            {
                $this->primaryHost = $app->container('router.public.primaryHost');
            }
        }
    }

    /**
     * Extened to parse the provided path to a route (if any set via subdomain)
     *
     * @param string       $path
     * @param Request|null $request
     *
     * @return RouteMatch
     */
    public function routeToController($path, Request $request = null)
    {
        if ($request && $this->subDomainSupportEnabled)
        {
            $app = \XF::app();
            $routesOnSubdomain = $app->container('router.public.routesOnSubdomain');

            $paths = explode('/', $path);
            $hostParts = explode($this->primaryHost, $request->getHost());
            $routeFromSubdomain = $hostParts[0] ?? null;

            if ($routeFromSubdomain)
            {
                $routeFromSubdomain = substr($routeFromSubdomain, 0, strlen($routeFromSubdomain) - 1);
                if ($routesOnSubdomain[$routeFromSubdomain] ?? false)
                {
                    if ($paths[0] === '')
                    {
                        unset($paths[0]);
                    }

                    array_unshift($paths, $routeFromSubdomain); // assume the route is exists from it exists in the cache
                    $path = implode('/', $paths);
                }
                else
                {
                    // make this subdomain useless
                    return $this->getNewRouteMatch();
                }
            }
            else
            {
                $possibleRoute = $paths[0];
                if ($routesOnSubdomain[$possibleRoute] ?? false) // accessing normal url but needs to be redirected to the new url
                {
                    $protocol = $request->getProtocol();
                    $app->response()->header('Location', "{$protocol}://{$possibleRoute}.{$this->primaryHost}/");
                }
            }
        }

        return parent::routeToController($path, $request);
    }

    /**
     * Extended to maintain <route>.<host> when required
     *
     * @param string $modifier
     * @param string $routeUrl
     * @param array  $parameters
     *
     * @return string
     */
    public function buildFinalUrl($modifier, $routeUrl, array $parameters = [])
    {
        if ($this->subDomainSupportEnabled && is_string($routeUrl))
        {
            $app = \XF::app();
            $request = $app->request();

            $routeFromHost = explode($this->primaryHost, $request->getHost())[0] ?? null;
            $routeUrlParts = explode('/', $routeUrl);

            if (\count($routeUrlParts) === 1) // default index
            {
                $routeFromUrl = $app->options()->forumsDefaultPage;
            }
            else
            {
                $routeFromUrl = $routeUrlParts[0];
            }

            if ($modifier === null || $modifier === 'nopath')
            {
                $modifier = 'full';
            }

            $routesOnSubdomain = $app->container('router.public.routesOnSubdomain');
            if (array_key_exists($routeFromUrl, $routesOnSubdomain) && $routesOnSubdomain[$routeFromUrl] === true)
            {
                if (\count($routeUrlParts) > 1) // if not default route
                {
                    unset($routeUrlParts[0]);
                }

                $routeUrl = implode('/', $routeUrlParts);
                $finalUrl = parent::buildFinalUrl($modifier, $routeUrl, $parameters);

                if ($routeFromHost !== $routeFromUrl)
                {
                    $finalUrl = str_replace($routeFromHost, $routeFromUrl . '.', $finalUrl);
                }

                return $finalUrl;
            }
            else
            {
                $routeUrl = implode('/', $routeUrlParts);

                return str_replace($routeFromHost, '', parent::buildFinalUrl($modifier, $routeUrl, $parameters));
            }
        }

        return parent::buildFinalUrl($modifier, $routeUrl, $parameters);
    }
}