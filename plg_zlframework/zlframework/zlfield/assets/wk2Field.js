(function($, window) {

	$(function() {

    	$('.wk2-zl-selector').each(function(i, item){

    		var input = $(this).nextAll('input'),
    			span = $(this).nextAll('span'),
    			value;

            try {
                value = JSON.parse(input.val());
            } catch (e) {
                value = {};
            }

            // set initial widget label
            if (value.widget && window.widgetkit.config.widgets[value.widget]) {
            	span.html('('+window.widgetkit.config.widgets[value.widget].label+')');
            }
        });
    });

    $(function() {

		$('body').on('click', '.wk2-zl-selector', function(e) {
            e.preventDefault();

    		var input = $(this).nextAll('input'),
    			span = $(this).nextAll('span'),
    			widgets = $(this).data('zl-widgets'),
    			value, scope;

            try {
                value = JSON.parse(input.val());
            } catch (e) {
                value = {};
            }
            
            // init Widget assistent
            window.widgetkit.env.init(value, function(attrs) {
            	if (scope.widget) {
            		span.html('('+scope.widget.label+')');
            	}

                input.val(JSON.stringify(attrs));
            });

			$(window.widgetkit.env.modal.element).on('show.uk.modal', function() {
				var element = window.widgetkit.env.modal.element;

				// hide content selector
				$('.wk-modal-header.uk-form', element).hide();

				// hide unsupported widgets
				$('.wk-modal-body[ng-hide="widget"] li').each(function(i, item) {
					if ($.inArray(angular.element(item).scope().widget.name, widgets) == -1) {
						$(item).remove();
					}
				})

				// get scope
				scope = angular.element( $('[ng-controller]', element)[0] ).scope();

				// workaround, emulate selected data source
				scope.selected = true;
        	});
        });
    });

})(jQuery, window);
