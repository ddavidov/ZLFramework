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
	Class: zlfwHelperZlux
		A class that contains ZLUX functions
*/
class zlfwHelperZlux extends AppHelper {

	/**
	 * The list of loaded zlux classes
	 * 
	 * @var array
	 */
	protected $_classes = array();

	/**
	 * Class Constructor
	 * 
	 * @param App $app A reference to the global App object
	 */
	public function __construct($app) {
		parent::__construct($app);

		// load class
		$this->app->loader->register('zlux', 'classes:zlux.php');
	}

	/**
	 * Get a zlux class
	 * 
	 * @param string $name The name of the class to retrieve
	 * 
	 * @return object The zlux class
	 */
	public function get($name)
	{	
		// load zlux class
		$name = strtolower($name);
		$class = 'zlux'.ucfirst($name);
		$this->app->loader->register($class, 'zlfw:zlux/'.ucfirst($name).'Manager/class.php');
		
		// add class, if not exists
		if (!isset($this->_classes[$name])) {
			$this->_classes[$name] = new $class($this->app);
		}

		return $this->_classes[$name];
	}
	
	/**
	 * Magic method to get a zlux class
	 * 
	 * @param string $name The name of the class
	 * 
	 * @return object The class object
	 */
	public function __get($name) {
		return $this->get($name);
	}

	/**
	 * Load ZLUX Main assets
	 */
	protected $_zlux1_loaded = false;
	protected $_zlux2_loaded = false;
	public function loadMainAssets($zlux2 = false)
	{
		// load zlux2 if indicated
		if($zlux2 && !$this->_zlux2_loaded) {
			$template = JFactory::getApplication()->getTemplate();
			$path = 'root:templates/'.$template.'/warp.php';

			// if no file found it's not warp 7, load zlux uikit css and js
			if (!$this->app->path->path($path)) {
				$this->app->document->addStylesheet('zlfw:vendor/zlux/css/zlux.uikit.min.css');
				$this->app->document->addScript('zlfw:vendor/zlux/js/uikit/uikit.min.js');
			} else {
				// load warp uikit js
				$this->app->document->addScript('root:templates/'.$template.'/warp/vendor/uikit/js/uikit.js');
				// and zlux styles
				$this->app->document->addStylesheet('zlfw:vendor/zlux/css/zlux.min.css');	
			}

			$this->app->document->addScript('zlfw:vendor/zlux/js/zlux.min.js');

			$this->loadVariables(true);

			// set loaded state
			$this->_zlux2_loaded = true;

		} else if(!$this->_zlux1_loaded) {

			// zlux assets
			$this->app->document->addStylesheet('zlfw:zlux/zluxMain.css');
			$this->app->document->addScript('zlfw:zlux/zluxMain.js');

			$this->loadBootstrap(true);
			$this->loadVariables();

			// set loaded state
			$this->_zlux1_loaded = true;
		}
	}

	/**
	 * Load ZL Bootstrap assets
	 */
	public function loadBootstrap($loadJS = false)
	{
		if ($this->app->joomla->isVersion('2.5')) {
			$this->app->document->addStylesheet('zlfw:zlux/assets/zlbootstrap/css/bootstrap-zl.min.css');
			$this->app->document->addStylesheet('zlfw:zlux/assets/zlbootstrap/css/bootstrap-zl-responsive.min.css');

			// if stated load JS too
			if ($loadJS) $this->app->document->addScript('zlfw:zlux/assets/zlbootstrap/js/bootstrap.min.js');

		// j3
		} else {
			
			// load the BS assets
			JHtml::_('bootstrap.framework');

			$this->app->document->addStylesheet('zlfw:zlux/assets/zlbootstrap/css/font-awesome-zl.min.css');
		}
	}

