<?php

namespace TickTackk\RouteOnSubdomain\XF\Mvc;

use XF\Http\Request;
use XF\Mvc\RouteBuiltLink;
use XF\Mvc\RouteMatch;
use XF\Pub\App as PubApp;
use XF\App as BaseApp;
use XF\Util\File;

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

        $app = $this->app();
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
     * Finds the final route of a route.
     *
     * @param string $mainRoute The route for which we need to find the final route
     *
     * @return string The final route. Will return same as $mainRoute if no route filter available
     */
    protected function findFinalRoute(string $mainRoute)
    {
        $app = $this->app();
        $allRouteFilters = $app->container('routeFilters')['out'] ?? [];
        $cleanedMainRoute = rtrim($mainRoute, '.');

        foreach ($allRouteFilters AS $primaryRoute => $routeFilters)
        {
            foreach ($routeFilters AS $routeFilter)
            {
                if ($routeFilter['find_route'] === $cleanedMainRoute . '/')
                {
                    return $this->findFinalRoute(rtrim($routeFilter['replace_route'], '/'));
                }
            }
        }

        return $cleanedMainRoute;
    }

    /**
     * Finds the main route of a route.
     *
     * @param string $route The route for which we need to find the real route
     *
     * @return string The real route. Will return same as $route if no route filter available
     */
    protected function findMainRoute(string $route)
    {
        $app = $this->app();
        $allRouteFilters = $app->container('routeFilters')['out'] ?? [];
        $cleanedRoute = rtrim($route, '.');

        foreach ($allRouteFilters AS $routeFilters)
        {
            foreach ($routeFilters AS $routeFilter)
            {
                if ($routeFilter['replace_route'] === $cleanedRoute . '/')
                {
                    $newRoute = $routeFilter['find_route'];
                    return $this->findMainRoute(rtrim($newRoute, '/'));
                }
            }
        }

        return $cleanedRoute;
    }

    /**
     * @param string $route
     * @param string $path
     * @param Request|null $request
     *
     * @return string
     */
    protected function tckRouteOnSubdomainGenerateRedirectLink(string $route, string $path, Request $request = null) : string
    {
        $app = $this->app();
        $request = $request ?: $app->request();
        $useFriendlyUrls = $app->options()->useFriendlyUrls;
        $protocol = $request->getProtocol();
        $redirectUrl = "{$protocol}://{$route}.{$this->primaryHost}";

        $input = $request->getInput();
        \array_shift($input);
        $inputStr = \http_build_query($input);

        $redirectUrl .= $useFriendlyUrls ? '/' : '/index.php';
        if ($path)
        {
            $redirectUrl .= ($useFriendlyUrls ? '' : '?') . $path;
        }

        if ($inputStr)
        {
            $redirectUrl .= ($useFriendlyUrls ? '/?' : '&') . $inputStr;
        }

        return $redirectUrl;
    }

    /**
     * Parses subdomain into a valid path and redirects to final route when required
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
            $emptyRouteMatch = $this->getNewRouteMatch();

            $app = $this->app();
            $routesOnSubdomain = $app->container('router.public.routesOnSubdomain');

            $paths = explode('/', $path);
            $hostParts = explode($this->primaryHost, $request->getHost());
            $routeFromSubdomain = rtrim($hostParts[0] ?? '', '.');

            if ($routeFromSubdomain)
            {
                $mainRoute = $this->findMainRoute($routeFromSubdomain);
                $finalRoute = $this->findFinalRoute($mainRoute);

                $isContentSpecificRouteFilter = strpos($mainRoute, '/') !== false;
                if ($isContentSpecificRouteFilter)
                {
                    $mainRoute = explode('/', $mainRoute)[0] ?? $mainRoute;
                }

                if ($routeFromSubdomain !== $finalRoute)
                {
                    if ($paths[0] === '')
                    {
                        unset($paths[0]);
                    }
                    $path = \implode('/', $paths);

                    $app->response()->redirect(
                        $this->tckRouteOnSubdomainGenerateRedirectLink($finalRoute, $path),
                        301
                    );

                    return $emptyRouteMatch;
                }

                if ($routesOnSubdomain[$mainRoute] ?? false)
                {
                    if ($isContentSpecificRouteFilter)
                    {
                        array_unshift($paths, $routeFromSubdomain);
                        $paths = array_values($paths);
                    }

                    if ($paths[0] === '')
                    {
                        unset($paths[0]);
                    }

                    if (!$isContentSpecificRouteFilter)
                    {
                        array_unshift($paths, $mainRoute); // assume the route exists because it exists in the cache
                    }
                    $path = implode('/', $paths);
                }
                else
                {
                    // make this subdomain useless
                    return $emptyRouteMatch;
                }
            }
            else
            {
                $possibleRoute = $paths[0];
                if ($paths[0] === '') // default index route
                {
                    $possibleRoute = $this->indexRoute;
                }

                $path = implode('/', $paths);
                if ($routesOnSubdomain[$possibleRoute] ?? false) // accessing normal url but needs to be redirected to the new url
                {
                    unset($paths[0]);
                    $path = implode($paths);

                    $app->response()->redirect(
                        $this->tckRouteOnSubdomainGenerateRedirectLink($possibleRoute, $path),
                        301
                    );

                    return $emptyRouteMatch;
                }
            }
        }

        return parent::routeToController($path, $request);
    }

    /**
     * Extended to maintain <route>.<host> when required. Also supports takes care of route filters.
     *
     * @param string $modifier
     * @param string $routeUrl
     * @param array  $parameters
     *
     * @return string
     */
    public function buildFinalUrl($modifier, $routeUrl, array $parameters = [])
    {
        $app = $this->app();
        $request = $app->request();
        $useFriendlyUrls = $app->options()->useFriendlyUrls;

        $protocol = $request->getProtocol();
        $originalModifier = $modifier;
        if ($this->subDomainSupportEnabled && $app instanceof PubApp && $modifier === 'canonical')
        {
            $modifier = null;
        }
        $finalUrl = parent::buildFinalUrl($modifier, $routeUrl, $parameters);

        if ($this->subDomainSupportEnabled && $app instanceof PubApp)
        {
            $modifier = $originalModifier; // restore

            if ($routeUrl instanceof RouteBuiltLink)
            {
                $finalUrl = utf8_substr($routeUrl->getLink(), strlen("{$protocol}://{$this->primaryHost}"));
                if ($useFriendlyUrls)
                {
                    $finalUrl = ltrim($finalUrl, '.');
                }
            }

            if ($originalModifier === 'full')
            {
                $finalUrl = parent::buildFinalUrl(null, $routeUrl, $parameters); // if the modifier is full then we need to parse it into non-full modifier based url
            }

            if ($useFriendlyUrls)
            {
                $finalUrlParts = explode('/', $finalUrl, 2);
            }
            else
            {
                $finalUrlParts = explode('?', $finalUrl);
            }

            $path = $finalUrlParts[1] ?? '';
            $pathParts = explode('/', $path);
            $routeFromPath = $pathParts[0] ?? null;

            if ($routeFromPath === '')
            {
                $routeFromPath = rtrim($this->indexRoute, '/'); // default value is 'index' but when router when setting up app; the value is set to 'forums/' :thonk:
            }

            if ($routeFromPath)
            {
                $finalRoute = $this->findFinalRoute($routeFromPath);
                $mainRoute = $this->findMainRoute($finalRoute);
                $subdomain = '';

                if (\in_array($originalModifier, [null, 'full', 'canonical']))
                {
                    $routesOnSubdomain = $app->container('router.public.routesOnSubdomain');
                    $isContentSpecificRouteFilter = strpos($mainRoute, '/') !== false;
                    if ($isContentSpecificRouteFilter)
                    {
                        $mainRoute = explode('/', $mainRoute)[0] ?? $mainRoute; // forums/forum-short-name => forums
                    }

                    if (\array_key_exists($mainRoute, $routesOnSubdomain) && $routesOnSubdomain[$mainRoute] === true)
                    {
                        $subdomain = $finalRoute . '.';
                        unset($pathParts[0]); // remove route from the path
                    }
                }

                $joinerChar = $useFriendlyUrls ? '/' : '?';
                $finalUrlPartsStr = rtrim(implode($joinerChar, [$finalUrlParts[0], implode('/', $pathParts)]), '?');
                if ($originalModifier === 'nopath')
                {
                    $finalUrlPartsStr = '/' . $finalUrlPartsStr; // because we need a separator if no path or the url will be messed up
                }

                $finalUrl = "{$protocol}://{$subdomain}{$this->primaryHost}{$finalUrlPartsStr}";
            }
        }

        return $finalUrl;
    }

    /**
     * @return BaseApp
     */
    protected function app()
    {
        return \XF::app();
    }
}