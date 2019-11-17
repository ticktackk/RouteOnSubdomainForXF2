<?php

namespace TickTackk\RouteOnSubdomain;

use TickTackk\RouteOnSubdomain\Exception\AccessControlAllowOriginHeaderAlreadySetException;
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
        $container = $app->container();
        $request = $app->request();

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
            return parse_url($boardUrl, PHP_URL_HOST);
        };

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

        $referer = $request->getReferrer();
        if ($referer && $container['router.public.allowRoutesOnSubdomain'])
        {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $primaryHost = $container['router.public.primaryHost'];

            $refererHostLen = utf8_strlen($refererHost);
            $primaryHostLen = strlen($primaryHost);

            if (
                $refererHost === $primaryHost || (
                    $refererHostLen > $primaryHostLen &&
                    utf8_substr($refererHost, ($refererHostLen - $primaryHostLen) - 1) === '.' . $primaryHost
                )
            )
            {
                $accessControlAllowOrigin = $app->response()->header('Access-Control-Allow-Origin');
                if ($accessControlAllowOrigin && $accessControlAllowOrigin !== '*')
                {
                    \XF::logException(new AccessControlAllowOriginHeaderAlreadySetException($accessControlAllowOrigin));
                    return;
                }

                if ($accessControlAllowOrigin !== '*') // allow all
                {
                    $newAccessControlAllowOrigin = $request->getProtocol() . '://' . $refererHost;
                    $isCoreRequest = $request->getServer('SCRIPT_NAME') === '/index.php';
                    $headers = [
                        'Access-Control-Allow-Origin' => $newAccessControlAllowOrigin,
                        'Access-Control-Allow-Credentials' => 'true',
                        'Vary' => 'Origin'
                    ];

                    foreach ($headers AS $name => $value)
                    {
                        if ($isCoreRequest)
                        {
                            $app->response()->header($name, $value);
                        }
                        else
                        {
                            header("{$name}: {$value}");
                        }
                    }
                }
            }
        }
    }
}