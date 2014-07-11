<?php
/**
* @package		ZL Framework
* @author    	JOOlanders, SL http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders, SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

	// load assets
	$this->app->zlfw->loadLibrary('qtip');
	
	// init vars
	$lparams = $this->app->data->create($params->get('layout'));
	$link = $this->app->link(array('task' => 'callelement', 'format' => 'raw', 'item_id' => $this->_item->id, 'item_layout' => $lparams->find('specific._itemlayout', 'related'), 'element' => $this->identifier), false);
	
	// unique id
	$el_id = 'qtip-'.uniqid();
	$dom   = "#$el_id";

	// get title
	$title = $this->app->zlfw->getqTipTitle($this, $params);
	
	// get content
	$result = $this->app->data->create($this->getRenderedValues($params));
	
	// get trigger
	$trigger = ($lparams->find('qtip._trigger_render') == '2' && $result->find('report.limited', false) == false) ? '' : $this->app->zlfw->getqTipTrigger($this, $params, $el_id);
	
	// get separator
	$separator = $params->find('separator._by_custom') != '' ? $params->find('separator._by_custom') : $params->find('separator._by');

	// get content
	if ($lparams->find('qtip._trigger_render') == '3') 
	{
		// only trigger
		$content = $this->app->zlfw->applySeparators($separator, $trigger, $params->find('separator._class'), $params->find('separator._fixhtml'));
	}
	else 
	{	// content + trigger
		$content = $this->app->zlfw->applySeparators($separator, $result->get('result', array()), $params->find('separator._class'), $params->find('separator._fixhtml')).$trigger;
	}
	
	// custom dom
	($lparams->find('qtip._trigger_content') == 'customdom') && $dom = $lparams->find('qtip._tg_dom');
	
	// render
	echo $content;
	
	// iframe option only if Static Content and on qTip render is enabled
	$iframelink = false;
	if ($this->getElementType() == 'staticcontent' && $params->find('layout.qtip.specific._render') == 'iframe')
	{
		$item_id = $params->find('layout.qtip.specific._iframe_itemsource') == 'current' ? $this->_item->id : $params->find('layout.qtip.specific._iframe_itemid');
		$iframelink = $params->find('layout.qtip.specific._iframe_custom_url') ? $params->find('layout.qtip.specific._iframe_custom_url') : $this->app->link(array('controller' => 'zlframework', 'task' => 'renderview', 'tmpl' => 'component', 'item_id' => $item_id, 'item_layout' => $params->find('layout.qtip.specific._iframe_itemlayout', 'related')), false);
	}
	
	// set loading effect if no iframe
	$iframelink || $lparams->set('qtip_additional_class', 'qtip-loading');
	
?>

<?php if ($trigger) : ?>
<script type="text/javascript">
jQuery(function($){

	$('<?php echo $dom ?>').qtip({
		content: {
			<?php if ($iframelink) : ?>
			text: '<iframe class="qtip-iframe" src="<?php echo $iframelink ?>" width="100%" height="99%"><p><?php echo JText::_('QT_IFRAME_NOT_SUPPORTED') ?></p></iframe>',
			title: {
				text: '<?php echo ($title ? $title : '') ?>',
				button: <?php echo ($lparams->find('qtip._button') ? 'true' : 'false'); ?>
			}
			<?php else : ?>
			text: '<div class="qtip-loader"></div>',
			ajax: {
				url: '<?php echo $link ?>',
				data: {
					method: 'returndata',
					'args[0]': '<?php echo json_encode(array('_layout' => 'default.php')) ?>',
					'args[1]': '<?php echo json_encode($lparams->find('qtip.separator')) ?>',
					'args[2]': '<?php echo json_encode($lparams->find('qtip.filter')) ?>',
					'args[3]': '<?php echo json_encode($lparams->find('qtip.specific')) ?>'
				},
				success: function(data, status) {
					var options = this.options;
					options.content.ajax = false;
					<?php echo ($lparams->find('qtip._button')); ?> && (options.content.title.button = true);
					options.style.classes = options.style.classes.replace('qtip-loading', 'qtip-loaded'); // remove the loading size
					
					// data is threated and populated in order to separate the title from the body
					var data = $('<div class="ui-content-wrapper" />').append(data),
						title = data.find('.pos-title') || data.find('.sub-pos-title');
					data.find('.pos-title, .sub-pos-title').remove();
					options.content.title.text = (<?php echo (int)($lparams->find('qtip._title') == 'loadeditemname'); ?> && title.html()) || '<?php echo addslashes($title) ?>';
					options.content.text = data;
					
					// destroy current and create new updated qtip
	 				this.destroy();
					$('<?php echo $dom ?>').qtip({
						content: options.content
					}).qtip('option', options).qtip('api').show();
				}
			}
			<?php endif; ?>
		},
		<?php echo $this->app->zlfw->getqtipOptions($this, $lparams) ?>
		
	}).click(function() { return false; });
	
});
</script>
<?php endif; ?>