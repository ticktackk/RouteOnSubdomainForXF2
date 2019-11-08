<?php

namespace TickTackk\RouteOnSubdomain\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\ControllerPlugin\AbstractPlugin;
use XF\ControllerPlugin\Toggle as TogglePlugin;
use XF\Mvc\Entity\Repository;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Message as MessageReply;
use XF\Repository\Route as RouteRepo;
use TickTackk\RouteOnSubdomain\XF\Repository\Route as ExtendedRouteRepo;
use XF\Mvc\Reply\View as ViewReply;
use XF\Mvc\Reply\Exception as ExceptionReply;
use XF\Phrase;

/**
 * Class RouteOnSubdomain
 *
 * @package TickTackk\RouteOnSubdomain\Admin\Controller
 */
class RouteOnSubdomain extends AbstractController
{
    /**
     * Check if the current admin has permission to manage options
     *
     * @param string       $action
     * @param ParameterBag $params
     *
     * @throws ExceptionReply
     */
    protected function preDispatchController($action, ParameterBag $params) : void
    {
        $this->assertAdminPermission('option');
    }

    /**
     * Lists all routes (prefixes)
     *
     * @return ViewReply
     * @throws \XF\PrintableException
     */
    public function actionIndex() : ViewReply
    {
        $routeRepo = $this->getRouteRepo();
        $routes = $routeRepo->getRouteOnSubdomainCacheData();

        $viewParams = [
            'routes' => $routes
        ];
        return $this->view(
            'TickTackk\RouteOnSubdomain:RouteOnSubdomain\Listing',
            'tckRouteOnSubdomain_route_on_subdomain_list',
            $viewParams
        );
    }

    /**
     * Selectively enables or disables specified routes (prefix)
     *
     * @return MessageReply
     * @throws \XF\PrintableException
     */
    public function actionToggle() : MessageReply
    {
        $togglePlugin = $this->getTogglePlugin();

        $reply = $togglePlugin->actionToggle(
            'TickTackk\RouteOnSubdomain:RouteOnSubdomain',
            'is_on_subdomain'
        );

        $message = $reply->getMessage();
        if ($message instanceof Phrase && $message->getName() === 'your_changes_have_been_saved')
        {
            $routeRepo = $this->getRouteRepo();
            $routeRepo->rebuildRouteOnSubdomainCache();
        }

        return $reply;
    }

    /**
     * @return Repository|RouteRepo|ExtendedRouteRepo
     */
    protected function getRouteRepo() : RouteRepo
    {
        return $this->repository('XF:Route');
    }

    /**
     * @return AbstractPlugin|TogglePlugin
     */
    protected function getTogglePlugin() : TogglePlugin
    {
        return $this->plugin('XF:Toggle');
    }
}