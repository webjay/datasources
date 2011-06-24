<?php

App::uses('HttpSocket', 'Network/Http');

class GoogleSource extends DataSource {

	// Google search
	const gSearchUrl = 'http://ajax.googleapis.com/ajax/services/search/web';
	// Google Social Graph
	const LookUp = 'http://socialgraph.apis.google.com/lookup';
	const OtherMe = 'http://socialgraph.apis.google.com/otherme';
	
	private $socket = null;

	public function listSources () {}

	/**
	 * Query Google.
	 *
	 * @param string $resource
	 * @param array $parameters for resource
	 * @return mixed data from resource
	 */
	public function query ($resource, $parameters = array()) {
		if (method_exists($this, $resource)) {
			return call_user_func_array(array($this, $resource), $parameters);
		}
	}
	
	private function getSocket () {
		if ($this->socket === null) {
			$this->socket = new HttpSocket();
		}
		return $this->socket;
	}

	private function search ($q, $param = array()) {
		$query = array(
			'v' => '1.0',
			'q' => $q,
			'key' => $this->config['ApiKey'],
			'rsz' => 'large'
		);
		$query = array_merge($query, $param);
		$socket = $this->getSocket();
		$results = $socket->get(self::gSearchUrl, $query);
		if ($results === false) {
			$this->log('Google::search failed');
			return false;
		}
		$results = json_decode($results, true);
		if ($results !== null) {
			return $results['responseData']['results'];
		}
		return false;
	}

	private function lookup ($q, $param = array()) {
		if (is_array($q)) {
			$q = implode(',', $q);
		}
		$query = array(
			'sgn' => 1,
			'edo' => 1,
			'edi' => 1,
			'fme' => 1,
			'jme' => 1,
			'q' => $q
		);
		$query = array_merge($query, $param);
		$socket = $this->getSocket();
		$result = $socket->get(self::LookUp, $query);
		if ($result === false) {
			$this->log('Google::lookup failed');
			return false;
		}
		$result = json_decode($result, true);
		return $result['nodes'];
	}
	
	private function otherme ($q, $param = array()) {
		if (is_array($q)) {
			$q = implode(',', $q);
		}
		$query = array(
			'sgn' => 1,
			'q' => $q
		);
		$query = array_merge($query, $param);
		$socket = $this->getSocket();
		$result = $socket->get(self::OtherMe, $query);
		if ($result === false) {
			$this->log('Google::otherme failed');
			return false;
		}
		return json_decode($result, true);
	}

}

?>