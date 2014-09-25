<?php
/**
* @package		ZL Framework
* @author    	JOOlanders, SL http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders, SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

// register FilesystemHelper class
App::getInstance('zoo')->loader->register('PathHelper', 'helpers:path.php');

/*
	Class: zlfwHelperPath
		The ZLFW path helper class
*/
class zlfwHelperPath extends PathHelper {

	/*
		Function: resources
			returns a list of resources to the given resource

		Parameters:
			$resource

		Returns:
			array
	*/
	public function resources($resource)
	{
		$paths = (array) $this->app->path->paths($resource);
		$parts = explode(':', $resource, 2);
		$file  = ltrim($parts[1], "\\/");
		
		$return = array();
		foreach ($paths as $path) {
			if (($fullpath = realpath("$path/$file")) && is_readable($fullpath)) {
				$return[] = $fullpath;
			}
		}

		return $return;
	}

	public function path($resource)
	{
		if ($path = $this->pathZOO($resource))
		{
			return $path;
		}

		if ($path = $this->pathWK($resource))
		{
			return $path;
		}

		return null;
	}

	public function pathWK($resource)
	{
		// load widgetkit
		if (JFile::exists(JPATH_ADMINISTRATOR.'/components/com_widgetkit/widgetkit.php')) {
			require_once(JPATH_ADMINISTRATOR.'/components/com_widgetkit/widgetkit.php');
		}

		$widgetkit = Widgetkit::getInstance();
		return $widgetkit['path']->path($resource);
	}

	/**
	 * Check paths to system logs and tmp folders
	 *
	 * @return string   If check failed warning message will be returned
	 */
	public function checkSystemPaths(){
		$app = JFactory::getApplication();
		$suspect = false;

		$real_tmp_path = $app->getCfg('tmp_path');
		$tmp_expect = JPATH_SITE.'/tmp';
		if(($real_tmp_path != $tmp_expect) || !file_exists($real_tmp_path) || !is_writable($real_tmp_path)){
			$suspect = true;
		}

		if($suspect){
			return JText::sprintf('PLG_ZLFRAMEWORK_SYSTEM_FOLDER_SUSPECT');
		}else{
			return null;
		}
	}

	public function pathZOO($resource)
	{
		return $this->app->path->path($resource);
	}

	/*
     * Return the full directory path
	 *
	 * Original Credits:
	 * @package   	JCE
	 * @copyright 	Copyright © 2009-2011 Ryan Demmer. All rights reserved.
	 * @license   	GNU/GPL 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
	 * 
	 * Extended and adapted:
	 * by JOOlanders (zoolanders.com)
	 * Copyright 2011, JOOlanders SL
     */
	public function getDirectory($root, $allowroot = false)
	{
		$user = JFactory::getUser();

		// Restricted Joomla! folders
		$restricted = explode(',', 'administrator,cache,components,includes,language,libraries,logs,media,modules,plugins,templates,xmlrpc');

		// Remove whitespace
		$root = trim($root);
		// Convert slashes / Strip double slashes
		$root = preg_replace('/[\\\\]+/', '/', $root);
		// Remove first leading slash
		$root = ltrim($root, '/');
		
		// Split in parts to better manage
		$parts = explode('/', $root);

		// abort if path starts with a variable, a . or is empty
		if (preg_match('/[\.\[]/', $parts[0]) || (empty($root) && !$allowroot)) {
			return false;
		}

		// abort if path not allowed
		if (!$allowroot && in_array(strtolower($parts[0]), $restricted)) {
			return false;
		}
		
		// join back
		$root = implode('/', $parts);
		
		// set path variables
		jimport('joomla.user.helper');

		// user
		$groups = JUserHelper::getUserGroups($user->id);
		$groups = array_keys($groups);
		// get the first group
		$usergroupid = array_shift($groups);
		// usergroup table				
		$group = JTable::getInstance('Usergroup', 'JTable');
		$group->load($usergroupid);
		// usertype	
		$usergroup = $group->title;

		// Replace any path variables
		$pattern = array(
			'/\[userid\]/', '/\[username\]/', '/\[usergroup\]/', '/\[usergroupid\]/',
			'/\[day\]/', '/\[month\]/', '/\[year\]/'
		);
		$replace = array(
			$user->id, $user->username, $usergroup, $usergroupid,
			date('d'), date('m'), date('Y')
		);
		
		$root = preg_replace($pattern, $replace, $root);

		// split into path parts to preserve /
		$parts = explode('/', $root);
		// clean path parts
		$parts = $this->app->zlfw->filesystem->makeSafe($parts, 'utf-8');
		// join path parts
		$root = implode('/', $parts);
		
		return $root;
	}

}