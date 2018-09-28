<?php
/**
 * This file is part of the InaneClasses package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <philip@inane.co.za>
 * @package Inane\Version
 *
 * @license MIT
 * @license http://www.inane.co.za/license/MIT
 *
 * @copyright 2015-2016 Philip Michael Raab <philip@inane.co.za>
 */
namespace Inane\Version;

use Zend\Http;

/**
 * InaneClasses Version
 * 
 * @package Inane\Version
 * @version 0.1.0
 */
final class Version {
	/**
	 * Inane Classes version identification - see compareVersion()
	 */
	const VERSION = '0.12.6';
	
	/**
	 * Inane (www.inane.co.za) Service Identifier for version information is retrieved from
	 */
	const VERSION_SERVICE_INANE = 'INANE';
	
	/**
	 * Local (inane.local) Service Identifier for version information is retrieved from
	 */
	const VERSION_SERVICE_LOCAL = 'LOCAL';
	
	/**
	 * The latest stable version Inane Classes available
	 *
	 * @var string
	 */
	protected static $latestVersion;

	/**
	 * Compare the specified Inane Classes version string $version
	 * with the current Inane\Version\Version::VERSION of Inane Classes.
	 *
	 * @param  string  $version  A version string (e.g. "0.7.1").
	 * @return int           -1 if the $version is older,
	 *                           0 if they are the same,
	 *                           and +1 if $version is newer.
	 *
	 */
	public static function compareVersion($version) {
		$version = strtolower($version);
		$version = preg_replace('/(\d)pr(\d?)/', '$1a$2', $version);
		
		return version_compare($version, strtolower(self::VERSION));
	}

	/**
	 * Fetches the version of the latest stable release.
	 *
	 * By default, this uses the API provided by www.inane.co.za for version
	 * retrieval.
	 * 
	 * @api
	 *
	 * @param  string      $service    Version service with which to retrieve the version
	 * @param  Http\Client $httpClient HTTP client with which to retrieve the version
	 * @return string
	 */
	public static function getLatest($service = self::VERSION_SERVICE_INANE, Http\Client $httpClient = null) {
		if (null !== self::$latestVersion) {
			return self::$latestVersion;
		}
		
		self::$latestVersion = 'not available';
		
		if (null === $httpClient && ! ini_get('allow_url_fopen')) {
			trigger_error(sprintf('allow_url_fopen is not set, and no Zend\Http\Client ' . 'was passed. You must either set allow_url_fopen in ' . 'your PHP configuration or pass a configured ' . 'Zend\Http\Client as the second argument to %s.', __METHOD__), E_USER_WARNING);
			
			return self::$latestVersion;
		}
		
		$response = false;
		if ($service === self::VERSION_SERVICE_INANE) {
			$response = self::getLatestFromUrl($httpClient);
		} elseif ($service === self::VERSION_SERVICE_LOCAL) {
			$response = self::getLatestFromUrl($httpClient, 'http://inane.local/projects/version/inaneclasses');
		} else {
			trigger_error(sprintf('Unknown version service: %s', $service), E_USER_WARNING);
		}
		
		if ($response) {
			self::$latestVersion = $response;
		}
		
		return self::$latestVersion;
	}

	/**
	 * Returns true if the running version of Inane Classes is
	 * the latest (or newer??) than the latest returned by self::getLatest().
	 * 
	 * @api
	 *
	 * @return bool
	 */
	public static function isLatest() {
		return self::compareVersion(self::getLatest()) < 1;
	}

	/**
	 * Get the API response to a call from a configured HTTP client
	 *
	 * @param  Http\Client  $httpClient Configured HTTP client
	 * @return string|false API response or false on error
	 */
	protected static function getApiResponse(Http\Client $httpClient) {
		try {
			$response = $httpClient->send();
		} catch ( Http\Exception\RuntimeException $e ) {
			return false;
		}
		
		if (! $response->isSuccess()) {
			return false;
		}
		
		return $response->getBody();
	}

	/**
	 * Get the latest version from www.inane.co.za
	 *
	 * @param  Http\Client $httpClient Configured HTTP client
	 * @return string|null API response or false on error
	 */
	protected static function getLatestFromUrl(Http\Client $httpClient = null, $url = null) {
		if ($url === null)
			$url = 'http://www.inane.co.za/projects/version/inaneclasses';
		
		if ($httpClient === null) {
			$apiResponse = file_get_contents($url);
		} else {
			$request = new Http\Request();
			$request->setUri($url);
			$httpClient->setRequest($request);
			$apiResponse = self::getApiResponse($httpClient);
		}
		
		if (! $apiResponse) {
			return false;
		}
		
		return $apiResponse;
	}
}
