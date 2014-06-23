<?php
/**
* @package		ZL Framework
* @author		JOOlanders, SL http://www.zoolanders.com
* @copyright	Copyright (C) JOOlanders, SL
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

/*
	Class: zlfwHelperRequest
		The ZLFW request helper class
*/
class zlfwHelperRequest extends AppHelper {

	/**
	 * isAjax
	 *
	 * @return bool True if an ajax call is being made
	 */
	public function isAjax(){
		return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])=='xmlhttprequest');
	}
}