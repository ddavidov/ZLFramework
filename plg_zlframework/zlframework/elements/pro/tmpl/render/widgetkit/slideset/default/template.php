<?php 
/**
* @package		ZL Framework
* @author    	JOOlanders, SL http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders, SL
* extends
* @package   Widgetkit
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

	$widget_id = $widget->id.'-'.uniqid();
	$settings  = $widget->settings;
	$sets      = array();
	$nav       = array();
	
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
	if (is_numeric($settings['items_per_set'])) {
		
		$sets = array_chunk($items, $settings['items_per_set']);

	} else {
	
		foreach ($items as $key => $item) {
			
			if (!isset($sets[$item['set']])) {
				$sets[$item['set']] = array();
			}

			$sets[$item['set']][] = $item;
		}

	}

	foreach (array_keys($sets) as $s) {
		$nav[] = ($settings['navigation'] == 2) ? '<li><span>'.$s.'</span></li>' : '<li><span></span></li>';
	}
?>

<div id="slideset-<?php echo $widget_id;?>" class="yoo-wk wk-slideset wk-slideset-default" data-widgetkit="slideset" data-options='<?php echo json_encode($settings); ?>'>
	<div>
		<div class="sets">
			<?php $i = 0; foreach ($sets as $set => $items) : ?>
				<ul class="set">
					<?php foreach ($items as &$item) : ?>
					<?php 
						/* Lazy Loading */
						$item["content"] = ($i==$settings['index']) ? $item["content"] : $widgetkit['image']->prepareLazyload($item["content"]);
					?>
					<li>
						<article class="wk-content"><?php echo $item['content']; ?></article>
						<?php if($settings['title']): ?>
						<strong class="title"><?php echo $item['title']; ?></strong>
						<?php endif; ?>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php $i=$i+1;?>
			<?php endforeach; ?>
		</div>
		<?php if ($settings['buttons'] && count($sets) > 1): ?><div class="next"></div><div class="prev"></div><?php endif; ?>
	</div>
	<?php if ($settings['navigation'] && count($nav) && count($sets) > 1) : ?>
	<ul class="nav <?php echo ($settings['navigation'] == 1) ? 'icon' : 'text'; ?>"><?php echo implode('', $nav); ?></ul>
	<?php endif; ?>
</div>