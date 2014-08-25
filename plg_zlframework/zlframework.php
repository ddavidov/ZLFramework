<?php
/**
* @package		ZL Framework
* @author    	JOOlanders, SL http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders, SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

// Import library dependencies
jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');

class plgSystemZlframework extends JPlugin {
	
	public $joomla;
	public $app;

	/**
	 * onAfterInitialise handler
	 *
	 * Adds ZOO event listeners
	 *
	 * @access	public
	 * @return null
	 */
	function onAfterInitialise()
	{
		// make sure ZOO exist
		if (!JFile::exists(JPATH_ADMINISTRATOR.'/components/com_zoo/config.php')
				|| !JComponentHelper::getComponent('com_zoo', true)->enabled) {
			return;
		}
		
		// load zoo
		require_once(JPATH_ADMINISTRATOR.'/components/com_zoo/config.php');
		
		// check if Zoo > 2.4 is loaded
		if (!class_exists('App')) {
			return;
		}
		
		// Get the Joomla and ZOO App instance
		$this->joomla = JFactory::getApplication();
		$this->app = App::getInstance('zoo');
		
		// load default and current language
		$this->app->system->language->load('plg_system_zlframework', JPATH_ADMINISTRATOR, 'en-GB');
		$this->app->system->language->load('plg_system_zlframework', JPATH_ADMINISTRATOR);
		
		// register plugin path
		if ( $path = $this->app->path->path( 'root:plugins/system/zlframework/zlframework' ) ) {
			$this->app->path->register($path, 'zlfw');
		}
		
		// register elements fields
		if ( $path = $this->app->path->path( 'zlfw:zlfield' ) ) {
			$this->app->path->register($path, 'zlfield'); // used since ZLFW 2.5.8
			$this->app->path->register($path.'/fields/elements', 'zlfields'); // temporal until all ZL Extensions adapted
			$this->app->path->register($path.'/fields/elements', 'fields'); // necessary since ZOO 2.5.13
		}
		
		// register elements - order is important!
		if ( $path = $this->app->path->path( 'zlfw:elements' ) ) {
			$this->app->path->register($path, 'elements'); // register elements path
		
			$this->app->loader->register('ElementPro', 'elements:pro/pro.php');
			$this->app->loader->register('ElementRepeatablepro', 'elements:repeatablepro/repeatablepro.php');
			$this->app->loader->register('ElementFilespro', 'elements:filespro/filespro.php');
		}

		if ( $path = JPATH_ROOT.'/media/zoo/custom_elements' ) {
			$this->app->path->register($path, 'elements'); // register custom elements path
		}
		
		// register helpers
		if ( $path = $this->app->path->path( 'zlfw:helpers' ) ) {
			$this->app->path->register($path, 'helpers');

			// necesary because of ZLElements old helper, this one overrides it
			$this->app->loader->register('zlHelper', 'helpers:zl.php');
		}
		
		// check and perform installation tasks
		if(!$this->checkInstallation()) return; // must go after language, elements path and helpers

		// let's define the check was succesfull to speed up other plugins loading
		if (!defined('ZLFW_DEPENDENCIES_CHECK_OK')) define('ZLFW_DEPENDENCIES_CHECK_OK', true);

		// register zlfield helper
		if ($this->app->path->path('zlfield:')) $this->app->loader->register('ZlfieldHelper', 'zlfield:zlfield.php');
		
		// register controllers
		if ( $path = $this->app->path->path( 'zlfw:controllers' ) ) {
			$this->app->path->register( $path, 'controllers' );
		}

		// register models
		if ( $path = $this->app->path->path( 'zlfw:models' ) ) {
			$this->app->path->register( $path, 'models' );
			$this->app->loader->register('ZLModel', 'models:zl.php');
			$this->app->loader->register('ZLModelItem', 'models:item.php');
		}

		// register classes
		if ( $path = $this->app->path->path( 'zlfw:classes' ) ) {
			$this->app->path->register($path, 'classes');
			$this->app->loader->register('ZLStorage', 'classes:zlstorage/ZLStorage.php');
		}
		
		// register events
		$this->app->event->register('TypeEvent');
		$this->app->event->dispatcher->connect('type:coreconfig', array($this, 'coreConfig'));
		$this->app->event->dispatcher->connect('application:sefparseroute', array($this, 'sefParseRoute'));
		$this->app->event->dispatcher->connect('type:beforesave', array($this, 'typeBeforeSave'));
		
		// perform admin tasks
		if ($this->joomla->isAdmin()) {
			$this->app->document->addStylesheet('zlfw:assets/css/zl_ui.css');
		}

		// init ZOOmailing if installed
		if ( $path = $this->app->path->path( 'root:plugins/acymailing/zoomailing/zoomailing' ) ) {
			
			// register path and include
			$this->app->path->register($path, 'zoomailing');
			require_once($path.'/init.php');
		}

		// load ZL Fields, workaround for first time using ZL elements
		if ($this->app->zlfw->isTheEnviroment('zoo-type-edit')) $this->app->zlfield->loadAssets();
	}

	/**
	 * Actions for type:beforesave event
	 */
	public function typeBeforeSave($event, $arguments = array())
	{
		$type = $event->getSubject();
		$elements = $type->config->get('elements');

		// search for decrypted passwords and encrypt
		array_walk_recursive($elements, 'plgSystemZlframework::_find_and_encrypt');

		// save result
		$type->config->set('elements', $elements);
	}

	protected static function _find_and_encrypt(&$item, $key)
	{
		$matches = array();
		if (preg_match('/zl-decrypted\[(.*)\]/', $item, $matches)) {
			$item = 'zl-encrypted['.App::getInstance('zoo')->zlfw->crypt($matches[1], 'encrypt').']';
		}
	}

	/**
	 * Setting the Core Elements
	 */
	public function coreConfig( $event, $arguments = array() ){
		$config = $event->getReturnValue();
		$config['_itemlinkpro'] = array('name' => 'Item Link Pro', 'type' => 'itemlinkpro');
		$event->setReturnValue($config);
	}
	
	/**
	 *  checkInstallation
	 */
	public function checkInstallation()
	{
		// if in admin views
		if ($this->app->zlfw->environment->is('admin.com_zoo admin.com_installer admin.com_plugins'))
		{
			return $this->_checkDependencies();
		}
		
		return true;
	}

	/**
	 *  _checkDependencies
	 */
	protected function _checkDependencies()
	{
		// prepare cache
		$cache = $this->app->cache->create($this->app->path->path('cache:') . '/plg_zlframework_dependencies', true, '86400', 'apc');

		// set plugins order
		$this->app->zlfw->checkPluginOrder();

		// checks if dependencies are up to date
		$status = $this->app->zlfw->dependencies->check("zlfw:dependencies.config");
		if (!$status['state']){

			// warn but not if in installer to avoid install confusions
			if (!$this->app->zlfw->environment->is('admin.com_installer'))
				$this->app->zlfw->dependencies->warn($status['extensions']);
		}

		// save state to cache
		if ($cache && $cache->check()) {
			$cache->set('updated', $status['state']);
			$cache->save();
		}
		
		return $status['state'];
	}

	/**
	 *  sefParseRoute
	 */
	public function sefParseRoute($event)
	{
		$app_id = $this->app->request->getInt('app_id', null);
		$app = $this->app->table->application->get($app_id);

		// check if was loaded
		if (!$app) return;
		
		$group = $app->getGroup();
		if($router = $this->app->path->path("applications:$group/router.php")){
			require_once $router;
			$class = 'ZLRouter'.ucfirst($group);
			$routerClass = new $class;
			$routerClass->parseRoute($event);
		}
	}
}