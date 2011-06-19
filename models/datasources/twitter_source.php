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
	const requestTokenUrl = 'https://api.twitter.com/oauth/request_token';
	const authorizeUrl = 'https://api.twitter.com/oauth/authorize';
	const accessTokenUrl = 'https://api.twitter.com/oauth/access_token';
	// get info from Twitter
	const rateLimitStatusUrl = 'http://api.twitter.com/1/account/rate_limit_status.json';
	const usersFollowingUrl = 'http://api.twitter.com/1/friends/ids.json';
	const usersFollowersUrl = 'http://api.twitter.com/1/followers/ids.json';
	const friendshipsIncomingUrl = 'http://api.twitter.com/1/friendships/incoming.json';
	const friendshipsOutgoingUrl = 'http://api.twitter.com/1/friendships/outgoing.json'
	const blockingUrl = 'http://api.twitter.com/1/blocks/blocking/ids.json';
	const userIdsUrl = 'http://api.twitter.com/1/users/lookup.json';
	// make changes
	const notificationFollowUrl = 'http://api.twitter.com/1/notifications/follow.json';
	const notificationLeaveUrl = 'http://api.twitter.com/1/notifications/leave.json';
	const reportSpamUrl = 'http://api.twitter.com/1/report_spam.json';
	const friendshipCreateUrl = 'http://api.twitter.com/1/friendships/create.json';
	const friendshipDestroyUrl = 'http://api.twitter.com/1/friendships/destroy.json';
	const blockCreateUrl = 'http://api.twitter.com/1/blocks/create.json';
	const blockDestroyUrl = 'http://api.twitter.com/1/blocks/destroy.json';
	// lists
	const listSubscribeUrl = 'http://api.twitter.com/version/lists/subscribers/create.json';
	const listUnsubscribeUrl = 'http://api.twitter.com/version/lists/subscribers/destroy.json';
	const listsUrl = 'http://api.twitter.com/1/lists.json';
	const listSubscribersUrl = 'http://api.twitter.com/1/lists/subscribers.json';
	const listsFollowingUrl = 'http://api.twitter.com/1/lists/subscriptions.json';
	const listAddUsersUrl = 'http://api.twitter.com/1/lists/members/create_all.json';
	const listRemoveUserUrl = 'http://api.twitter.com/1/lists/members/destroy.json';
	const listCreateUrl = 'http://api.twitter.com/1/lists/create.json';
	const listUpdateUrl = 'http://api.twitter.com/1/lists/update.json';
	const listDeleteUrl = 'http://api.twitter.com/1/lists/destroy.json';
	
	/**
	 * Constructor
	 *
	 * @param array $config Configuration array
	 */
	public function __construct ($config) {
		parent::__construct($config);
	}

	/**
	 * Send the user to authorization.
	 *
	 * @param string $callback url to send the user after authorization
	 * @return array authorizeUrl, requestToken
	 */
	public function getAuthorizeUrl ($callback) {
		$o = new OAuth($this->key, $this->secret);
		$requestToken = $o->getRequestToken(self::requestTokenUrl, $callback);
		$authorizeUrl = self::authorizeUrl.'?'.http_build_query(array(
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
	 * @param array $request_token which we received from getAuthorizeUrl
	 * @param string $oauth_verifier which we GET from Twitter
	 * @return array access token
	 */
	public function getAccessToken ($requestToken, $oauthVerifier) {
		$o = new OAuth($this->config['key'], $this->config['secret']);
		$o->setToken($requestToken['oauth_token'], $requestToken['oauth_token_secret']);
		return $o->getAccessToken(self::accessTokenUrl, null, $oauthVerifier);
	}
	
	private function fetch ($url, $accessToken) {
		$o = new OAuth($this->config['key'], $this->config['secret']);
		$o->setToken($accessToken['oauth_token'], $accessToken['oauth_token_secret']);
		$result = $o->fetch($url);
		if ($result === true) {
			return json_decode($o->getLastResponse());
		}
		return false;
	}
	
	public function getRateLimitStatus ($accessToken) {
		return $this->fetch(self::rateLimitStatus, $accessToken);
	}

}

?>