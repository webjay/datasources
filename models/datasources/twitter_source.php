<?php
/**
 * Twitter API Datasource
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author Jacob Friis Saxberg <@webjay>
 * @link https://github.com/webjay/datasources
 * @package       datasources
 * @subpackage    datasources.models.datasources
 * 
 *
 * A CakePHP datasource for interacting with the Twitter API.
 *
 * Create a datasource in your config/database.php
 *  public $twitter = array(
 *		'datasource' => 'Datasources.TwitterSource',
 *		'key' => 'PUBLIC KEY',
 *		'secret' => 'SECRET KEY'
 *  );
 */

/**
 * Twitter Datasource
 *
 */
class TwitterSource extends DataSource {

	// oauth
	const requestToken = 'https://api.twitter.com/oauth/request_token';
	const authorize = 'https://api.twitter.com/oauth/authorize';
	const accessToken = 'https://api.twitter.com/oauth/access_token';
	// get info from Twitter
	const rateLimitStatus = 'http://api.twitter.com/1/account/rate_limit_status.json';
	const usersFollowing = 'http://api.twitter.com/1/friends/ids.json';
	const usersFollowers = 'http://api.twitter.com/1/followers/ids.json';
	const friendshipsIncoming = 'http://api.twitter.com/1/friendships/incoming.json';
	const friendshipsOutgoing = 'http://api.twitter.com/1/friendships/outgoing.json';
	const blocking = 'http://api.twitter.com/1/blocks/blocking/ids.json';
	const userIds = 'http://api.twitter.com/1/users/lookup.json';
	// make changes
	const notificationFollow = 'http://api.twitter.com/1/notifications/follow.json';
	const notificationLeave = 'http://api.twitter.com/1/notifications/leave.json';
	const reportSpam = 'http://api.twitter.com/1/report_spam.json';
	const friendshipCreate = 'http://api.twitter.com/1/friendships/create.json';
	const friendshipDestroy = 'http://api.twitter.com/1/friendships/destroy.json';
	const blockCreate = 'http://api.twitter.com/1/blocks/create.json';
	const blockDestroy = 'http://api.twitter.com/1/blocks/destroy.json';
	// lists
	const listSubscribe = 'http://api.twitter.com/version/lists/subscribers/create.json';
	const listUnsubscribe = 'http://api.twitter.com/version/lists/subscribers/destroy.json';
	const lists = 'http://api.twitter.com/1/lists.json';
	const listSubscribers = 'http://api.twitter.com/1/lists/subscribers.json';
	const listsFollowing = 'http://api.twitter.com/1/lists/subscriptions.json';
	const listAddUsers = 'http://api.twitter.com/1/lists/members/create_all.json';
	const listRemoveUser = 'http://api.twitter.com/1/lists/members/destroy.json';
	const listCreate = 'http://api.twitter.com/1/lists/create.json';
	const listUpdate = 'http://api.twitter.com/1/lists/update.json';
	const listDelete = 'http://api.twitter.com/1/lists/destroy.json';

	public function listSources ($data = null) {}
	
	/**
	 * Query Twitter.
	 *
	 * @param string $type internal method name
	 * @param array $parameters for internal method
	 * @return mixed data from internal method
	 */
	public function query ($type, $parameters = array()) {
		if (method_exists($this, $type)) {
			return call_user_func_array(array($this, $type), $parameters);
		} else {
			// check that const exists
			if (defined('self::'.$type) === false) {
				return false;
			}
			// create accessToken
			$accessToken = array(
				'oauth_token' => $parameters['oauth_token'],
				'oauth_token_secret' => $parameters['oauth_token_secret']
			);
			unset($parameters['oauth_token']);
			unset($parameters['oauth_token_secret']);
			// create url
			$url = constant('self::'.$type);
			$url .= '?'.http_build_query($parameters, null, '&');
			// get or send data
			$result = $this->fetch($url, $accessToken);
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
			'oauth_callback' => $callback,
			'force_login' => true
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
	
	private function fetch ($url, $accessToken) {
		try {
			$o = new OAuth($this->config['key'], $this->config['secret']);
			$o->setToken($accessToken['oauth_token'], $accessToken['oauth_token_secret']);
			$method = OAUTH_HTTP_METHOD_GET;
			$result = $o->fetch($url, null, $method);
			if ($result === true) {
				$response_info = $o->getLastResponseInfo();
				$this->log($response_info, 'debug');
				return $o->getLastResponse();
			}
		} catch (OAuthException $E) {
			$response = json_decode($E->lastResponse);
			$this->log($response);
		}
		return false;
	}
	
	private function rateLimitStatus ($accessToken) {
		return $this->fetch(self::rateLimitStatus, $accessToken);
	}

}

?>