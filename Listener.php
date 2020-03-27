<?php

namespace TickTackk\RouteOnSubdomain;

use TickTackk\RouteOnSubdomain\Exception\AccessControlAllowOriginHeaderAlreadySetException;
use TickTackk\RouteOnSubdomain\XF\Repository\Route as ExtendedRouteRepo;
use XF\App;
use XF\App as BaseApp;
use XF\Container;
use XF\Db\Exception as DbException;
use XF\Http\Request;
use XF\Http\Response;
use XF\Mvc\Dispatcher as MvcDispatcher;
use XF\Mvc\RouteMatch as MvcRouteMatch;
use XF\Pub\App as PubApp;

/**
 * Class Listener
 *
 * @package TickTackk\RouteOnSubdomain
 */
class Listener
{
    /**
     * @var null|string
     */
    protected static $origin = null;

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

        $container['router.public.primaryHostWithSchema'] = function (Container $c) use($app)
        {
            $boardUrl = $app->options()->boardUrl;
            return \parse_url($boardUrl, \PHP_URL_SCHEME) . '://' . $c['router.public.primaryHost'];
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
     * @param string $referer
     */
    protected static function setOriginFromReferer(string $referer) : void
    {
        $scheme = \parse_url($referer, \PHP_URL_SCHEME);
        $host = \parse_url($referer, \PHP_URL_HOST);

        static::$origin = "{$scheme}://{$host}";
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
            static::setOriginFromReferer($referer);
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

            if ($routesOnSubdomain[$routeFromSubdomain] === true)
            {
                static::setOriginFromReferer($referer);
                return true;
            }
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
        $headers = [
            'Access-Control-Allow-Origin' => static::$origin,
            'Access-Control-Allow-Credentials' => 'true'
        ];

        $requestHeaders = [
            ['HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'Access-Control-Allow-Request-Method'],
            ['HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Access-Control-Allow-Request-Headers']
        ];

        foreach ($requestHeaders AS $headerData)
        {
            $inServer = \key($headerData);
            $inResponse = \reset($headerData);
            $fallback = '*';

            if (\is_array($inResponse))
            {
                $fallback = $inResponse[1];
                $inResponse = $inResponse[0];
            }

            $value = $request->getServer($inServer, null);
            $headers[$inResponse] = $value ?: $fallback;
        }

        static::setHeaders($headers, $app, null, $request);
    }

    /**
     * @param array $headers
     * @param BaseApp|null $app
     * @param Response|null $response
     * @param Request|null $request
     */
    protected static function setHeaders(array $headers, App $app = null, Response $response = null, Request $request = null) : void
    {
        if ($response === null || $request === null)
        {
            $app = $app ?: \XF::app();
            $response = $response ?: $app->response();
            $request = $request ?: $app->request();
        }

        foreach ($headers AS $name => $value)
        {
            static::setHeader($name, $value, $app, $response, $request);
        }
    }

    /**
     * @param string $name
     * @param string|array $value
     * @param BaseApp|null $app
     * @param Response|null $response
     * @param Request|null $request
     */
    protected static function setHeader(string $name, $value, App $app = null, Response $response = null, Request $request = null) : void
    {
        if ($response === null || $request === null)
        {
            $app = $app ?: \XF::app();
            $response = $response ?: $app->response();
            $request = $request ?: $app->request();
        }

        if (\is_array($value))
        {
            $value = \array_unique($value);
            foreach ($value AS $realValue)
            {
                static::setHeader($name, $realValue, $app, $response, $request);
            }
            return;
        }

        if ($request->getServer('SCRIPT_NAME') === '/index.php')
        {
            $response->header($name, $value);
        }
        else
        {
            \header("{$name}: {$value}");
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