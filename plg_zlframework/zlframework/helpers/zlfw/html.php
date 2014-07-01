<?php
/**
* @package		ZL Framework
* @author    	ZOOlanders http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

/*
   Class: zlfwHelperHTML
   	  A class that contains zoo html functions
*/
class zlfwHelperHTML extends AppHelper {

	public function _($type) {

		// get arguments
		$args = func_get_args();

		// Check to see if we need to load a helper file
		$parts = explode('.', $type);

		if (count($parts) >= 2) {
			$func = array_pop($parts);
			$file = array_pop($parts);

			if (in_array($file, array('zoo', 'control')) && method_exists($this, $func)) {
				array_shift($args);
				return call_user_func_array(array($this, $func), $args);
			}
		}

		return call_user_func_array(array('JHTML', '_'), $args);

	}

	/**
	 * Converts a double colon seperated string or 2 separate strings to a string ready for bootstrap tooltips
	 *
	 * @param string $title
	 * @param string $content
	 * @param int $translate
	 * @param int $escape
	 *
	 * @return mixed|string
	 * @internal param $string
	 */
	public function tooltipText($title = '', $content = '', $translate = 1, $escape = 1){
		// Return empty in no title or content is given.
		if ($title == '' && $content == '')
		{
			return '';
		}

		// Split title into title and content if the title contains '::' (old Mootools format).
		if ($content == '' && !(strpos($title, '::') === false))
		{
			list($title, $content) = explode('::', $title, 2);
		}

		// Pass texts through the JText.
		if ($translate)
		{
			$title = JText::_($title);
			$content = JText::_($content);
		}

		// Escape the texts.
		if ($escape)
		{
			$title = str_replace('"', '&quot;', $title);
			$content = str_replace('"', '&quot;', $content);
		}

		// Return only the content if no title is given.
		if ($title == '')
		{
			return $content;
		}

		// Return only the title if title and text are the same.
		if ($title == $content)
		{
			return '<strong>' . $title . '</strong>';
		}

		// Return the formated sting combining the title and  content.
		if ($content != '')
		{
			return '<strong>' . $title . '</strong><br />' . $content;
		}

		// Return only the title.
		return $title;
	}

	/**
	 * Returns category select list html string.
	 *
	 * @param Application $application The application object
	 * @param array $options The options
	 * @param string $name The hmtl name
	 * @param string|array $attribs The html attributes
	 * @param string $key
	 * @param string $text
	 * @param string $selected The selected value
	 * @param string $idtag
	 * @param boolean $translate
	 * @param string $category The category id to build the select list for
	 *
	 * @return string category select list html
	 * @since 3.0.13
	 */
	public function categoryList($application, $type=null, $root_cat=0, $maxLevel=9999, $hide_empty=false, $prefix='-&nbsp;', $spacer='.&nbsp;&nbsp;&nbsp;')
	{
		// get tree list
		$cats = $this->app->tree->buildList($root_cat, $application->getCategoryTree(true, null, false), array(), $prefix, $spacer, '', 0, $maxLevel);

		// remove empty categories
		if ($hide_empty) 
			$cats = array_filter($cats, create_function('$category', 'return $category->totalItemCount();'));

		return $cats;
	}
}