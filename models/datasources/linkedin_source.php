<?php
/**
 * LinkedIn API Datasource
 * A CakePHP datasource for interacting with the LinkedIn API.
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author Jacob Friis Saxberg <@webjay>
 * @link https://github.com/webjay/datasources
 * @package       datasources
 * @subpackage    datasources.models.datasources
 * 
 */

class LinkedinSource extends DataSource {
	
	const requestToken = 'https://www.linkedin.com/uas/oauth/requestToken';
	const authorize = 'https://www.linkedin.com/uas/oauth/authorize';
	const accessToken = 'https://www.linkedin.com/uas/oauth/accessToken';
	const peopleSearch = 'http://api.linkedin.com/v1/people-search:(people:(id,first-name,last-name),num-results,member-url-resources:(member-url:(name,url)))';

	public function listSources () {}

	public function query ($resource, $arguments) {
		date_default_timezone_set('UTC');
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
			return json_decode($result, true);
		}
	}
	
	/**
	 * Send the user to authorization.
	 *
	 * @param string $callback url to send the user after authorization
	 * @return array authorize, requestToken
	 */
	private function authorize ($callback) {
		$o = new OAuth($this->config['key'], $this->config['secret']);
		$requestToken = $o->getRequestToken(self::requestToken, $callback);
		$authorizeUrl = self::authorize.'?'.http_build_query(array(
			'oauth_token' => $requestToken['oauth_token'],
			'oauth_callback' => $callback
		), null, '&');
		return array(
			'authorizeUrl' => $authorizeUrl, 
			'requestToken' => $requestToken
		);
	}
	
	/**
	 * The user came back from authorization and we can now get the access token.
	 *
	 * @param array $request_token which we received from getAuthorize
	 * @param string $oauth_verifier which we GET from Twitter
	 * @return array access token
	 */
	private function accessToken ($requestToken, $oauthVerifier) {
		$o = new OAuth($this->config['key'], $this->config['secret']);
		$o->setToken($requestToken['oauth_token'], $requestToken['oauth_token_secret']);
		return $o->getAccessToken(self::accessToken, null, $oauthVerifier);
	}
	
	private function fetch ($url, $parameters, $method, $accessToken) {
		try {
			$o = new OAuth($this->config['key'], $this->config['secret']);
			$o->setToken($accessToken['oauth_token'], $accessToken['oauth_token_secret']);
			$o->setTimestamp(time());
			$result = $o->fetch($url, $parameters, $method);
			if ($result === true) {
				$responseInfo = $o->getLastResponseInfo();
				$this->log($responseInfo, 'debug');
				return $o->getLastResponse();
			}
		} catch (OAuthException $E) {
			$response = json_decode($E->lastResponse, true);
			if ($response === null) {
				$this->log('LinkedIn: '.$E);
				pr($E);
			} else {
				pr($response);
				$this->log('LinkedIn: '.$response);
			}
		}
		return false;
	}

}

?>