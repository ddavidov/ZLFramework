/* ===================================================
 * zlux
 * https://zoolanders.com
 * ===================================================
 * Copyright (C) JOOlanders SL
 * http://www.gnu.org/licenses/gpl-2.0.html
 * ========================================================== */
;(function ($, ZX, window, document, undefined) {
    "use strict";

    var instance_id = 0,
        cache = {};
    
    ZX.component('filesManager', $.extend(true, {}, ZX.extensions['manager'], {

        defaults: {
            root: '', // relative path to the root folder
            extensions: '', // array or comma separated values of allowed extensions
            storage: 'local',
            storage_params: {},
            max_file_size: '',
            resize: {}
        },

        init: function() {
            var $this = this;

            // init main manager
            ZX.extensions['manager'].init.apply(this);
            
            // set instance id
            this.id = instance_id++;

            // clean settings
            this.options.max_file_size = parseSize(this.options.max_file_size);
            this.options.extensions = this.options.extensions.replace(/\|/g, ',');
            this.options.root = ZX.utils.cleanURI(this.options.root);

            // check storage param
            if (this.options.storage === '' || this.options.storage === undefined || this.options.storage === null) {
                // ZX._ErrorLog(0, ZX.lang._('STORAGE_PARAM_MISSING'));
                this.options.storage = 'local';
            }

            // set initial path
            this.currentPath = this.options.root;

            // EVENT resource selected
            this.on('resourceSelected', function (e, resource)
            {
                if (resource.element.attr('data-zx-status') !== 'true') {
                    resource.element.attr('data-zx-status', 'true');

                    // remove selected status from siblings
                    resource.element.siblings().removeAttr('data-zx-status');

                    // if folder
                    if (resource.element.data('type') === 'folder') {

                        // go to the resources path
                        $this.goToPath(resource.element.data('id'));
                    }

                    // trigger event
                    // $this.trigger('manager-resource-selected', $resource);
                }

                // prevent default
                return false;
            });

            // EVENT resource deleted
            this.on('resourceDeleted', function (e, resource)
            {
                // remove the object from cache
                var data = cache[$this.currentPath].data;
                $.each(data, function(i, value){
                    if (resource.data.name === value.name && resource.data.type === value.type) {
                        // found, remove
                        data.splice(i, 1);

                        // stop iteration
                        return false;
                    }
                });

                // redraw instances
                $this.redrawInstances();
            });

            // override the ajax function
            this.DTsettings.ajax = function (data, callback, settings) {
                $this.ajax(data, callback, settings);
            };

            // all set, init resources
            this.initResources();
        },

        DTsettings: {
            paging: false,
            columns: [
                { 
                    title: '', data: 'type', searchable: false, class: 'zx-manager-resource-icon uk-width-1-1',
                    render: function (data, type) {
                        if (type === 'display') {
                            return '<i class="uk-icon-' + (data === 'folder' ? 'folder' : 'file-o') + '"></i>';
                        } else {
                            return data;
                        }
                    }
                },
                { 
                    title: ZX.lang._('NAME'), data: 'name', class: 'zx-manager-resource-name',
                    render: function (data, type) {
                        return type === 'display' ? '' : data;
                    },
                    createdCell: function (cell, cellData, rowData) {
                        // store path in data
                        $(cell).parent('tr').attr('data-id', ZX.utils.cleanURI(rowData.name));
                    }
                }
            ],
            columnDefs: {
                visible: false, targets: [ 2 ]
            },
            sorting: [ [0,'desc'], [1,'asc'] ], // init sort
            serverSide: true,
            rowCallback: function(row, data) {
                var rsc_data = data;
                    rsc_data.details = [];

                // set resource details
                if (data.type === 'folder') {
                    rsc_data.details.push({name: ZX.lang._('NAME'), value: data.basename});

                } else { // file
                    rsc_data.details.push({name: ZX.lang._('NAME'), value: data.basename});
                    rsc_data.details.push({name: ZX.lang._('TYPE'), value: data.content_type});
                    rsc_data.details.push({name: ZX.lang._('SIZE'), value: data.size.display});
                }

                var $resource = ZX.managerResource(row, rsc_data);
                $resource.pushData(rsc_data);

                // set resource dom properties
                $resource.element.attr('data-type', data.type).addClass('zx-manager-resource');

                // reset and append the resource data
                $('.zx-manager-resource-name', $resource.element).html('').append(
                    // render the resource content
                   $resource.render()
                );

                // append the resource edit feature to the name
                $('.zx-x-name', $resource.element).append(
                    '<i class="zx-x-name-edit uk-icon-pencil-square" title="' + ZX.lang._('RENAME') + '" />'
                );
            },
            initComplete: function() {
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

                // trigger table init event 50ms after as a workaround if the first ajax call is canceled
                // setTimeout(function() {
                //     this.trigger("InitComplete");
                // }, 50);
            },
            preDrawCallback: function() {
                // show processing
                // $this.zluxdialog.spinner('show');
            
            },
            drawCallback: function(settings) {
                // pagination hide/show
                // var oPaging = $this.table.fnPagingInfo(),
                //     pagination = $('.dataTables_paginate', $(settings.tableWrapper)).closest('.row-fluid');
                
                // if (oPaging.totalPages <= 1) pagination.hide(); else pagination.show();

                // trigger event
                // this.trigger("DrawCallback");


                // // update dialog scrollbar
                // $this.zluxdialog.scrollbar('refresh');

                // // hide spinner
                // $this.zluxdialog.spinner('hide');
            }
        },

        goToPath: function (path) {
            this._goToPath = path;
            this.resources.DataTable().ajax.reload();
            // reset var after ajax request
            this._goToPath = '';
        },

        ajax: function (data, callback, settings) {
            var $this = this,

            // set the new root
            root = ZX.utils.cleanURI($this.currentPath + '/' + $this._goToPath);

            // if available use cache data instead

            if (cache[root]) {
                callback( cache[root] );
                return;
            }

            // prepare request data to be send
            data.extensions = $this.options.extensions;
            data.root = root;

            // if S3 storage
            if ($this.options.storage === 's3') {
                data.storage = s3;
                data.accesskey = $this.options.storage_params.accesskey;
                data.key = $this.options.storage_params.secretkey;
                data.bucket = $this.options.storage_params.bucket;
            }

            // request
            ZX.ajax.requestAndNotify({
                url: ZX.url.ajax('zlux', 'getFilesManagerData'),
                data: data,
                queue: 'filesmanager',
                // beforeSend: function(){
                //     // check if the data is cached
                //     var cached = false;

                //     setTimeout(function(){
                //         console.log(cache['']);
                //     }, 100);

                    

                //     // if not reloading
                //     if (!$this.reloading || $this.redrawing){

                //         // check if already cached
                //         var json = cache[root];
                       
                //         if (json) {
                //             console.log('it is cached');
                //             // if first time, save real root path, as it can be changed for security reasons by the server
                //             if (!$this.cacheInited) $this.startRoot = json.root;

                //             // save current path
                //             $this.currentPath = root;

                //             // set cache status
                //             $this.cacheInited = true;

                //             // emulate the xhr events
                //             // $($this.resources).trigger('xhr', [settings, json]);
                //             callback( json );

                //             // avoid ajax call
                //             cached = true;
                //         }

                //         // if redrawing
                //         if ($this.redrawing) {
                //             // reset the param
                //             $this.redrawing = false;

                //             // save root
                //             $this.currentPath = root;

                //             // avoid the ajax call
                //             return false;
                //         }
                //     }

                //     console.log(cached);

                //     // if cached abort ajax
                //     if (cached) return false;

                //     // else, the ajax proceeds, show the spinner
                //     // $this.zluxdialog.spinner('show');
                // }
            })

            .done(function (json) {

                // reset cache if reloading, in order to retrieve the content again
                // if ($this.reloading) cache = {};

                // cache the retrieved data
                cache[json.root] = json;

                // if first time, save real root path, as it can be changed for security reasons by the server
                if (!$this.cacheInited) $this.startRoot = json.root;

                // save new path
                $this.currentPath = json.root;

               
               // redraw the other instances
                // if ($this.cacheInited) $this.redrawInstances();

                // set reloading to false
                $this.reloading = false;

                // set cache status
                $this.cacheInited = true;

                // redraw
                callback(json);
            });
        },

        /**
         * Redraw all other instances
         */
        redrawInstances: function() {
            var $this = this;

            // redraw instances
            $('[data-zx-manager-files]').each(function(index, instance){
                var manager = $(instance).data('filesManager');
                
                // skip current instance
                if (manager.id === $this.id) return true;

                // if resources and cache inited
                if (manager.resources && manager.cacheInited) {
                    // redraw
                    // manager.redrawing = true;
                    manager.resources.DataTable().ajax.reload();
                }
            });
        },

        /**
         * Reload the data from source and redraw
         */
        reload: function() {
            var $this = this;

            // set vars
            $this.reloading = true;

            // reload
            $this.resources.DataTable().ajax.reload();
        },

        preResourceDelete: function(resource, request) {
            var $this = this;

            // adapt request
        },

        /**
         * Delete the object from the server // TO CHECK
         */
        deleteObject: function($object) {
            var $this = this,
                aoData = [],

            // save object path
            path = $this.getFullPath($object.name);

            // push the storage related data
            aoData = $this.pushStorageData(aoData);

            // if S3 storage
            if($this.options.storage === 's3') {

                // add a slash if folder
                if($object.type === 'folder') {
                    path = path + '/';
                }
            }

            // set path
            aoData.push({ "name": "path", "value": path });

            //
            $this.deleteObject().done(function(json) {

                // remove the object from cache
                var aaData = cache[$this.sCurrentPath].data;
            });

            aoData.push({ "name": "key", "value": $this.options.storage_params.secretkey });
        },

        /**
         * Perform the actions related to rename the Object
         */
        renameResource: function($object) {
            var $this = this,
                name = $('.zlux-x-name a', $object.dom),
                ext = name.html().match(/\.+[a-zA-Z]+$/g),
                raw_name = name.html().replace(/\.+[a-zA-Z]+$/g, '');

            // if is folder ignore the extension
            ext = ext !== null ? ext : '';

            // prepare and display the confirm message
            var msg = $('<div>' + ZX.lang._('INPUT_THE_NEW_NAME') + '<br /><input class="zlux-x-input" type="text" value="' + raw_name + '" /> ' + ext + '<br /><span class="label label-success label-link">' + ZX.lang._('CONFIRM').toLowerCase() + '</span></div>')

            // confirm action
            .on('click', '.label-link', function(){
                // only allowed to be submited once
                if ($(this).data('submited')) return; $(this).data('submited', true);

                // set spinner
                $('.column-icon i', $object.dom).addClass('uk-icon-spinner uk-icon-spin');

                // change the object name
                ZX.lang._changeObjectName($object, $('input', msg).val() + ext)
                
                // if succesfull
                .done(function(new_name)
                {
                    // update the dom name
                    name.html(new_name);
                    // in details also
                    $('.zlux-x-details-content ul li:first span', $object.dom).html(new_name);

                    // and path data
                    $object.dom.attr('data-id', new_name);

                    // update the object data, and as it's a reference to DataTables data it will be also updated :)
                    $object.name = new_name;
                    $object.basename = new_name.replace(/\.+[a-zA-Z]+$/g, '');
                    $object.path = $object.path.replace(/(\w|[-.])+$/, new_name);

                    // update the cache
                    $.zx.filesManager.aAjaxDataCache[$this.sCurrentPath].aaData = $this.oTable.fnGetData();

                    // redraw the other instances
                    $this.redrawInstances();

                    // remove selected status as the object has changed
                    $object.dom.removeAttr('data-zlux-object-status');

                    // remove msg
                    $('.zlux-x-msg', $object.dom).remove();
                })

                // if fails
                .fail(function(message) {
                    $this.pushMessageToObject($object, message);
                })

                // on result
                .always(function() {
                    // remove spinner
                    $('.column-icon i', $object.dom).removeClass('uk-icon-spinner uk-icon-spin');
                });
            })

            .on('keypress', 'input', function(e){
                var code = (e.keyCode ? e.keyCode : e.which);
                if (code === 13) {
                    // Enter key was pressed, emulate click event
                    $('.label-link', msg).trigger('click');
                }
            });

            $this.pushMessageToObject($object, msg);
        },

        /**
         * Requests the Object name change
         */
        _changeObjectName: function($object, name) {
            var $this = this,
                aoData = [],
                src = $this.getFullPath($object.name),
                dest = $this.getFullPath(name);

            // push the storage related data
            aoData = $this.pushStorageData(aoData);

            // if S3 storage
            if($this.options.storage === 's3') {

                // add a slash if folder
                if($object.type === 'folder') {
                    src = src + '/';
                    dest = dest + '/';
                }
            }

            // set paths
            aoData.push({ "name": "src", "value": src });
            aoData.push({ "name": "dest", "value": dest });

            // make the request and return a promise
            return $.Deferred(function( defer )
            {
                $.ajax({
                    "url": $.zx.url.ajax('zlux', 'moveObject'),
                    "data": aoData,
                    "dataType": "json",
                    "type": "post"
                })
                
                .done(function(json) {
                    if (json.result) {

                        defer.resolve(json.name);
                    } else {
                        // failed with reported error
                        defer.reject(json.errors);
                    }
                })

                .fail(function(){
                    // some unreported error
                    defer.reject(ZX.lang._('SOMETHING_WENT_WRONG'));
                });

            }).promise();
        },

        /**
         * Push the necesary storage DATA
         */
        pushStorageData: function(aoData) {
            var $this = this;

            // if S3 storage
            if($this.options.storage === 's3') {
                aoData.push({ "name": "storage", "value": "s3" });
                aoData.push({ "name": "accesskey", "value": $this.options.storage_params.accesskey });
                aoData.push({ "name": "key", "value": $this.options.storage_params.secretkey });
                aoData.push({ "name": "bucket", "value": $this.options.storage_params.bucket });
            }

            return aoData;
        },

        /**
         * Returns the full path prepended to the passed relative path
         */
        getFullPath: function(path) {
            var cp = this.sCurrentPath;
            return cp ? cp + '/' + path : path;
        }
    }));


    /* Helper functions
       ---------------------------------------------- */

    /**
        Parses the specified size string into a byte value. For example 10kb becomes 10240

        @param  String/Number size String to parse or number to just pass through
        @return Number Size in bytes
    */
    function parseSize(size){
        if (typeof(size) !== 'string' || size === '') return size;

        var muls = {
                t: 1099511627776,
                g: 1073741824,
                m: 1048576,
                k: 1024
            },
            mul;

        size = /^([0-9]+)([mgk]?)$/.exec(size.toLowerCase().replace(/[^0-9mkg]/g, ''));
        mul = size[2];
        size = +size[1];
        
        if (muls.hasOwnProperty(mul)) {
            size *= muls[mul];
        }
        return size;
    }


    // init code
    $(document).on("uk-domready", function(e) {

        $("[data-zx-manager-files]").each(function() {
            var manager = $(this);

            if (!manager.data("filesManager")) {
                var obj = ZX.filesManager(manager, $.UIkit.Utils.options(manager.attr("data-zx-manager-files")));
            }
        });
    });

})(jQuery, jQuery.zx, window, document);