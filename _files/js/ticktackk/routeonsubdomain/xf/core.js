var TickTackk = window.TickTackk || {};
TickTackk.RouteOnSubdomain = TickTackk.RouteOnSubdomain || {};

(function ($, window, document, _undefined)
{
    "use strict";

    $(document).on('ajax:send', function (e, xhr, settings)
    {
        if ('url' in settings)
        {
            try
            {
                var ajaxUrl = settings.url,
                    originalAjaxUrl = ajaxUrl,
                    routesOnSubdomain = XF.config.routesOnSubdomain,
                    fullBase = XF.config.url.fullBase + 'index.php?',
                    relativeUrl = null;

                if (ajaxUrl.startsWith(fullBase)
                    && typeof routesOnSubdomain === 'object' && routesOnSubdomain.length !== 0
                )
                {
                    $.each(routesOnSubdomain, function (route, finalFullBase)
                    {
                        if (ajaxUrl.startsWith(fullBase + route + '/'))
                        {
                            ajaxUrl = ajaxUrl.substring(fullBase.length + (route + '/').length);
                            ajaxUrl = finalFullBase + 'index.php?' + ajaxUrl;

                            console.info('Switched AJAX url from '
                                + XF.htmlspecialchars(originalAjaxUrl)
                                + ' to '
                                + XF.htmlspecialchars(ajaxUrl)
                            );

                            settings.url = ajaxUrl;
                            if (typeof settings.xhrFields === 'undefined')
                            {
                                settings.xhrFields = {};
                            }

                            settings.xhrFields.withCredentials = true;
                        }
                    });
                }
            }
            catch (e)
            {
                // just why
            }
        }
    });
})(jQuery, window, document);