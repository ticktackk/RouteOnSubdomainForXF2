<?php

namespace TickTackk\RouteOnSubdomain;

use TickTackk\RouteOnSubdomain\XF\Repository\Route as ExtendedRouteRepo;
use XF\Container;
use XF\Pub\App as PubApp;
use XF\Db\Exception as DbException;

/**
 * Class AddOn
 *
 * @package TickTackk\RouteOnSubdomain
 */
class AddOn
{
    /**
     * Add cached routes which are available in subdomains to container on setup for public xf app instance.
     * Do note that we are putting only the public routes on subdomains.
     *
     * @param PubApp $app
     *
     * @throws DbException
     */
    public static function appPubSetup(PubApp $app) : void
    {
        $container = $app->container();

        $container['router.public.routesOnSubdomain'] = $app->fromRegistry('publicRoutesOnSubdomain',
            function (Container $c)
            {
                /** @var ExtendedRouteRepo $routeRepo */
                $routeRepo  = $c['em']->getRepository('XF:Route');
                return $routeRepo->rebuildRouteOnSubdomainCache();
            }
        );
    }
}