	/**
	 * Load JS Variables
	 */
	public function loadVariables($zlux2 = false)
	{
		// init vars
		$javascript = '';

		// save Joomla! URLs
		if ($zlux2) {
			$urls = array(
				'zlfw' => 'plugins/system/zlframework/zlframework/',
				'zlux' => 'plugins/system/zlframework/zlframework/vendor/zlux/',
				'ajax' => JURI::base() . 'index.php?option=com_zoolanders&format=raw&'. $this->app->session->getFormToken() .'=1',
				'root' => JURI::root()
			);

			$javascript .= "jQuery.zx.url.push(" . json_encode($urls) . ");";

		} else {
			$app_id = $this->app->zoo->getApplication() ? $this->app->zoo->getApplication()->id : '';

			$javascript .= 'jQuery.zlux.url._root = "' . JURI::root() . '";';
			$javascript .= 'jQuery.zlux.url._root_path = "' . JURI::root(true) . '";';
			$javascript .= 'jQuery.zlux.url._base = "' . JURI::base() . '";';
			$javascript .= 'jQuery.zlux.url._base_path = "' . JURI::base(true) . '";';
			$javascript .= 'jQuery.zlux.zoo.app_id = "' . $app_id . '";';
		}

		// set translations strings
		$translations = array
		(
			// ZLUX Main
			'APPLY_FILTERS' => 'PLG_ZLFRAMEWORK_ZLUX_APPLY_FILTERS',
			'REFRESH' => 'PLG_ZLFRAMEWORK_REFRESH',
			'DELETE' => 'PLG_ZLFRAMEWORK_DELETE',
			'RENAME' => 'PLG_ZLFRAMEWORK_RENAME',
			'NAME' => 'PLG_ZLFRAMEWORK_NAME',
			'TYPE' => 'PLG_ZLFRAMEWORK_TYPE',
			'SIZE' => 'PLG_ZLFRAMEWORK_SIZE',
			'CONFIRM' => 'PLG_ZLFRAMEWORK_CONFIRM',
			'AUTHOR' => 'PLG_ZLFRAMEWORK_AUTHOR',
			'CREATED' => 'PLG_ZLFRAMEWORK_CREATED',
			'ACCESS' => 'PLG_ZLFRAMEWORK_ACCESS',
			'ROUTE' => 'PLG_ZLFRAMEWORK_ROUTE',
			'ROOT' => 'PLG_ZLFRAMEWORK_ROOT',
			'SOMETHING_WENT_WRONG' => 'PLG_ZLFRAMEWORK_ZLUX_SOMETHING_WENT_WRONG',
			
			// ZLUX FilesManager
			'STORAGE_PARAM_MISSING' => 'PLG_ZLFRAMEWORK_ZLUX_FM_STORAGE_PARAM_MISSING',
			'INPUT_THE_NEW_NAME' => 'PLG_ZLFRAMEWORK_ZLUX_FM_INPUT_THE_NEW_NAME',
			'DELETE_THIS_FILE' => 'PLG_ZLFRAMEWORK_ZLUX_FM_DELETE_THIS_FILE',
			'DELETE_THIS_FOLDER' => 'PLG_ZLFRAMEWORK_ZLUX_FM_DELETE_THIS_FOLDER',
			'FOLDER_NAME' => 'PLG_ZLFRAMEWORK_ZLUX_FM_FOLDER_NAME',
			'EMPTY_FOLDER' => 'PLG_ZLFRAMEWORK_ZLUX_FM_EMPTY_FOLDER',

				// Upload
				'ADD_NEW_FILES' => 'PLG_ZLFRAMEWORK_ZLUX_FM_ADD_NEW_FILES',
				'START_UPLOADING' => 'PLG_ZLFRAMEWORK_ZLUX_FM_START_UPLOADING',
				'CANCEL_CURRENT_UPLOAD' => 'PLG_ZLFRAMEWORK_ZLUX_FM_CANCEL_CURRENT_UPLOAD',
				'NEW_FOLDER' => 'PLG_ZLFRAMEWORK_ZLUX_FM_NEW_FOLDER',
				'UPLOAD_FILES' => 'PLG_ZLFRAMEWORK_ZLUX_FM_UPLOAD_FILES',
				'DROP_FILES' => 'PLG_ZLFRAMEWORK_ZLUX_FM_DROP_FILES',
				
				// Errors
				'FILE_EXT_ERROR' => 'PLG_ZLFRAMEWORK_ZLUX_FM_ERR_FILE_EXT', 
				'FILE_SIZE_ERROR' => 'PLG_ZLFRAMEWORK_ZLUX_FM_ERR_FILE_SIZE',
				'RUNTIME_MEMORY_ERROR' => 'PLG_ZLFRAMEWORK_ZLUX_FM_ERR_RUNTIME_MEMORY',
				'S3_BUCKET_PERIOD_ERROR' => 'PLG_ZLFRAMEWORK_ZLUX_FM_ERR_S3_BUCKET_PERIOD',
				'S3_BUCKET_MISSCONFIG_ERROR' => 'PLG_ZLFRAMEWORK_ZLUX_FM_ERR_S3_BUCKET_MISSCONFIG',
				'UPLOAD_URL_ERROR' => 'PLG_ZLFRAMEWORK_ZLUX_FM_ERR_UPLOAD_URL',

				// plupload core strings
				'File extension error.' => 'PLG_ZLFRAMEWORK_FLP_FILE_EXTENSION_ERROR', 
				'File size error.' => 'PLG_ZLFRAMEWORK_FLP_FILE_SIZE_ERROR',
				'File count error.' => 'PLG_ZLFRAMEWORK_FLP_FILE_COUNT_ERROR',

			// ZLUX ItemsManager
			'IM_NO_ITEMS_FOUND' => 'PLG_ZLFRAMEWORK_ZLUX_IM_NO_ITEMS_FOUND',
			'IM_PAGINATION_INFO' => 'PLG_ZLFRAMEWORK_ZLUX_IM_PAGINATION_INFO',
			'IM_FILTER_BY_APP' => 'PLG_ZLFRAMEWORK_ZLUX_IM_FILTER_BY_APP',
			'IM_FILTER_BY_TYPE' => 'PLG_ZLFRAMEWORK_ZLUX_IM_FILTER_BY_TYPE',
			'IM_FILTER_BY_CATEGORY' => 'PLG_ZLFRAMEWORK_ZLUX_IM_FILTER_BY_CATEGORY',
			'IM_FILTER_BY_TAG' => 'PLG_ZLFRAMEWORK_ZLUX_IM_FILTER_BY_TAG'
		);

		// translate
		$translations = array_map(array('JText', '_'), $translations);

		// add to script
		if ($zlux2) {
			$javascript .= "jQuery.zx.lang.push(" . json_encode($translations) . ");";
			$javascript .= "jQuery.zx.init();";
		} else {
			$javascript .= "jQuery.zlux.lang.set(" . json_encode($translations) . ");";
		}

		// load the script
		$this->app->document->addScriptDeclaration($javascript);
	}

