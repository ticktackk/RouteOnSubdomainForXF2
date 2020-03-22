<?php

namespace TickTackk\RouteOnSubdomain\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View as ViewReply;
use XF\Mvc\Reply\Exception as ExceptionReply;
use XF\Pub\Controller\AbstractController;

/**
 * Class CORSPreflight
 *
 * @package TickTackk\RouteOnSubdomain\Pub\Controller
 */
class CORSPreflight extends AbstractController
{
    /**
     * @param string $action
     * @param ParameterBag $params
     */
    public function checkCsrfIfNeeded($action, ParameterBag $params) : void
    {
    }

    /**
     * @return ViewReply
     *
     * @throws ExceptionReply
     */
    public function actionIndex() : ViewReply
    {
        if ($this->request()->getRequestMethod() !== 'options')
        {
            throw $this->exception($this->error(
                \XF::phrase('tckRouteOnSubdomain_action_available_via_post_only'),
                405
            ));
        }

        $view = $this->view('TickTackk\RouteOnSubdomain:CORSPreflight\Index');
        $view->setResponseType('json');

        return $view;
    }
}