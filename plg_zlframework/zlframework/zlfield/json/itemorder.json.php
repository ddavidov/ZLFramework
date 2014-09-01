<?php
/**
* @package		ZL Framework
* @author    	JOOlanders, SL http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders, SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

	// avaible when integrated trough json as subfield
	$params = isset($params) ? $params : $this->app->data->create(array());

	// init vars
	$json = array();
	$node = $this->data->create($node);
	$psv  = $this->data->create($psv);

	$apps = (array)$psv->get('_chosenapps', $params->get('apps', array()));
	$allowed_types = (array) $psv->get('_chosentypes', $params->get('types', array()));

	// clean values
	$apps = array_filter($apps);
	$allowed_types = array_filter($allowed_types);

	// get apps
	$applications = array();
	if (!empty($apps)) {
		$applications = $this->app->zlfw->getApplications($apps);
	} else if ($group = $this->app->request->getString('group', false)) {
		$applications = array($this->app->object->create('Application')->setGroup($group));
	}

	// depricated method beacuse in modules for ex will not work
	// $applications = array($this->app->zoo->getApplication());

	/* add general options */
	$json[] =
	'"_reversed":{
		"type":"checkbox",
		"label":"PLG_ZLFRAMEWORK_REVERSE",
		"specific": {
			"label": "JYES",
			"value":"_reversed"
		}
	},
	"_priority":{
		"type":"checkbox",
		"label":"PLG_ZLFRAMEWORK_PRIORITY",
		"specific": {
			"label": "JYES",
			"value":"_priority"
		}
	},
	"_alphanumeric":{
		"type":"checkbox",
		"label":"PLG_ZLFRAMEWORK_ALPHANUMERIC",
		"specific": {
			"label": "JYES",
			"value":"_alphanumeric"
		}
	}';

	// if at least one app selected
	if (!empty($applications))
	{
		/* add core elements */
		$elements = $this->app->object->create('Type', array('_core', $applications[0]))->getCoreElements();
		
		// filter orderable elements
		$elements = array_filter($elements, create_function('$element', 'return $element->getMetaData("orderable") == "true";'));

		// set core options
		$options = array();

		if ($node->get('add_default')) {
			$options[''] = JText::_('default');
		}
		
		foreach ($elements as $element) {
			$options[$element->config->name ? $element->config->name : $element->getMetaData('name')] = $element->identifier;
		}

		$json[] =
		'"_core":{
			"type":"select",
			"label":"PLG_ZLFRAMEWORK_CORE",
			"specific": {
				"options":'.json_encode($options).'
			}
		}';

		/* add type elements */
		foreach ($applications as $application)
		{
			// get types
			$types = $application->getTypes();

			// filter types
			$types = !empty($allowed_types) ? array_filter($types, create_function('$type', 'return in_array($type->id, array(\''.implode('\', \'', $allowed_types).'\'));')) : $types;

			$type_json = array();
			if(!empty($types)) foreach ($types as $type)
			{
				$element_json = array();
				$elements = $type->getElements();
				$options = array('- '.JText::_('PLG_ZLFRAMEWORK_SELECT_ELEMENT').' -' => false);
				
				// filter orderable elements
				$elements = array_filter($elements, create_function('$element', 'return $element->getMetaData("orderable") == "true";'));

				if(!empty($elements))
				{
					// create element options
					foreach ($elements as $element) {
						$options[$element->config->name ? $element->config->name : $element->getMetaData('name')] = $element->identifier;
					}

					// elements
					$type_json[] =
					'"_'.$type->id.'":{
						"type":"select",
						"label":"'.$type->name.'",
						"specific": {
							"options":'.json_encode($options).'
						}
					}';

				} // end if Elements
			} // end Type foreach

			$json[] =
			'"_'.$application->id.'_fieldset":{
				"type":"wrapper",
				"fields": {'.implode(",", $type_json).'},
				"specific":{
					"toggle":{
						"label":"PLG_ZLFRAMEWORK_NAME_APP_ELEMENTS||'.$application->name.'"
					}
				},
				"layout":"fieldset"
			}';

		} // end App foreach

	}
	else
	{
		// if no apps return message instead
		$json[] =
		'"message_wrapper": {
			"type":"fieldset",
			"fields": {
				"message": {
					"type":"info",
					"specific":{
						"text":"PLG_ZLFRAMEWORK_IFT_ELEMENT_ORDERING_MSG"
					}
				}
			}
		}';	
	}

	// JSON
	return 
	'{"fields": {
		
		'. /* random */ '
		"_random":{
			"type":"checkbox",
			"label":"PLG_ZLFRAMEWORK_RANDOM",
			"specific": {
				"label": "JYES",
				"value":"_random"
			},
			"dependents":"_options_wrapper !> 1"
		},

		'. /* options */ '
		"_options_wrapper": {
			"type":"wrapper",
			"fields": {'.implode(",", $json).'}
		}

	}}';
?>