	/**
	 * Get Amazon S3 signed policy
	 */
	public function getAmazonS3signedPolicy($bucket, $secretkey)
	{
		// prepare policy
		$policy = base64_encode(json_encode(array(
			// ISO 8601 - date('c'); generates uncompatible date, so better do it manually
			'expiration' => date('Y-m-d\TH:i:s.000\Z', strtotime('+1 day')),  
			'conditions' => array(
				array('bucket' => $bucket),
				array('acl' => 'public-read'),
				array('starts-with', '$key', ''),
				// "Some versions of the Adobe Flash Player do not properly handle HTTP responses that have an empty body. 
				// To configure POST to return a response that does not have an empty body, set success_action_status to 201.
				// When set, Amazon S3 returns an XML document with a 201 status code." 
				// http://docs.amazonwebservices.com/AmazonS3/latest/dev/HTTPPOSTFlash.html
				array('success_action_status' => '201'),
				// Plupload internally adds name field, so we need to mention it here
				array('starts-with', '$name', ''), 	
				// One more field to take into account: Filename - gets silently sent by FileReference.upload() in Flash
				// http://docs.amazonwebservices.com/AmazonS3/latest/dev/HTTPPOSTFlash.html
				array('starts-with', '$Filename', ''), 
			)
		)));

		// sign policy
		$signature = base64_encode(hash_hmac('sha1', $policy, $secretkey, true));

		// return
		return array('signature' => $signature, 'policy' => $policy);
	}

	/**
	 * Convert an Item ZOO Object to ZLUX one
	 * 
	 * @param array $items List of ZOO Items Objects
	 * 
	 * @return array List of ZLUX Items Objects
	 */
	public function getItemObject($items)
	{
		// validate data
		settype($items, 'array');

		$objects = array();
		foreach ($items as $item)
		{
			// prepare author data
			$author = $item->created_by_alias;
			$author_id = $item->created_by;
			$users  = $this->app->table->item->getUsers($item->application_id);
			if (!$author && isset($users[$item->created_by])) {
				$author = $users[$item->created_by]->name;
				$author_id = $users[$item->created_by]->id;
			}

			// set object
			$objects[$item->id] = array(
				'id' => $item->id,
				'name' => $item->name,
				'_itemname' => $item->name, // necesary
				'application' => array(
					'name' => $item->getApplication()->name, 
					'id' => $item->getApplication()->id
				),
				'type' => array(
					'name' => $item->getType()->name,
					'id' => $item->getType()->id
				),
				'access' => JText::_($this->app->zoo->getGroup($item->access)->name),
				'created' => $item->created,
				'author' => array(
					'name' => $author,
					'id' => $author_id
				)
			);
		}

		return $objects;
	}
}