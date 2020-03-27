var TickTackk = window.TickTackk || {};
TickTackk.RouteOnSubdomain = TickTackk.RouteOnSubdomain || {};

(function($, window, document, _undefined)
{
    "use strict";

    XF.Editor = XF.extend(XF.Editor, {
        __backup: {
            'getEditorConfig': 'tckRouteOnSubdomain_getEditorConfig'
        },

        getEditorConfig: function()
        {
            var editorConfig = this.tckRouteOnSubdomain_getEditorConfig();

            if (typeof editorConfig === 'object')
            {
                editorConfig.requestWithCredentials = true;
            }

            return editorConfig;
        }
    });
}) (jQuery, window, document);