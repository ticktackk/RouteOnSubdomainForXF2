var TickTackk = window.TickTackk || {};
TickTackk.RouteOnSubdomain = TickTackk.RouteOnSubdomain || {};

(function ($, window, document, _undefined)
{
    "use strict";

    $(document).on('ajax:send', function (e, xhr, settings)
    {
        if ('url' in settings)
        {
            var ajaxUrl = settings.url,
                updateSettingsWith = {};

            try
            {
                var routesOnSubdomain = XF.config.routesOnSubdomain,
                    fullBase = XF.config.url.fullBase + 'index.php?',
                    withCredentials = null,

                    fakeFullBaseLink = $('<a />', {
                        href: XF.config.url.fullBase
                    }),
                    fullBaseHost = fakeFullBaseLink[0].hostname,
                    fullBaseHostLength = fullBaseHost.length,

                    fakeAjaxLink = $('<a />', {
                        href: ajaxUrl
                    }),
                    ajaxUrlHost = fakeAjaxLink[0].hostname,
                    ajaxUrlHostLength = ajaxUrlHost.length;

                if (ajaxUrl.startsWith(fullBase) && typeof routesOnSubdomain === 'object' && routesOnSubdomain.length !== 0)
                {
                    $.each(routesOnSubdomain, function (route, finalFullBase)
                    {
                        if (ajaxUrl.startsWith(fullBase + route + '/'))
                        {
                            ajaxUrl = ajaxUrl.substring(fullBase.length + (route + '/').length);
                            ajaxUrl = finalFullBase + 'index.php?' + ajaxUrl;
                            updateSettingsWith.url = ajaxUrl;
                            withCredentials = true;
                            
                            return false;
                        }
                    });
                }
                else if (ajaxUrlHost === fullBaseHost || (
                    ajaxUrlHostLength > fullBaseHostLength &&
                    ajaxUrlHost.substr(ajaxUrlHostLength - fullBaseHostLength) === fullBaseHost)
                )
                {
                    withCredentials = true;
                }

                if (withCredentials !== null)
                {
                    updateSettingsWith.xhrFields = {
                        withCredentials: withCredentials
                    };
                }
            }
            catch (e)
            {
                // just why
            }
            finally
            {
                $.extend(settings, updateSettingsWith);
            }
        }
    });
})(jQuery, window, document);