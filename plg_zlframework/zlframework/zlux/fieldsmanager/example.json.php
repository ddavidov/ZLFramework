<?php
/**
* @package		ZL Framework
* @author    	JOOlanders, SL http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders, SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

	return
	'{"fields": {

		'./* general - this options are available on allmost all fields */'
		"_id":{
			"type":"[type]",
			"settings":{
				'./* The type settings settings */'
			},
			"adjust_ctrl":{ './* adjust the ctrl on the fly only for the current field/subfields/wrapper */'
				"pattern":'.json_encode('/\[some_control\]/').',
				"replacement":""
			},
			"control": "custom_control", './* wrapp any individual or group fields with a control */'
			"renderif":{
				"ext_name":"0" './* extension name : 0, 1 - render if not/render if */'
			},
			"class":"rowCustomClass",
			"layout":"default/separator" './* The field layout. Optional */'
		}

		'./* separator */'
		"_id":{
			"type":"separator",
			"settings":{
				"title":"PLG_ZLFW_NAME"
			},
			"layout":"default/section/subsection"
		},

		'./* subfield */'
		"_id": {
			"type":"subfield",
			"path":"elements:pro\/tmpl\/file.php", './* file path where to search the sub params in json format */'
			"path":"elements:pro\/tmpl\/{_id}\/file.php", './* {_id} use any parent ID and will be replaced with it value */'
			"control":"control_name", './* set the control of the subfields, if necesary */'
			"arguments":{
				"params":{
					"type":"true"
				}
			}
		},

		'./* wrapper */'
		"_id": {
			"type":"wrapper", './* all 3 are diferent kind of wrappers sharing same options */'
			"min_count":"2", './* the wrapper will render only if min number of fields returns as a valid row */'
			"fields": {
				"_id":{},
				"_id":{}
			},
			"control":"control_name", './* set the control of the subfields, if necesary */'
			"layout":"default/control_wrapper/fieldset/toggle", './* all 3 are diferent kind of wrappers sharing same */ '
			"renderif":{
				"ext_name":"0" './* extension name : 0, 1 - render if not/render if */'
			}
		},


		'./* === FIELDS ===*/'

		'./* general - this options are available on allmost all fields */'
		"_id":{
			"type":"ANY",
			"label":"PLG_ZLFW_LABEL",
			"help":"PLG_ZLFW_HELP", './* PLG_ZLFW_HELP||{ZL_SOME_VAR} */'
			"default":"some_value",
			"dependents":"_id > NONE | _id !> opt OR opt2 | _id > opt", './* NONE = '', !> = not igual, > = iqual - show/hide on live other fields depending of the current field value. */'
			"childs":{
				"loadfields": { './* load other fields as childs of current that will have acces to its values, making dynamic behaviours */'
					"_id": {}
				}
			},
			"check_old_value":{
				"id":"old_id",
				"adjust_ctrl":{
					"pattern":'.json_encode('/\[lightbox_settings\]/').',
					"replacement":""
				},
				"translate_value":{
					"0":"none", './* if the old value meaning has changed, translate it */'
					"1":"_SKIPIT_" './* _SKIPIT_ will break the interaction and continue with normal param value method */'
				}
			}
		},
		
		'./* info - custom raw text for simple messages */'
		"_id":{
			"type":"info",
			"settings":{
				"text":"PLG_ZLFW_CUSTOM_TEXT"
			}
		},

		'./* text - html text input */'
		"_id":{
			"type":"text",
			"settings":{
				"placeholder":"PLG_ZLFW_PLACEHOLDER"
			}
		},

		'./* textarea - html textarea input*/'
		"_id":{
			"type":"textarea",
			"settings":{
				"value":"1"
			}
		},

		'./* hidden - html hidden input */'
		"_id":{
			"type":"hidden",
			"settings":{
				"value":"1"
			}
		},

		'./* password - html password input */'
		"_id":{
			"type":"password"
		},
		
		'./* checkbox - html checkbox input */'
		"_id":{
			"type":"checkbox",
			"settings":{
				"label":"JYES"
			}
		},

		'./* radio - html radio input */'
		"_id":{
			"type":"radio",
			"settings": {
				"options": {
					"Option1":"opt1",
					"Option2":"opt2"
				}
			}
		},
		
		'./* select - html select input */'
		"_id":{
			"type":"select",
			"settings": {
				"options": {
					"Option1":"",
					"Option2":"option2",
					"Option3":"option3"
				},
				"options":'.json_encode(array('name' => 'value')).',  './* Options can be provided trough PHP too */'
				"multi":"1", './* 0:default, 1 - if it's multiselectable */'
				"min_opts":"2", './* display only if min options reached */'
				"hidden_opts":"somevalue|anothervalue", './* avoid rendering this options */'
				"value_map":{
					"_id":"_chosenapps" './* uncomplete */'
				}
			}
		},

		'./* layout - html select input renders layouts as options */'
		"_id":{
			"type":"layout",
			"settings": {
				'./* it inherits all settings options from select plus have it's own */'
				"path":"elements:pro\/tmpl\/{value} , other:path\/allowed", './* path where to search the content, parent values evaluated */'
				"path":"elements:pro\/tmpl\/{_id}", './* {_id} use any parent ID and will be replaced with it value */'
				"path":"elements:pro\/tmpl\/{subfolders}\/folder", './* {subfolders} allow to search for resources in the Subfolders and merge them as options */'
				"mode":"folders", './* folder, files - the content to show as layouts */'
				"regex":'.json_encode('^([_A-Za-z0-9]*)\.php$').', './* reg ex to filter the results, the example will show only layouts starting with _ */'
			}
		},

		'./* apps - html select input renders ZOO apps as options */'
		"_id":{
			"type":"apps",
			"settings": {
				'./* it inherits all settings options from select */'
			}
		},

		'./* types - html select input renders ZOO types as options */'
		"_id":{
			"type":"types",
			"settings": {
				'./* it inherits all settings options from select plus */'
				"submissions":{
					"submission_id":"id" './* Filter Types based if they are part of provided submissions */'
				}
			}
		},

		'./* items - html form renders ZOO items as options */'
		"_id":{
			"type":"items",
			"settings": {
				"apps":"id id2", './* App filter, separete by space */'
				"types":"type type2", './* Type filter, separete by space */'
			}
		},

		'./* options - html form for name/value options creation */'
		"_id":{
			"type":"options"
		},

		'./* elements - html select input renders ZOO elements as options */'
		"_id":{
			"type":"elements",
			"settings": {
				'./* it inherits all settings options from select plus have it's own */'
				"elements":"elementType1 elementType2", './* Element Type filter, separete by space */'
				"apps":"appGroup1 appGroup2 appId", './* App filter, separete by space */'
				"types":"type1 type2" './* Type filter, separete by space */'
			}
		},

		'./* cats - html select input renders ZOO cats as options */'
		"_id":{
			"type":"cats",
			"settings": {
				'./* it inherits all settings options from select plus have it's own */'
				'./* uncomplete */'
			}
		},

		'./* itemLayoutList - html select input renders ZOO App layouts as options */'
		"_id":{
			"type":"itemLayoutList",
			"settings": {
				'./* it inherits all settings options from select plus have it's own */'
				"typefilter":"type,othertype"
			}
		},

		'./* modulelist - html select input renders Joomla! Modules as options */'
		"_id":{
			"type":"modulelist"
		},

		'./* Combination Example - Sublayout */'
		"_sublayout":{
			"type": "layout",
			"label": "PLG_ZLFRAMEWORK_SUB_LAYOUT",
			"help": "PLG_ZLFRAMEWORK_SUB_LAYOUT_DESC",
			"default": "_default.php",
			"settings": {
				"path":"elements:'.$element->getElementType().'\/tmpl\/render\/'.basename(dirname(__FILE__)).'\/_sublayouts",
				"regex":'.json_encode('^([_A-Za-z0-9]*)\.php$').',
				"minimum_options":"2"
			},
			"childs":{						
				"loadfields": {
					"layout_wrapper":{
						"type": "fieldset",
						"min_count":"1",
						"fields": {

							"subfield": {
								"type":"subfield",
								"path":"elements:'.$element->getElementType().'\/tmpl\/render\/'.basename(dirname(__FILE__)).'\/_sublayouts\/{value}\/params.php"
							}

						}
					}
				}
			}
		}

	},
	"control":"fieldscontrol"}';
?>