/* ===================================================
 * zlux
 * https://zoolanders.com
 * ===================================================
 * Copyright (C) JOOlanders SL
 * http://www.gnu.org/licenses/gpl-2.0.html
 * ========================================================== */
;(function ($, ZX, window, document, undefined) {
    "use strict";

    var instance_id = 0, active = false, cache = {}, dropdown,
    
    itemsManagerSettings = {

        defaults: {
            apps: '', // Array or comma separated values
            types: '', // idem
            categories: '', // idem
            tags: '', // idem
            authors: '' // idem
        },

        init: function() {
            var $this = this;

            // init main manager
            ZX.extensions.manager.init.apply(this);
            
            // set instance id
            this.id = instance_id++;

            // set the filter param
            $this.filter = {};

            // override the ajax function
            this.DTsettings.ajax = function (data, callback, settings) {
                $this.ajax(data, callback, settings);
            };

            // set language vars
            $.extend($this.DTsettings.language, {
                emptyTable: ZX.lang._('IM_NO_ITEMS_FOUND'),
                info: ZX.lang._('IM_PAGINATION_INFO')
            });
        },

        DTsettings: {
            pageLength: 5,
            columns:
            [
                {
                    title: '', data: '_itemname', class: 'zx-manager-resource-name uk-width-1-1',
                    render: function (data, type) {
                        return type === 'display' ? '' : data;
                    }
                }
            ],
            initComplete: function(settings) {
                // var input_filter = $('.zlux-x-filter-input_wrapper', wrapper)
                
                // .append(
                //     // set search icon
                //     $('<i class="icon-search" />'),
                //     // and the cancel button
                //     $('<i class="icon-remove zlux-ui-dropdown-unselect" />').hide().on('click', function(){
                //         $('input', input_filter).val('');
                //         $(this).hide();
                //         // reset the filter
                //         $this.oTable.fnFilter('');
                //     })
                // );

                // // set search events
                // $('input', input_filter).on('keyup', function(){
                //     if ($(this).val() === '') {
                //         $('.zlux-ui-dropdown-unselect', input_filter).hide();
                //     } else {
                //         $('.zlux-ui-dropdown-unselect', input_filter).show();
                //     }
                // });

                // // fix the header column order
                // $('thead tr th:last', settings.nTable).prependTo($('thead tr', settings.nTable));

                // // trigger table init event
                // $this.trigger("InitComplete");
            },
            rowCallback: function(row, data) {
                var rsc_data = data;
                    rsc_data.details = [];

                // set resource details
                rsc_data.details.push( data.application.name + ' / ' + data.type.name + ' / ' + data.id );
                rsc_data.details.push( data.created );

                // add Author if known
                if (data.author.name) rsc_data.details.push({name: $this._('AUTHOR'), value: data.author.name});
            
                var resource = ZX.managerResource(row, rsc_data);
                resource.pushData(rsc_data);

                // set resource dom properties
                resource.element.addClass('zx-manager-resource');

                // fix the column order
                // $('td:last', resource.element).prependTo(resource.element);

                // reset and append the resource data
                $('.zx-manager-resource-name', resource.element).html('').append(
                    // render the resource content
                    resource.render()
                );
            },
            preDrawCallback: function(settings) {
                // show processing
                // $this.zluxdialog.spinner('show');

                // trigger event
                // $this.trigger("DTPreDrawCallback", oSettings);
            },
            drawCallback: function(settings) {
                // pagination hide/show
                // var oPaging = oSettings.oInstance.fnPagingInfo(),
                //     pagination = $('.dataTables_paginate', $(oSettings.nTableWrapper)).closest('.row-fluid');
                
                // // hide/show the pagination
                // if (oPaging.iTotalPages <= 1) pagination.hide(); else pagination.show();

                // // update dialog scrollbar
                // $this.zluxdialog.scrollbar('refresh');

                // // hide processing
                // $this.zluxdialog.spinner('hide');

                // // trigger event
                // $this.trigger("TableDrawCallback", oSettings);
            }
        },

        ajax: function (data, callback, settings) {
            var $this = this,

            // determine what filter values to use
            apps = $this.filter.apps ? $this.filter.apps : $this.options.apps,
            types = $this.filter.types ? $this.filter.types : $this.options.types,
            cats = $this.filter.cats ? $this.filter.cats : $this.options.categories,
            tags = $this.filter.tags ? $this.filter.tags : $this.options.tags,
            authors = $this.filter.authors ? $this.filter.authors : $this.options.authors;

            // push the preset filter values
            data.apps = $this.options.apps;
            data.types = $this.options.types;
            data.categories = $this.options.categories;
            data.tags = $this.options.tags;
            data.authors = $this.options.authors;

            // push the new filter values
            data.filter_apps = apps;
            data.filter_types = types;
            data.filter_cats = cats;
            data.filter_tags = tags;

            // specify zlux version
            data.zlux2 = true;

            // save draw value
            var draw = data.draw;

            // hash the request data
            data.draw = 0;
            var hash = String($.param(data)).hashCode();

            // if request cached, use instead and abort ajax
            if (false && cache[hash]) {
                cache[hash].draw = draw;

                callback( cache[hash] );
                return;
            }

            // recover draw value
            data.draw = draw;

            // request
            ZX.ajax.requestAndNotify({
                url: ZX.url.get('ajax:', {controller: 'zlux', task: 'getItemsManagerData'}),
                data: data,
                queue: 'itemsmanager'
            })

            .done(function (json) {

                // cache the retrieved data
                cache[hash] = json;

                // redraw
                callback(json);
            });
        },

        /**
         * Reload the data from source and redraw
         */
        reload: function() {
            var $this = this;

            // reload
            $this.resources.DataTable().ajax.reload();
        },

        preResourceDelete: function(resource, request) {
            var $this = this;

            // adapt request
        }
    };


    ZX.component('itempicker', $.extend(true, {}, itemsManagerSettings, ZX.extensions.managerDropdown, {

        init: function() {
            var $this = this;

            // init functions
            itemsManagerSettings.init.apply(this);
            ZX.extensions.managerDropdown.init.apply(this);

            this.on("click", function(){
               if(active!==$this) $this.pick(this.value);
            });

            // init dropdown
            dropdown = this.initDropdown(dropdown).addClass('zx-itempicker');
        },

        DTsettings: {
            ordering: false,
            columns:
            [
                {
                    title: '', data: '_itemname', class: 'zx-x-main-column uk-width-1-1',
                    render: function (data, type) {
                        return type === 'display' ? '' : data;
                    }
                }
            ],
            rowCallback: function(row, data) {
                var rsc_data = data;
                    rsc_data.details = [];

                // set resource details
                rsc_data.details.push( data.application.name + ' / ' + data.type.name + ' / ' + data.id );
            
                var resource = ZX.managerResource(row, rsc_data);
                resource.pushData(rsc_data);

                // set resource dom properties
                resource.element.addClass('zx-manager-resource');

                // append the resource data
                $('.zx-x-main-column', resource.element).append(
                    resource.render()
                );
            }
        },

        pick: function(inititem) {
           ZX.extensions.managerDropdown.pick.apply(this, [inititem]);

           active = this;
        },
    }));

    // init code
    $(document).on("focus.itempicker.zlux", "[data-zx-itempicker]", function(e) {
        var ele = $(this);

        if (!ele.data("itempicker")) {
            e.preventDefault();
            var obj = ZX.itempicker(ele, $.UIkit.Utils.options(ele.attr("data-zx-itempicker")));
            ele.trigger("focus");
        }
    });

    $(document).on("click.itempicker.zlux", function(e) {
        var target = $(e.target);

        if (active && target[0] != dropdown[0] && !target.data("itempicker") && !target.parents(".zx-itempicker:first").length) {
            dropdown.hide();
            active = false;
        }
    });

    // init code
    $.UIkit.ready(function(context) {
        $("[data-zx-itempicker]", context).each(function() {
            var ele = $(this);

            if (!ele.data("itempicker")) {
                var obj = ZX.itempicker(ele, $.UIkit.Utils.options(ele.attr("data-zx-itempicker")));
            }
        });
    });

})(jQuery, jQuery.zx, window, document);