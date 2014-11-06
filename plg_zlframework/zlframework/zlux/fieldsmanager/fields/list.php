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
$attrs = '';
$preoptions = $fld->find('settings.options') ? $fld->find('settings.options') : array('JYES' => '1', 'JNO' => '0');
$options = array(); foreach ($preoptions as $text => $val) $options[] = $this->app->html->_('select.option', $val, $text);

echo $this->app->html->_('select.genericlist', $options, $name, $attrs, 'value', 'text', $this->values->get($id), $name);