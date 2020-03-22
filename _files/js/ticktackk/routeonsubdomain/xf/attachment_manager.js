var TickTackk = window.TickTackk || {};
TickTackk.RouteOnSubdomain = TickTackk.RouteOnSubdomain || {};

(function($, window, document, _undefined)
{
    "use strict";

    XF.AttachmentManager = XF.extend(XF.AttachmentManager, {
        __backup: {
            'getFlowOptions': 'tckRouteOnSubdomain_getFlowOptions'
        },

        getFlowOptions: function()
        {
            var flowOptions = this.tckRouteOnSubdomain_getFlowOptions();

            if (typeof flowOptions === 'object')
            {
                flowOptions.withCredentials = true;
            }

            return flowOptions;
        }
    });

                        # replace with the empty string
}) (jQuery, window, document);