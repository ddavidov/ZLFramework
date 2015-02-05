<?php
/**
* @package		ZL Framework
* @author    	JOOlanders, SL http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders, SL
* extends
* @package   Widgetkit
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   YOOtheme Proprietary Use License (http://www.yootheme.com/license)
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

	$widget_id = $widget->id.'-'.uniqid();
	$settings  = $widget->settings;
	
	$items = $this->getRenderedValues($params, $widget->mode);
	$items = $items['result'];

	// if separator tag present wrap each item
	if(preg_match('/(^tag|\stag)=\[(.*)\]/U', $separator, $separated_by)){
		foreach($items as &$item) {
			$item['content'] = $this->app->zlfw->applySeparators($separated_by[0], $item['content'], $params->find('separator._class'), $params->find('separator._fixhtml'));
		}
	}
	unset($item);
?>

<div id="accordion-<?php echo $widget_id;?>" class="yoo-wk wk-accordion wk-accordion-default clearfix" <?php if (is_numeric($settings['width'])) echo 'style="width: '.$settings['width'].'px;"'; ?> data-widgetkit="accordion" data-options='<?php echo json_encode($settings); ?>'>
	<?php foreach ($items as $key => $item) : ?>
		<h3 class="toggler"><?php echo $item['title'];?></h3>
		<div><div class="content wk-content clearfix"><?php echo $item['content'];?></div></div>
	<?php endforeach; ?>
</div>