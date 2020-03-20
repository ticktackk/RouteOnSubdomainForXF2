var TickTackk = window.TickTackk || {};
TickTackk.RouteOnSubdomain = TickTackk.RouteOnSubdomain || {};

(function ($, window, document, _undefined)
{
    "use strict";

    $(document).on('ajax:send', function (e, xhr, settings)
    {
        if ('url' in settings)
        {
            var updateSettingsWith = {};
            try
            {
                var fakeFullBaseLink = $('<a />', {
                        href: XF.config.url.fullBase
                    }),
                    fullBaseHost = fakeFullBaseLink[0].hostname,
                    fullBaseHostLength = fullBaseHost.length,

                    fakeAjaxLink = $('<a />', {
                        href: settings.url
                    }),
                    ajaxUrlHost = fakeAjaxLink[0].hostname,
                    ajaxUrlHostLength = ajaxUrlHost.length;

                if (ajaxUrlHost === fullBaseHost || (
                    ajaxUrlHostLength > fullBaseHostLength &&
                    ajaxUrlHost.substr(ajaxUrlHostLength - fullBaseHostLength) === fullBaseHost)
                )
                {
                    updateSettingsWith = {
                        xhrFields: {
                            withCredentials: true
                        }
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