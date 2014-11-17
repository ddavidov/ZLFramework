<?php
/**
* @package		ZL Framework
* @author    	JOOlanders, SL http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders, SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

/*
   Class: zluxFieldsEngineJoomla
*/
class zluxFieldsEngineJoomla extends zluxFieldsEngine
{
	/**
	 * Class constructor
	 *
	 * @param string $app App instance.
	 */
	public function __construct($app)
	{
		parent::__construct($app);

		// register paths
		if ($path = $this->app->path->path('zluxFields:engine/joomla/fields')) {
			$this->app->path->register($path, 'zluxFields.fields');
		}
	}

	/*
		Function: row - Returns row html string
	*/
	public function renderField($fld, $id, $value, $args = array())
	{
		$field = parent::renderField($fld, $id, $value, $args);

		switch ($fld->get('layout')) {
			case 'raw':
			default:
				return $field;
				break;

			case 'control':
				return $this->app->joomla->isVersion('2.5') ? $this->getLabel($fld) . $field :
				'<div class="control-group">'
				. '<div class="control-label">' . $this->getLabel($fld) . '</div>'
				. '<div class="controls">' . $field . '</div>'
				.'</div>';
				break;
		}
	}

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 */
	protected function getLabel($fld)
	{
		$label = '';
		$text = JText::_($fld->get('label'));
		$desc = $fld->get('desc');

		// Build the class for the label.
		$class = !empty($desc) ? 'hasTooltip' : '';
		$class = $fld->get('required') ? $class . ' required' : $class;
		// $class = !empty($this->labelclass) ? $class . ' ' . $this->labelclass : $class;

		// Add the opening label tag and main attributes attributes.
		$label .= '<label id="' . $fld->get('id') . '-lbl" for="' . $fld->get('id') . '" class="' . $class . '"';

		// If a description is specified, use it to build a tooltip.
		if (!empty($desc))
		{
			JHtml::_('bootstrap.tooltip');
			$label .= ' title="' . JHtml::tooltipText(trim($text, ':'), JText::_($fld->get('desc')), 0) . '"';
		}

		// Add the label text and closing tag.
		if ($fld->get('required'))
		{
			$label .= '>' . $text . '<span class="star">&#160;*</span></label>';
		}
		else
		{
			$label .= '>' . $text . '</label>';
		}

		return $label;
	}
}