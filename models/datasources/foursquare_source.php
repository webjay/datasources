<?php
/**
 * Foursquare API Datasource
 * A CakePHP datasource for interacting with the Foursquare API.
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author Jacob Friis Saxberg <@webjay>
 * @link https://github.com/webjay/datasources
 * @package       datasources
 * @subpackage    datasources.models.datasources
 * 
 */

class FoursquareSource extends DataSource {
	
	const authenticate = 'https://foursquare.com/oauth2/authenticate';
	const accessToken = 'https://foursquare.com/oauth2/access_token';
	const peopleSearch = 'https://api.foursquare.com/v2/users/search';
	const userInfo = 'https://api.foursquare.com/v2/users/';
	const apiDateVersion = 20110630;

	public function listSources () {}

	public function query ($resource, $arguments) {
		// set function parameters
		$parameters = $arguments[0];
		$method = empty($arguments[1]) ? OAUTH_HTTP_METHOD_GET : $arguments[1];
		$accessToken = empty($arguments[2]) ? false : $arguments[2];
		if (method_exists($this, $resource)) {
			if ($accessToken === false) {
				$param = $parameters;
			} else {
				$param = array_merge($parameters, $accessToken);
			}
			return call_user_func_array(array($this, $resource), $param);
		} else {
			// check that const exist
			if (defined('self::'.$resource) === false) {
				return false;
			}
			// create url
			$url = constant('self::'.$resource);
			// get or send data
			$result = $this->fetch($url, $parameters, $method, $accessToken);
			if ($result === false) {
				return false;
			}
			$return = json_decode($result, true);
			if (!isset($return['meta']['code']) || $return['meta']['code'] != 200) {
				return false;
			}
			return $return;
		}
	}
	
	private function fetch ($url, $parameters, $method, $accessToken) {
		$params = array(
			'oauth_token' => $accessToken,
			'v' => self::apiDateVersion
		);
		return file_get_contents($url.'?'.http_build_query(array_merge($params, $parameters), $method, '&'));
	}
	
	// public function userInfo ($userid, $parameters, $accessToken) {
	// 	return $this->fetch(self::userInfo.$userid, array(), OAUTH_HTTP_METHOD_GET, $parameters, $accessToken);
	// }
	
	/**
	 * Send the user to authenticate.
	 *
	 * @param string $callback url to send the user after authorization
	 * @return string url
	 */
	private function authenticate ($callback) {
		return self::authenticate.'?'.http_build_query(array(
			'client_id' => $this->config['key'],
			'response_type' => 'code',
			'redirect_uri' => $callback
		), null, '&');
	}
	
	/**
	 * The user came back from authorization and we can now get the access token.
	 *
	 * @param string $callback url
	 * @param string $code we get from 4sq
	 * @return string access token or bool false on error
	 */
	public function accessToken ($callback, $code) {
		// HttpSocket didnt work
		$body = @file_get_contents(self::accessToken.'?'.http_build_query(array(
			'client_id' => $this->config['key'],
			'client_secret' => $this->config['secret'],
			'grant_type' => 'authorization_code',
			'redirect_uri' => $callback,
			'code' => $code
		), null, '&'));
		if ($body === false) {
			return false;
		}
		$token = json_decode($body, true);
		return $token['access_token'];
	}

}

?>