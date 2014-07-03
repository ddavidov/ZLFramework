/* ===================================================
 * zlux
 * https://zoolanders.com
 * ===================================================
 * Copyright (C) JOOlanders SL
 * http://www.gnu.org/licenses/gpl-2.0.html
 * ========================================================== */
;(function ($, ZX, window, document, undefined) {
    "use strict";

    var notify = function(msg, options){
        // display message
        var notify = $.UIkit.notify(msg, options);

        // add zlux class for the holding content styling
        notify.element.parent().addClass('zx');

        return notify;
    },

    confirm = function(msg, options){
        
        options = $.extend(options, {
            timeout: false // confirmation must wait user interaction
        });

        return $.Deferred(function( defer )
        {
            var notify = ZX.notify(msg + '<div class="uk-text-center uk-margin-top">\
                    <a class="zx-x-confirm uk-margin-right"><i class="uk-icon-check uk-icon-small"></i></a>\
                    <a class="zx-x-cancel uk-margin-left"><i class="uk-icon-times uk-icon-small"></i></a>\
                </div>',
            options);

            notify.element.on('click', '.zx-x-confirm', function(e, b){
                defer.resolve();
            });

            notify.element.on('click', function(e, b){
                defer.reject();
            });

        }).promise();
    },

    closeAll = function(group, instantly){
        $.UIkit.notify.closeAll(group, instantly);
        return this;
    },

    loadAssets = function() {
        return $.zx.assets.load(ZX.url.get('zlux:js/uikit/addons/notify.min.js'));
    };

    ZX.notify             = notify;
    ZX.notify.confirm     = confirm;
    ZX.notify.closeAll    = closeAll;
    ZX.notify.loadAssets  = loadAssets;


    $.when(ZX.ready()).done(function(){
        ZX.notify.loadAssets();
    });

})(jQuery, jQuery.zx, window, document);