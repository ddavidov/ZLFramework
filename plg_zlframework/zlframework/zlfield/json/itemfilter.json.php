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
	$childs = array();

	// Elements
	$childs[] = isset($params['elements']) ? '"_chosenelms":{
		"type": "elements",
		"label": "'.$params->find('elements.label').'",
		"help": "'.$params->find('elements.help').'",
		"specific":{
			'.($params->find('elements.multi') ? '"multi":"1",' : '').'
			"value_map":{
				"apps":"_chosenapps",
				"types":"_chosentypes"
			}
			'.($params->find('elements.constraint') ? ',"constraint":'.json_encode($params->find('elements.constraint')) : '').'
		}
	}' : '';

	// Item Filter
	$childs[] = isset($params['itemfilter']) ? '"_filter_fieldset":{
		"type":"wrapper",
		"fields": {
			"_created_by":{
				"type":"text",
				"label":"PLG_ZLFRAMEWORK_AUTHOR",
				"help":"PLG_ZLFRAMEWORK_IFT_AUTHOR_DESC"
			},
			"_state":{
				"type":"select",
				"label":"PLG_ZLFRAMEWORK_IFT_PUBLISHED_STATE",
				"help":"PLG_ZLFRAMEWORK_IFT_PUBLISHED_STATE_DESC",
				"default":"1",
				"specific":{
					"options":{
						"PLG_ZLFRAMEWORK_ENABLED":"1",
						"PLG_ZLFRAMEWORK_DISABLED":"0",
						"PLG_ZLFRAMEWORK_ANY":""
					}
				}
			},
			"_frontpage":{
				"type":"select",
				"label":"PLG_ZLFRAMEWORK_IFT_FRONTPAGE_STATE",
				"help":"PLG_ZLFRAMEWORK_IFT_FRONTPAGE_STATE_DESC",
				"specific":{
					"options":{
						"PLG_ZLFRAMEWORK_ANY":"",
						"PLG_ZLFRAMEWORK_ENABLED":"1"
					}
				}
			},
			"_offset":{
				"type": "text",
				"label": "PLG_ZLFRAMEWORK_FT_OFFSET",
				"help": "PLG_ZLFRAMEWORK_FT_OFFSET_DESC"
			},
			"_limit":{
				"type": "text",
				"label": "PLG_ZLFRAMEWORK_FT_LIMIT",
				"help": "PLG_ZLFRAMEWORK_FT_LIMIT_DESC"
			},
			"item_dates": {
				"type":"subfield",
				"path":"zlfield:json/itemfilter/dates.json.php"
			},
			"elements_filter": {
				"type":"subfield",
				"path":"zlfield:json/itemfilter/elements.json.php"
			}
		},
		"specific":{
			"toggle":{
				"label":"PLG_ZLFRAMEWORK_FILTER"
			}
		},
		"layout":"fieldset",
		"control":"itemfilter"
	}' : '';

	// Item Order
	$childs[] = isset($params['itemorder']) ? '"_order_fieldset":{
		"type":"fieldset",
		"fields": {
			"_order_subfield": {
				"type":"subfield",
				"path":"zlfield:json/itemorder.json.php"
			}
		},
		"specific":{
			"toggle":{
				"label":"PLG_ZLFRAMEWORK_ORDER"
			}
		},
		"layout":"fieldset",
		"control":"itemorder"
	}' : '';

	// remove empty values
	$childs = array_filter($childs);


	// set main fields
	$main = array();

	/* Categories */
	if(isset($params['categories'])) $main[] = '"_chosencats":{
		"type": "cats",
		"label": "'.$params->find('categories.label').'",
		"help": "'.$params->find('categories.help').'",
		"specific": {
			'.($params->find('categories.multi') ? '"multi":"1",' : '').'
			"value_map":{
				"apps":"_chosenapps"
			}
		},
		"old_id":"_chosencat"
	}';

	/* Tags */
	if(isset($params['tags'])) $main[] = '"_chosentags":{
		"type": "tags",
		"label": "'.$params->find('tags.label').'",
		"help": "'.$params->find('tags.help').'",
		"specific": {
			'.($params->find('tags.multi') ? '"multi":"1"' : '').'
		}
	}';

	/* Types */
	if (isset($params['types'])) {

		$specific = array();
		$specific[] = $params->find('types.multi') ? '"multi":"1"' : '';
		$specific[] = $params->find('types.types') ? '"types":"'.$params->find('types.types').'"' : '';
		$specific[] = '"value_map":{"apps":"_chosenapps"}';
		$specific = array_filter($specific);

		$main[] = '"_chosentypes":{
			"type":"types",
			"label":"'.$params->find('types.label').'",
			"help":"'.$params->find('types.help').'",
			"help":"PLG_ZLFRAMEWORK_APP_TYPES_DESC",
			"specific":{'.implode(',', $specific).'},
			"childs":{
				"loadfields": {'.implode(",", $childs).'}
			}
		}';
	}

	$specific = array();
	$specific[] = $params->find('apps.multi') ? '"multi":"1"' : '';
	$specific[] = $params->find('apps.group') ? '"group":"'.$params->find('apps.group').'"' : '';
	$specific = array_filter($specific);

	return
	'{
		"_chosenapps":{
			"type": "apps",
			"label": "'.$params->find('apps.label').'",
			"help": "'.$params->find('apps.help').'",
			"specific":{'.implode(',', $specific).'},
			"childs":{
				"loadfields":{

					'./* main fields */'
					'.implode(",", $main).'

					'./* If no type, render Childs as App ones */'
					'.(!isset($params['types']) ? implode(",", $childs) : "").'

				}
			}
		}
	}';
?>