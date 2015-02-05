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
	$content   = array();
	$nav       = ($settings['navigation']) ? 'nav-'.$settings['navigation'] : '';
	
	// ZL integration
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

<div id="slideshow-<?php echo $widget_id; ?>" class="yoo-wk wk-slideshow wk-slideshow-list" data-widgetkit="slideshow" data-options='<?php echo json_encode($settings); ?>'>
	<div>
	
		<ul class="nav <?php echo $nav; ?> clearfix">
			<?php foreach ($items as $key => $item) : ?>
			<?php $content[] = '<li><article class="wk-content clearfix">'.$item['content'].'</article></li>'; ?>
			<li>
				<span><?php echo $item['title']; ?></span>
			</li>
			<?php endforeach; ?>
		</ul>
		
		<div class="slides-container"><?php echo (count($content)) ? '<ul class="slides">'.implode('', $content).'</ul>' : '';?></div>
		
	</div>
</div>