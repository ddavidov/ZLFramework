<?php
/**
* @package		ZL Framework
* @author    	JOOlanders, SL http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders, SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

// init vars
$settings = json_decode($params->find('layout._widget'), true);

// load WK
if (!($wk = (@include JPATH_ADMINISTRATOR.'/components/com_widgetkit/widgetkit-app.php')
	and isset($settings['widget'])
	and $widget = $wk['widgets']->get($settings['widget']))
) {
	return;
}

JLoader::register('YOOtheme\Widgetkit\Content\Item', JPATH_ADMINISTRATOR . '/components/com_widgetkit/src/Content/Item.php');
$settings = array_merge($widget->getConfig('settings'), $settings);

// set Widget items
foreach ($items as $i => $item) {
	$items[$i] = new YOOtheme\Widgetkit\Content\Item($wk, $item);
}

// render
echo $wk['view']->render($widget->getConfig('view'), compact('widget', 'items', 'settings'));
