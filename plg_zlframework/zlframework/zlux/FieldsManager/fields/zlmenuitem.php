<?php
/**
* @package		ZL Framework
* @author    	JOOlanders, SL http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders, SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

// WORKFLOW: all view params are rendered and nulled (if not the selected one). When View chosen the appropiate params are enabled.

// declare the JS
$this->app->document->addScriptDeclaration("
jQuery(document).ready(function($){

	// anulate all fields except the selected one, avoiding that way to save unnecessary data
	$('.zx-zoolanders-menu-params [data-zx-viewpath]').not('[data-zx-selected]').find('[name]').each(function(){
		$(this).attr('data-zx-ctrl', this.name);
		this.name = '';
	});

	// on extension chosen event
	$('.zx-zoolanders-menu-params select').on('change', function(){
		var sel = $(this);

		// reset all params
		$('.zx-zoolanders-menu-params [data-zx-viewpath]').hide().find('[name]').attr('name', '');
		
		// enable selected view params
		$('.zx-zoolanders-menu-params [data-zx-viewpath=\"'+sel.val()+'\"]').show().find('[name]').each(function(){
			this.name = $(this).data('zx-ctrl');
		});
	});
});");

// get the list of extensions
$exts = $this->app->event->dispatcher->notify($this->app->event->create(null, 'zoolanders:joomlamenuitems'))->getReturnValue();

// get the selected view
$selectedView = null;
foreach ($exts as $ext) foreach ($ext['views'] as $view) {
	if( ($this->values->get('name') == $ext['name']) && ($this->values->get('view') == $view['name']) ) {
		$selectedView = $view;
	}
}

if (!empty($exts)) {

	echo '<div class="zx-zoolanders-menu-params">';

	// set the view options
	$options = array();
	$options[] = $this->app->html->_('select.option', '', 'Choose the view');
	foreach ($exts as $ext) {
		foreach ($ext['views'] as $view) {
			$options[] = $this->app->html->_('select.option', $view['path'], $view['title']);
		}
	}

	$field = $this->app->html->_('select.genericlist', $options, $name, '', 'value', 'text', ($selectedView ? $selectedView['path'] : null), $name);

	// render the select
	echo '<div class="control-group">'
		. '<div class="control-label">' . $this->getLabel($fld) . '</div>'
		. '<div class="controls">' . $field . '</div>'
		.'</div>';

	// render each view params
	foreach ($exts as $ext) {

		foreach ($ext['views'] as $view) {
			$isSelected = ($this->values->get('name') == $ext['name']) && ($this->values->get('view') == $view['name']);
			$attrs = $isSelected ? ' data-zx-selected' : ' style="display: none;"';
		?>

			<div data-zx-viewpath="<?php echo $view['path']; ?>"<?php echo $attrs; ?>>

				<?php
				// render view params
				echo implode("\n", $this->renderFields($view['path']));

				// render necessary field controls
				echo '<input type="hidden" name="'.$this->control.'[name]'.'" value="'.$ext['name'].'" />';
				echo '<input type="hidden" name="'.$this->control.'[view]'.'" value="'.$view['name'].'" />';
				?>

			</div>
		<?php
		}
	}

	echo '</div>';

} else {
	echo JText::_('There are no views available');
}