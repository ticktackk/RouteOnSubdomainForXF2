<?php

namespace TickTackk\RouteOnSubdomain\XF\Repository;

use XF\Entity\Route as RouteEntity;
use TickTackk\RouteOnSubdomain\Entity\RouteOnSubdomain as RouteOnSubdomainEntity;
use XF\Mvc\Entity\ArrayCollection;

/**
 * Class Route
 *
 * @package TickTackk\RouteOnSubdomain\XF\Repository
 */
class Route extends XFCP_Route
{
    /**
     * @return array
     * @throws \XF\PrintableException
     */
    public function getRouteOnSubdomainCacheData() : array
    {
        $routesOnSubdomain = $this->getRoutesForSubdomain();

        $output = [];
        foreach ($routesOnSubdomain AS $routeOnSubdomain)
        {
            $output[$routeOnSubdomain->route_prefix] = $routeOnSubdomain->is_on_subdomain;
        }

        return $output;
    }

    /**
     * @return ArrayCollection|RouteOnSubdomainEntity[]
     * @throws \XF\PrintableException
     */
    public function getRoutesForSubdomain() : ArrayCollection
    {
        $db = $this->db();
        $db->beginTransaction();;

        /** @var ArrayCollection|RouteEntity[] $routes */
        $routes = $this->findRoutesForList()
            ->where('route_type', 'public')
            ->where('sub_name', '')
            ->with('AddOn')
            ->order('route_prefix')
            ->keyedBy('route_prefix')
            ->fetch();

        $routesOnSubdomain = $this->finder('TickTackk\RouteOnSubdomain:RouteOnSubdomain')->fetch();

        foreach ($routes AS $route)
        {
            if (!isset($routesOnSubdomain[$route->route_prefix]))
            {
                /** @var RouteOnSubdomainEntity $routeOnSubdomain */
                $routeOnSubdomain = $this->em->create('TickTackk\RouteOnSubdomain:RouteOnSubdomain');
                $routeOnSubdomain->route_prefix = $route->route_prefix;
                $routeOnSubdomain->save(true, false);

                $routesOnSubdomain[$routeOnSubdomain->route_prefix] = $routeOnSubdomain;
            }
        }

        /** @var RouteOnSubdomainEntity $routeOnSubdomain */
        foreach ($routesOnSubdomain AS $routeOnSubdomain)
        {
            if (!isset($routes[$routeOnSubdomain->route_prefix]))
            {
                $routeOnSubdomain->delete(true, false);
            }
        }

        $db->commit();

        return $routesOnSubdomain;
    }

    /**
     * @param array|null $cache
     *
     * @return array
     * @throws \XF\PrintableException
     */
    public function rebuildRouteOnSubdomainCache(array $cache = null) : array
    {
        $cache = $cache ?: $this->getRouteOnSubdomainCacheData();

        $this->app()->registry()->set('publicRoutesOnSubdomain', $cache);

        return $cache;
    }
}