/* ===================================================
 * ZLfield
 * https://zoolanders.com/extensions/zlframework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
(function ($) {
	var Plugin = function(){};
	Plugin.prototype = $.extend(Plugin.prototype, {
		name: 'ZLfield',
		options: {
			url: '',
			type: '',
			enviroment: '',
			enviroment_args: ''
		},
		initialize: function(body, options) {
			this.options = $.extend({}, this.options, options);
			var $this = this;

			// on save/apply no initialized inputs are removed
			$('#toolbar-apply, #toolbar-save').click(function(){
				$('.zlfield input').each(function(){
					$(this).val() || $(this).remove();
				});
				return true;
			});

			$(document).ready(function()
			{
				// remove chosen script on J3
				$('.zlfield-main .chzn-container').each(function(){
					$(this).prev('select').data("chosen", null).show();
					$(this).remove();
				});

				// set element actions when added to a type
				$('.col-left ul.element-list').on('element.added', function(event, element){ 
					// set action
					$this.actions($(element));
				});

				// set element actions on sorting or added to a position
				$('ul.ui-sortable').on('sortstop', function(event, ui)
				{
					// Placeholders - Control name must be updated dinamically on each reorder or assignment
					var b = RegExp(/(elements\[[a-z0-9_-]+\])|(positions\[[a-z0-9_-]+\]\[[0-9]+\])/);
					$("#assign-elements ul.element-list:not(.unassigned)").each(function () {
						var c = "positions[" + $(this).data("position") + "]";
						$(this).children().each(function (d) {
							$(this).find("[data-control^=positions], [data-control^=elements]").each(function () {
								$(this).attr("data-control", "tmp" + $(this).attr("data-control").replace(b, c + "[" + d + "]"))
							})
						})
					});
					b = RegExp(/^tmp/);
					$("#assign-elements ul.element-list").find("[data-control^=tmp]").each(function () {
						$(this).attr("data-control", $(this).attr("data-control").replace(b, ""))
					}); // Placeholders END
	
					// set action
					$this.actions(ui.item);
				});

				// init actions
				$this.initActions();
			});
		},

		/* 
		 * initModules - init ZL Field on Modules
		 */
		initActions: function() {
			var $this = this,
				env = $this.options.enviroment;

			// init on Position view
			(env == 'type-positions' || env == 'type-edit') && 
			$('.col-left ul.ui-sortable > li.element').each(function(){
				$(this).parent().trigger('sortstop', { item: $(this) });
			});

			// init Core Elements on Edit view
			(env == 'type-edit') &&
			$('.col-left .core-element-configuration .element-list > li.element').each(function(){
				$this.actions($(this));
			});

			// init on Item Edit view
			env == 'item-edit' && $('.item-edit .creation-form .zlfield-main').each(function(){
				$this.actions($(this));
			});

			// init on Module view
			env == 'module' && $('form#module-form .zlfield-main').each(function(){
				// add Class for specific styling
				$(this).closest('li, .control-group').addClass('zlfield-module');
				$(this).closest('.controls').removeClass('controls'); // j3

				// call actions
				$this.actions($(this));
			})

			// init on App Config view
			env == 'app-config' && $('.col-right .zlfield-main').each(function(){
				// call actions
				$this.actions($(this));
			})
		},

		/* 
		 * Actions - set ZL Field actions
		 */
		actions: function($dom) {
			var $this = this;

			// only once or when insisted
			if (!$dom.data('zlfield-actions-init'))
			{
				/* 
				 * Fields Help Tips
				 */
				$dom.find('.qTipHelp').each(function(){
					var $qtip = $(this);
					$qtip.qtip({
						overwrite: false,
						content: {
							text: $qtip.find('.qtip-content')
						},
						position: {
							my: 'bottom center',
							at: 'top center',
							viewport: $(window)
						},
						show: {
							solo: true,
							delay: 300
						},
						hide: {
							fixed: true,
							delay: 300
						},
						style: 'ui-tooltip-light ui-tooltip-zlparam'
					});
				}); // Fields Help Tips END


				/* 
				 * Fields Select Expand
				 */
				$dom.find('.zl-select-expand').each(function(){
					var $expand = $(this);
					$expand.click(function(){
						$(this).prev().height(150);
						$(this).remove();
					}).qtip({
						overwrite: false,
						content: {
							text: $expand.data('zl-qtip')
						},
						position: {
							at: 'right top',
							my: 'left bottom'
						},
						show: {
							solo: true,
							delay: 600
						},
						hide: {
							delay: 0
						},
						style: 'ui-tooltip-light ui-tooltip-zlparam'
					});
				}); // Fields Select Expand END


				/* 
				 * Password Field
				 */
				$('#toolbar-apply, #toolbar-save').on('mousedown', function(){
					$dom.find('.zl-row[data-type=password] .zl-field input').each(function(){
						$(this).val('zl-decrypted['+$(this).val()+']');
					});
				})


				/* 
				 * Override Field
				 */
				$dom.find('.zl-state').each(function(){
					var $checkbox = $(this).find('input'),
						$row = $checkbox.closest('.zl-row');

					$checkbox.on('change', function(){
						var checkd = $(this).attr('checked') == 'checked'; // it is checked?

						if (checkd){
							$row.removeClass('zl-disabled')
							$row.find('.zl-field').children().removeAttr('disabled');
						} else {
							$row.addClass('zl-disabled')
							$row.find('.zl-field').children().attr('disabled', true);
						}
					})

					// init the action
					.trigger('change');
				}); // Override Field END


				/* 
				 * Override Field Tooltip
				 */
				$dom.find('.zl-state input').each(function(){
					var $checkbox = $(this);
					$checkbox.qtip({
						content: {
							text: 'Override this field' // default text
						},
						position: {
							at: 'left top',
							my: 'right bottom',
							effect: false
						},
						show: {
							target: $checkbox.closest('.zl-row'),
							solo: true,
							delay: 200
						},
						hide: {
							target: $checkbox.closest('.zl-row'),
							delay: 0
						},
						style: 'ui-tooltip-light ui-tooltip-zlparam',
						events: {
							show: function(event, api) {
								($checkbox.attr('checked') == 'checked') && event.preventDefault(); // Stop it!

								// hide if checkbox checked
								$checkbox.bind('change', function(){
									api.hide();
								});

								// Update the content of the tooltip on each show
								api.set('content.text', $checkbox.parent().attr('tooltip'));
							}
						}
					});
				}); // Override Field Tooltip END


				/* 
				 * Toggle Fields
				 */
				$dom.find('.zl-toggle').each(function(){
					var toggle = $(this),
						content = toggle.next();

					// set action
					$('.btn-open', toggle).on('click', function(){
						toggle.toggleClass('open') && content.show();
					});
					$('.btn-close', toggle).on('click', function(){
						toggle.toggleClass('open') && content.hide();
					});
				}); // Toggle Fields END


				/* 
				 * Dependents - Fields are shown/hidden depending on this field value
				 */
				$dom.find('[data-dependents]').each(function(){
					var field = $(this),
						rules = field.data("dependents").replace(/ /g, '').split('|'), // remove empty spaces and split into rules
						ph = field.closest('.zlfield.placeholder .wrapper, .zlfield.placeholder').first();

					// for each rule
					rules.each(function(val)
					{
						var c = val.split('!>'),
							m = c.length == 2 ? '!>' : '>'; // mode
							c = m == '>' ? val.split('>') : c, // second split if necesary
							d = c[0].split(','), // dependents array
							e = c[1].replace('NONE', ''); // dependent option

						// if select
						(field.data("type") == 'select' || field.data("type") == 'itemLayoutList' || field.data("type") == 'layout' || field.data("type") == 'apps' || field.data("type") == 'types' || field.data("type") == 'elements' || field.data("type") == 'modulelist' || field.data("type") == 'separatedby') 
						&& d.each(function(val) // for each dependent of the option
						{
							var dep = ph.find('[data-id="'+val+'"]').data('e', e).data('m', m).hide();

							field.find('.zl-field select').bind('change', function(){
								var e = dep.data('e'), // dependent value
									m = dep.data('m'), // dependent mode
									selection = $.makeArray($(this).val()), // for multiselect compatibility
									match = 0; // by default no match

								if (e && e.match(/OR/g)){
									$.each(selection, function(index, value){ // for each selected value
										// regex search value on begin/end of string or with OR in any side
										var re = new RegExp('(\\b|OR)'+value+'(\\b|OR)', 'g');
										( (m == '!>' && !e.match(re)) || (m == '>' && e.match(re)) ) && (match = 1);
										// check mode and Select value, mark any match
									})
								} else if (e && e.match(/AND/g)){
									var min = e.split('AND').length;
									(selection.length == min) && $.each(selection, function(index, value){
										// regex search value on begin/end of string or with AND in any side
										var re = new RegExp('(\\b|AND)'+value+'(\\b|AND)', 'g');
										if ( (m == '!>' && !e.match(re)) || (m == '>' && e.match(re)) ) {
											match = 1;
										} else { match = 0; }
										// check mode and Select value, mark only if all matched
									})
								} else {
									$.each(selection, function(index, value){
										( (m == '!>' && value != e) || (m == '>' && value == e) ) && (match = 1);
										// check mode and Select value, mark any match
									})
								}

								// if match Show, otherwise Hide
								match && dep.slideDown('fast') || dep.slideUp('fast');
							}).trigger('change');

						});
						
						// if checkbox
						(field.data("type") == 'checkbox') && d.each(function(val)
						{
							var dep = ph.find('[data-id="'+val+'"]').hide();
							field.find('.zl-field input').bind('change', function(){
								var checkd = $(this).attr('checked') == 'checked'; // it is checked?
								
								( (m == '!>' && !checkd) || (m == '>' && checkd) ) && dep.slideDown('fast') || dep.slideUp('fast');
								// check mode and Checkbox state, then slide Up or Down
							}).trigger('change');
						});

						// if radio
						(field.data("type") == 'radio') && d.each(function(val)
						{
							var option = e, // it must be declared local to avoid some weard issue that changes true string values to 1 number value
								dep = ph.find('[data-id="'+val+'"]').hide();
							field.find('.zl-field input').bind('change', function()
							{
								var checkd = $(this).attr('checked') == 'checked', // it is checked?
									match = 0, // by default no match
									value = $(this).attr('value');

								if(checkd) // proceed only if it's the checked input
								{
									if (option && option.match(/OR/g)){
										var re = new RegExp(value, 'g');
										( (m == '!>' && !option.match(re)) || (m == '>' && option.match(re)) ) && (match = 1);
										// check mode, value and check state for multiple values, mark any match
									}
									else
									{
										( (m == '!>' && value != option && checkd) || (m == '>' && value == option && checkd) && checkd) && (match = 1);
										// check mode, value and check state, mark any match
									}

									// if match Show, otherwise Hide
									match && dep.slideDown('fast') || dep.slideUp('fast');
								}

							}).trigger('change');
						});
						
						// if text
						(field.data("type") == 'text') && d.each(function(val)
						{
							var dep = ph.find('[data-id="'+val+'"]').hide();
							field.find('.zl-field input').on('keyup change', function(){
								var filled = $(this).val() != ''; // has text?
								
								( (m == '!>' && !filled) || (m == '>' && filled) ) && dep.slideDown('fast') || dep.slideUp('fast');
								// check mode and Input state, then slide Up or Down
							}).trigger('change');
						});
					});
				}); // Dependents END


				/* 
				 * Dependent - The field is shown/hidden depending on other fields values
				 */
				$dom.find('[data-dependent]').each(function(){
					var dep = $(this).hide(),
						rules = dep.data("dependent").replace(/ /g, ''), // remove empty spaces 
						AND_rules = rules.match(/AND/g) ? rules.split('AND') : [], // split AND rules
						OR_rules = rules.match(/OR/g) ? rules.split('OR') : [], // split OR rules
						ph = dep.parents('.zlfield.placeholder .wrapper, .zlfield.placeholder').first();
					
					dep.on('zlfield-dependent-event', function(event)
					{
						A_match = 0;
						if (AND_rules.length) A_match = $this.evaluateDependentRules(AND_rules, dep, ph, 'AND');
						else A_match = $this.evaluateDependentRules(OR_rules, dep, ph, 'OR');

						// Show if match, Hide otherwise
						A_match && dep.slideDown('fast') || dep.slideUp('fast');
					}).trigger('zlfield-dependent-event');

				}); // Dependent END


				/* 
				 * Relies On - Fields are loaded depending on other fields values
				 */
				$dom.find('[data-relieson]').each(function(){
					var placeholder = $(this), // placeholder
						b = placeholder.data('relieson'),
						c = placeholder.parents('.placeholder').find('[data-id="'+b.id+'"]'), // find the parent field
						select = c.find('select');
						
					// on select change
					select.on('change', function()
					{
						var val  = $(this).val() ? $(this).val() : '',
							ac   = $(this).closest('.zl-field'), // activity holder
							json = b.json, // convert json string to object
							ctrl  = placeholder.attr('data-control'), // field ctrl
							args = $(this).closest('.zlfield-main').attr('data-ajaxargs'),
							psv  = b.psv, // parents values
							pid  = b.id;

						// add current value to parents array
						psv[b.id] = val;

						// peform ajax request
						ac.append($('<span class="activity zl-loader">'));

						// make ajax request
						var jqxhr = $.ajax({
							type: 'POST',
							url: b.url+'&task=loadfield',
							data: {
								json:json, 
								ctrl:ctrl, 
								psv:psv, 
								pid:pid, 
								node:null, 
								args:args, 
								ajaxcall:true, 
								enviroment:$this.options.enviroment, 
								enviroment_args:$this.options.enviroment_args
							}
						})

						// if success
						.done(function(data){
							data = $.parseJSON(data);

							// remove activity indication
							ac.find('.activity').remove();

							// set data
							placeholder.slideUp('fast', function()
							{
								// remove old html
								placeholder.empty(); 

								// set new html if any
								data && data.result && placeholder.html('<div class="loaded-fields">'+data.result+'</div>');
								
								// init ZL Field Actions on the new fields
								$this.actions(placeholder.find('> .loaded-fields'));

								// show the new content
								placeholder.slideDown('fast');

								// trigger custom event for noticing the field was loaded
								select.trigger('loaded.zlfield');
							});
						})
					});
				}); // Relies On END


				/* 
				 * Load Field
				 */
				$dom.find('.load-field-btn').on('click', function(event){
					event.preventDefault();

					var $button = $(this),
						$wrapper = $button.parent('.zlfield-main'),
						$zlfield = $wrapper.data('ajaxargs');

					// add loading indicator
					$button.find('span').addClass('zlux-loader-raw');

					$.ajax({
						url : $this.options.url,
						type : 'POST',
						data: {
							task: 'loadZLfield',
							group: $zlfield.group,
							type: $this.options.type,
							control_name: $zlfield.control_name,
							json_path: $zlfield.json_path,
							element_id: $zlfield.element_id,
							element_type: $zlfield.element_type,
							enviroment: $zlfield.enviroment,
							node: $zlfield.node
						},
						success : function(data) {
							data = $.parseJSON(data);

							$wrapper.fadeOut('fast', function(){
								// set results
								$(this).html(data.result);

								// apply ZL Field Actions
								$this.actions($wrapper);

								// show the new content
								$wrapper.fadeIn('slow');
							});
						}
					});
				}); // Load Field

				/* 
				 * Items selector
				 */
				$('[data-type=items] .zl-field', $dom).each(function(){
					var $field = $(this),
						info = $('input.zlux-x-dummy', $field),
						cname = info.val(),
						params = info.data('params');

					// remove dummy input
					info.remove();

					// load the field JS
					$.zlux.assets.load('fields').done(function(){

						// init
						$field.zlux('fields', $.extend({}, params, {
							field: 'items',
							controlName: cname
						}));
					});

				}); // Items selector END

				/* 
				 * Options field
				 */
				$('[data-type=options] .zl-field', $dom).each(function(){
					var $field = $(this),
						info = $('input.zlux-x-dummy', $field),
						cname = info.val(),
						params = info.data('params');

					// remove dummy input
					info.remove();

					// load the field JS
					$.zlux.assets.load('fields').done(function(){

						// init
						$field.zlux('fields', $.extend({}, params, {
							field: 'options',
							controlName: cname
						}));
					});

				}); // Options field END

				/* 
				 * Attributes field
				 */
				$('[data-type=attributes] .zl-field', $dom).each(function(){
					var $field = $(this),
						info = $('input.zlux-x-dummy', $field),
						cname = info.val(),
						params = info.data('params');

					// remove dummy input
					info.remove();

					// load the field JS
					$.zlux.assets.load('fields').done(function(){

						// init
						$field.zlux('fields', $.extend({}, params, {
							field: 'attributes',
							controlName: cname
						}));
					});

				}); // Options field END

			$dom.data('zlfield-actions-init', !0)}
		},

		/* 
		 * evaluateDependentRules - evaluate the Dependent rules
		 */
		evaluateDependentRules: function(rules, dep, ph, mode) {
			var $this = this,
				A_match = 0, // by default no match
				loop = 1;

			rules.each(function(val)
			{
				if (!loop) return false; // workaround for braking the loop

				var c = val.split('!='),
					m = c.length == 2 ? '!=' : '=='; // mode
					c = m == '==' ? val.split('==') : c, // second split if necesary
					field = ph.find('[data-id="'+c[0]+'"]'), // the field value it depends on
					type = field.data("type"),
					unique_id = mode+'-'+dep.data("id")+'-'+field.data("id")+'-'+ph.data("id"),
					e = c[1].replace('NONE', ''); // dependent option

				// if select
				if (type == 'select' || type == 'itemLayoutList' || type == 'layout' || type == 'apps' || type == 'types' || type == 'elements' || type == 'modulelist' || type == 'separatedby')
				{
					var select = field.find('.zl-field select'),
						selection = $.makeArray(select.val()), // for multiselect compatibility
						match = 0;

					if (e && e.match(/OR/g)){
						$.each(selection, function(index, value){ // for each selected value
							// regex search value on begin/end of string or with OR in any side
							var re = new RegExp('(\\b|OR)'+value+'(\\b|OR)', 'g');
							( (m == '!=' && !e.match(re)) || (m == '==' && e.match(re)) ) && (match = 1);
							// check mode and Select value, mark any match
						})
					} else if (e && e.match(/AND/g)){
						var min = e.split('AND').length;
						(selection.length == min) && $.each(selection, function(index, value){
							// regex search value on begin/end of string or with AND in any side
							var re = new RegExp('(\\b|AND)'+value+'(\\b|AND)', 'g');
							// check mode and Select value, mark only if all matched
							if ( (m == '!=' && !e.match(re)) || (m == '==' && e.match(re)) ) {
								match = 1;
							} else { match = 0; }
						})
					} else {
						$.each(selection, function(index, value){
							// check mode and Select value, mark any match
							( (m == '!=' && value != e) || (m == '==' && value == e) ) && (match = 1);
						})
					}

					// save match
					A_match = match;

					if (!select.data('zlfield-dependent-'+unique_id+'-init')) {
						select.on('change', function(){
							dep.trigger('zlfield-dependent-event');
						});
					select.data('zlfield-dependent-'+unique_id+'-init', !0)}
				};
				
				// if checkbox
				if (type == 'checkbox')
				{
					var checkbox = field.find('.zl-field input'),
						match = 0,
						checkd = checkbox.attr('checked') == 'checked'; // it is checked?
						
						// check mode and Checkbox state, mark any match
						( (m == '!=' && !checkd) || (m == '==' && checkd) ) && (match = 1)

					// save match
					A_match = match;

					if (!checkbox.data('zlfield-dependent-'+unique_id+'-init')) {
						checkbox.on('change', function(){
							dep.trigger('zlfield-dependent-event');
						});
					checkbox.data('zlfield-dependent-'+unique_id+'-init', !0)}
				};

				// if radio
				if (type == 'radio')
				{
					var radio = field.find('.zl-field input'),
						match = 0, // by default no match
						option = e; // it must be declared local to avoid some weard issue that changes true string values to 1 number value

					radio.each(function()
					{
						var checkd = $(this).attr('checked') == 'checked', // it is checked?
							value = $(this).attr('value');

						if(checkd) // proceed only if it's the checked input
						{
							if (option && option.match(/OR/g)){
								var re = new RegExp(value, 'g');
								// check mode, value and check state for multiple values, mark any match
								( (m == '!=' && !option.match(re)) || (m == '==' && option.match(re)) ) && (match = 1);
							}
							else
							{
								// check mode, value and check state, mark any match
								( (m == '!=' && value != option && checkd) || (m == '==' && value == option && checkd) && checkd) && (match = 1);
							}
						}
					});

					// save match
					A_match = match;

					if (!radio.data('zlfield-dependent-'+unique_id+'-init')) {
						radio.on('change', function(){
							dep.trigger('zlfield-dependent-event');
						});
					radio.data('zlfield-dependent-'+unique_id+'-init', !0)}
				};
				
				// if text
				if (type == 'text')
				{
					var input = field.find('.zl-field input'),
						filled = input.val() != ''; // has text?
						
					// check mode and Input state, then slide Up or Down
					( (m == '!=' && !filled) || (m == '==' && filled) ) && (match = 1);
						
					// save match
					A_match = match;

					if (!input.data('zlfield-dependent-'+unique_id+'-init')) {
						input.on('keyup change', function(){
							dep.trigger('zlfield-dependent-event');
						});
					input.data('zlfield-dependent-'+unique_id+'-init', !0)}
				};

				// stop iteration if not match allready
				if ( (mode == 'AND' && !A_match) || (mode == 'OR' && A_match) ) loop = false;
			});
			
			return A_match;
		}
	});
	// Don't touch
	$.fn[Plugin.prototype.name] = function() {
		var args   = arguments;
		var method = args[0] ? args[0] : null;
		return this.each(function() {
			var element = $(this);
			if (Plugin.prototype[method] && element.data(Plugin.prototype.name) && method != 'initialize') {
				element.data(Plugin.prototype.name)[method].apply(element.data(Plugin.prototype.name), Array.prototype.slice.call(args, 1));
			} else if (!method || $.isPlainObject(method)) {
				var plugin = new Plugin();
				if (Plugin.prototype['initialize']) {
					plugin.initialize.apply(plugin, $.merge([element], args));
				}
				element.data(Plugin.prototype.name, plugin);
			} else {
				$.error('Method ' +  method + ' does not exist on jQuery.' + Plugin.name);
			}
		});
	};
})(jQuery);

/* ===================================================
 * ZLfield Widgetkit2 layout
 * https://zoolanders.com/extensions/zlframework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
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
				var modal = $(this);

				// hide content selector
				$('.uk-modal-header.uk-form', modal).hide();

				// get scope
				scope = angular.element( $('[ng-controller]', modal)[0] ).scope();

				// workaround, emulate data source was selected
				scope.selected = true;
        	});
        });
    });

})(jQuery, window);
