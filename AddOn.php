<?php

namespace TickTackk\RouteOnSubdomain;

use TickTackk\RouteOnSubdomain\Exception\AccessControlAllowOriginHeaderAlreadySetException;
use TickTackk\RouteOnSubdomain\XF\Repository\Route as ExtendedRouteRepo;
use XF\App;
use XF\App as BaseApp;
use XF\Container;
use XF\Pub\App as PubApp;
use XF\Db\Exception as DbException;
use XF\Mvc\Dispatcher as MvcDispatcher;
use XF\Mvc\RouteMatch as MvcRouteMatch;

/**
 * Class AddOn
 *
 * @package TickTackk\RouteOnSubdomain
 */
class AddOn
{
    /**
     * @param BaseApp $app
     *
     * @throws DbException
     */
    protected static function setupContainer(App $app) : void
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

        $container['router.public.primaryHost'] = function (Container $c) use($app)
        {
            $boardUrl = $app->options()->boardUrl;
            return \parse_url($boardUrl, PHP_URL_HOST);
        };

        $request = $app->request();
        $container['router.public.allowRoutesOnSubdomain'] = function (Container $c) use($app, $request)
        {
            $primaryHost = $c['router.public.primaryHost'];
            if (!$primaryHost)
            {
                return false;
            }

            if (!$app->validator('Url')->isValid($request->getProtocol() . '://' . $primaryHost))
            {
                return false;
            }

            return \in_array(true, \array_values($c['router.public.routesOnSubdomain']), true);
        };
    }

    /**
     * @param BaseApp $app
     *
     * @return bool
     */
    protected static function isValidRefererOrOrigin(App $app) : bool
    {
        $request = $app->request();
        $referer = $request->getReferrer() ?: $request->getServer('HTTP_ORIGIN');
        if (!$referer)
        {
            return false;
        }

        $container = $app->container();
        if (!$container['router.public.allowRoutesOnSubdomain'])
        {
            return false;
        }

        $refererHost = \parse_url($referer, \PHP_URL_HOST);
        $primaryHost = $container['router.public.primaryHost'];
        $refererHostLen = utf8_strlen($refererHost);
        $primaryHostLen = strlen($primaryHost) + 1; // + 1 take count for hostname before '.'

        if ($refererHost === $primaryHost)
        {
            return true;
        }

        if ($refererHostLen > $primaryHostLen)
        {
            $routesOnSubdomain = $app->container('router.public.routesOnSubdomain');
            $routeFromSubdomain = substr($refererHost, 0, ($refererHostLen - $primaryHostLen));

            if (!\array_key_exists($routeFromSubdomain, $routesOnSubdomain))
            {
                return false;
            }

            return $routesOnSubdomain[$routeFromSubdomain] === true;
        }

        return false;
    }

    /**
     * Add cached routes which are available in subdomains, primary host and if allow routes on subdomain to container
     * on setup for public xf app instance.
     *
     * Do note that we are care only about public routes.
     *
     * @param PubApp $app
     *
     * @throws DbException
     */
    public static function appPubSetup(PubApp $app) : void
    {
        static::setupContainer($app);

        if (!static::isValidRefererOrOrigin($app))
        {
            return;
        }

        $accessControlAllowOrigin = $app->response()->header('Access-Control-Allow-Origin');
        if ($accessControlAllowOrigin && $accessControlAllowOrigin !== '*')
        {
            \XF::logException(new AccessControlAllowOriginHeaderAlreadySetException($accessControlAllowOrigin));
            return;
        }

        if ($accessControlAllowOrigin === '*') // allow all
        {
            return;
        }

        $request = $app->request();
        $isCoreRequest = $request->getServer('SCRIPT_NAME') === '/index.php';
        $headers = [
            'Access-Control-Allow-Origin' => $request->getServer('HTTP_ORIGIN'),
            'Access-Control-Allow-Credentials' => 'true'
        ];

        if ($request->getRequestMethod() === 'options')
        {
            $requestHeaders = [
                ['Access-Control-Request-Method' => 'Access-Control-Allow-Request-Method'],
                ['Access-Control-Request-Headers' => 'Access-Control-Allow-Request-Headers']
            ];

            foreach ($requestHeaders AS $headerData )
            {
                $inRequestHeaders = \str_replace(
                    '-', '_',
                    \strtoupper(\array_key_first($headerData))
                );
                $inRequestHeaders = "HTTP_{$inRequestHeaders}";
                $inResponseHeader = \reset($headerData);

                $value = $request->getServer($inRequestHeaders, null);
                if ($value)
                {
                    $headers[$inResponseHeader] = $value;
                }
            }
        }

        foreach ($headers AS $name => $value)
        {
            if ($isCoreRequest)
            {
                $app->response()->header($name, $value);
            }
            else
            {
                \header("{$name}: {$value}");
            }
        }
    }

    /**
     * Called before the app pre dispatch method is called and before the dispatch loop, allows you to
     * modify the original RouteMatch object before it is modified.
     *
     * @param MvcDispatcher $dispatcher Dispatcher object.
     * @param MvcRouteMatch $routeMatch Route match object.
     */
    public static function dispatcherPreDispatch(MvcDispatcher $dispatcher, MvcRouteMatch $routeMatch) : void
    {
        $app = \XF::app();
        if (!$app instanceof PubApp)
        {
            return;
        }

        $request = $app->request();
        if ($request->getRequestMethod() !== 'options')
        {
            return;
        }

        if (!$request->getServer('HTTP_ACCESS_CONTROL_REQUEST_METHOD'))
        {
            return;
        }

        if (!static::isValidRefererOrOrigin($app))
        {
            return;
        }

        $routeMatch->setController('TickTackk\RouteOnSubdomain:CORSPreflight');
        $routeMatch->setAction('index');
    }
}