<?php

/**
 * Class zlfwHelperCheck
 * Diagnistic helper for checking required system settings or server settings
 */
class zlfwHelperCheck extends AppHelper {

	/**
	 * Check curl options
	 */
	public function checkCurl(){
		$errors = array();

		function_exists('curl_version') ? 'Enabled' : 'Disabled';
	}
}

 