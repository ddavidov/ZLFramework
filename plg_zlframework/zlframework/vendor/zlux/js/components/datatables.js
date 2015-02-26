/**
 * @package     zlux
 * @version     2.0.2
 * @author      ZOOlanders - http://zoolanders.com
 * @license     GNU General Public License v2 or later
 */

(function ($, ZX, UI) {
    "use strict";

    var datatables = function(){

        // set uikit pagination option
        $.fn.dataTableExt.pager.uikit = ZX.datatables.pagination;
        $.fn.dataTableExt.pager.uikit_simple = ZX.datatables.pagination_simple;
    },

    settings = {
        dom: 'tp',
        language: {
            emptyTable: ZX.lang._('EMPTY_FOLDER'),
            infoEmpty: ''
        },
        stripeClasses: [], // get rid of inbuild stripe classes
        pagingType: 'uikit',
        processing: false
    },

    pagination = {
        fnInit: function( oSettings, nPaging, fnCallbackDraw ) {
            var oLang = oSettings.oLanguage.oPaginate;
            var fnClickHandler = function ( e ) {
                e.preventDefault();

                if ( oSettings.oApi._fnPageChange(oSettings, e.data.action) ) {
                    fnCallbackDraw( oSettings );
                }
            };

            $(nPaging).append(
                '<ul class="zx-manager-pagination">'+
                    '<li class="zx-manager-pagination-first uk-disabled"><a href="#"></i></a></li>'+
                    '<li class="zx-manager-pagination-prev uk-disabled"><a href="#"></a></li>'+
                    '<li class="zx-manager-pagination-next uk-disabled"><a href="#"></a></li>'+
                    '<li class="zx-manager-pagination-last uk-disabled"><a href="#"></a></li>'+
                '</ul>'
            );
            var els = $('a', nPaging);
            $(els[0]).bind( 'click.DT', { action: 'first' }, fnClickHandler );
            $(els[1]).bind( 'click.DT', { action: 'previous' }, fnClickHandler );
            $(els[2]).bind( 'click.DT', { action: 'next' }, fnClickHandler );
            $(els[3]).bind( 'click.DT', { action: 'last' }, fnClickHandler );
        },

        fnUpdate: function ( oSettings, fnCallbackDraw ) {
            var iListLength = 4;
            var paging = oSettings.oInstance.DataTable().page.info();
            var an = oSettings.aanFeatures.p;
            var i, ien, j, sClass, iStart, iEnd, iHalf=Math.floor(iListLength/2);

            if ( paging.pages < iListLength) {
                iStart = 1;
                iEnd = paging.pages;
            }
            else if ( paging.page <= iHalf ) {
                iStart = 1;
                iEnd = iListLength;
            } else if ( paging.page >= (paging.pages-iHalf) ) {
                iStart = paging.pages - iListLength + 1;
                iEnd = paging.pages;
            } else {
                iStart = paging.page - iHalf + 1;
                iEnd = iStart + iListLength - 1;
            }

            for ( i=0, ien=an.length ; i<ien ; i++ ) {
                // Remove the middle elements
                $('li:gt(1)', an[i]).filter(':not(.zx-manager-pagination-last)').not('.zx-manager-pagination-next').remove();

                // Add the new list items and their event handlers
                for ( j=iStart ; j<=iEnd ; j++ ) {
                    var active = (j==paging.page+1);
                    sClass = active ? 'class="uk-active"' : '';
                    $('<li '+sClass+'>'+(active ? '<span>' : '<a href="#">')+j+(active ? '</span>' : '</a>')+'</li>')

                        .insertBefore( $('li.zx-manager-pagination-next, li.zx-manager-pagination-last', an[i])[0] )
                        .bind('click', function (e) {
                            e.preventDefault();

                            oSettings._iDisplayStart = (parseInt($('a', this).text(),10)-1) * paging.length;
                            fnCallbackDraw( oSettings );
                        });
                }

                // Add / remove disabled classes from the static elements
                if ( paging.page === 0 ) {
                    $('li.zx-manager-pagination-first, li.zx-manager-pagination-prev', an[i]).addClass('uk-disabled');
                } else {
                    $('li.zx-manager-pagination-first, li.zx-manager-pagination-prev', an[i]).removeClass('uk-disabled');
                }

                if ( paging.page === paging.pages-1 || paging.pages === 0 ) {
                    $('li.zx-manager-pagination-next, li.zx-manager-pagination-last', an[i]).addClass('uk-disabled');
                } else {
                    $('li.zx-manager-pagination-next, li.zx-manager-pagination-last', an[i]).removeClass('uk-disabled');
                }
            }

            // hide active page if only one
            if(paging.pages === 1) $('.zx-manager-pagination .uk-active').hide();
        }
    },

    pagination_simple = {
        fnInit: function( oSettings, nPaging, fnCallbackDraw ) {
            var oLang = oSettings.oLanguage.oPaginate;
            var fnClickHandler = function ( e ) {
                e.preventDefault();

                if ( oSettings.oApi._fnPageChange(oSettings, e.data.action) ) {
                    fnCallbackDraw( oSettings );
                }
            };

            $(nPaging).append(
                '<ul class="zx-manager-pagination">'+
                    '<li class="zx-manager-pagination-prev uk-disabled"><a href=""></a></li>'+
                    '<li class="zx-manager-pagination-next uk-disabled"><a href=""></a></li>'+
                '</ul>'
            );
            var els = $('a', nPaging);
            $(els[0]).bind( 'click.DT', { action: 'previous' }, fnClickHandler );
            $(els[1]).bind( 'click.DT', { action: 'next' }, fnClickHandler );
        },

        fnUpdate: function ( oSettings, fnCallbackDraw ) {
            var iListLength = 4;
            var paging = oSettings.oInstance.DataTable().page.info();
            var an = oSettings.aanFeatures.p;
            var i, ien, j, sClass, iStart, iEnd, iHalf=Math.floor(iListLength/2);

            if ( paging.pages < iListLength) {
                iStart = 1;
                iEnd = paging.pages;
            }
            else if ( paging.page <= iHalf ) {
                iStart = 1;
                iEnd = iListLength;
            } else if ( paging.page >= (paging.pages-iHalf) ) {
                iStart = paging.pages - iListLength + 1;
                iEnd = paging.pages;
            } else {
                iStart = paging.page - iHalf + 1;
                iEnd = iStart + iListLength - 1;
            }

            for ( i=0, ien=an.length ; i<ien ; i++ ) {

                // Add / remove disabled classes from the static elements
                if ( paging.page === 0 ) {
                    $('li.zx-manager-pagination-prev', an[i]).addClass('uk-disabled');
                } else {
                    $('li.zx-manager-pagination-prev', an[i]).removeClass('uk-disabled');
                }

                if ( paging.page === paging.pages-1 || paging.pages === 0 ) {
                    $('li.zx-manager-pagination-next', an[i]).addClass('uk-disabled');
                } else {
                    $('li.zx-manager-pagination-next', an[i]).removeClass('uk-disabled');
                }
            }
        }
    };

    ZX.datatables = datatables;
    ZX.datatables.settings = settings;
    ZX.datatables.pagination = pagination;
    ZX.datatables.pagination_simple = pagination_simple;

})(jQuery, zlux, UIkit);