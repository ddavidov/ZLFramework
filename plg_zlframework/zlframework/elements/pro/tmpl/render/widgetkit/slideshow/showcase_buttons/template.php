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
	$sets  = array_chunk($items, $settings['items_per_set']);

	foreach (array_keys($sets) as $s) {
		$nav[] = '<li><span></span></li>';
	}

	// create the settings object
	$settings = $this->app->data->create($settings);

	// if separator tag present wrap each item
	if(preg_match('/(^tag|\stag)=\[(.*)\]/U', $separator, $separated_by)){
		foreach($items as &$item) {
			$item['content'] = $this->app->zlfw->applySeparators($separated_by[0], $item['content'], $params->find('separator._class'), $params->find('separator._fixhtml'));
		}
	}
	unset($item);
?>

<div id="showcase-<?php echo $widget_id; ?>" class="yoo-wk wk-slideshow-showcasebuttons" data-widgetkit="showcase" data-options='<?php echo json_encode($settings); ?>'>

	<div id="slideshow-<?php echo $widget_id; ?>" class="wk-slideshow">
		<div class="slides-container">
			<ul class="slides">
				<?php $i = 0; foreach ($items as $key => &$item) : ?>
				<?php  
					/* Lazy Loading */
					$item["content"] = ($i==$settings['index']) ? $item["content"] : $widgetkit['image']->prepareLazyload($item["content"]);
				?>
				<li>
					<article class="wk-content clearfix"><?php echo $item['content']; ?></article>
				</li>
				<?php $i=$i+1;?>
				<?php endforeach; ?>
			</ul>
			<?php if ($settings->get('buttons')): ?><div class="next"></div><div class="prev"></div><?php endif; ?>
		</div>
	</div>

	<div id="slideset-<?php echo $widget_id;?>" class="wk-slideset <?php if (!$settings['slideset_buttons']) echo 'no-buttons'; ?>">
		<div>
			<div class="sets">
				<?php foreach ($sets as $set => $items) : ?>
				<ul class="set">
					<?php foreach ($items as $item) : ?>
					<li>
						<div><div><?php echo $item['navigation']; ?></div></div>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php endforeach; ?>
			</div>
			<?php if ($settings->get('slideset_buttons') && count($sets) > 1): ?><div class="next"></div><div class="prev"></div><?php endif; ?>
		</div>
	</div>
	
</div>