<?php

class B2Uploader{

	// Variables set by user
	private $bucketId;

	// Authorization stuff
	private $accountId;
	private $applicationKey;
	private $credentials;
	private $authorizationToken;	// Filled in by Authorize() function

	// URLs
	const AUTHORIZE_URL = 'https://api.backblaze.com/b2api/v1/b2_authorize_account';
	private $apiUrl;
	private $downloadUrl;
	private $uploadUrl;


	public function getFilesInBucket(){
		$this->authorize();
		$this->getApiUrl();

		$url = $this->getApiUrl() .  "/b2api/v1/b2_list_file_names";

		$headers = array();
		$headers[] = "Authorization: " . $this->getAuthorizationToken();

		$postFields = array("bucketId" => $this->getBucketId());

		return $this->curlRequest('POST', $url, $headers, $postFields);
	}

	private function getUploadURL(){
		$url = $this->getApiUrl() .  "/b2api/v1/b2_get_upload_url";

		// Set the headers
		$headers = array();
		$headers[] = "Authorization: " . $this->getAuthorizationToken();

		// Post the bucketId fields
		$postFields = array("bucketId" => $this->getBucketId());

		// Ask CURL to make the request
		$response = $this->curlRequest('POST', $url, $headers, $postFields);

		$this->uploadUrl = $response->uploadUrl;
		$this->authorizationToken = $response->authorizationToken;
	}

	private function authorize(){
		// Set the headers
		$headers = array();
		$headers[] = "Accept: application/json";
		$headers[] = "Authorization: Basic " . $this->getCredentials();

		// Make the request
		$response = $this->curlRequest('GET', self::AUTHORIZE_URL, $headers);

		// Save the JSON data in our object
		$this->setApiUrl($response->apiUrl);
		$this->setAuthorizationToken($response->authorizationToken);
		$this->setDownloadUrl($response->downloadUrl);
	}

	public function uploadFile($path){
		// Authorize ourselves
		$this->authorize();

		// Get the uploadUrl
		$this->getUploadURL();

		// Open the file for reading
		$handle = fopen($path, 'r');
		$read_file = fread($handle,filesize($path));

		// Generate SHA1 hash
		$sha1_of_file_data = sha1_file($path);

		// Add headers
		$headers = array();
		$headers[] = "Authorization: " . $this->getAuthorizationToken();
		$headers[] = "X-Bz-File-Name: " . basename($path);
		$headers[] = "Content-Type: " . 'b2/x-auto';
		$headers[] = "X-Bz-Content-Sha1: " . $sha1_of_file_data;

		// Make the request
		$response = $this->curlRequest('POST', $this->uploadUrl, $headers, $read_file, true);

		print_r($response);
	}

	private function curlRequest($httpMethod = 'GET', $url, array $headers = [], $postFields = [], $uploadFile = false){

		$session = curl_init($url);

		// Add the file contents if we're uploading
		if($uploadFile){
			curl_setopt($session, CURLOPT_POSTFIELDS, $postFields);
		}else{
			// Add post fields
			if(count($postFields) != 0){
				$postFields = json_encode($postFields);
				curl_setopt($session, CURLOPT_POSTFIELDS, $postFields);
			}
		}

		// Add headers
		curl_setopt($session, CURLOPT_HTTPHEADER, $headers);

		// Set HTTP method
		if($httpMethod === 'GET'){
			curl_setopt($session, CURLOPT_HTTPGET, true);
		}else{
			curl_setopt($session, CURLOPT_POST, true);
		}

		// Receive server responses
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		// Fire the request
		$server_output = curl_exec($session);

		// Check if we got a HTTP 200 status code
		if(curl_getinfo($session, CURLINFO_HTTP_CODE) != 200){
			throw new RuntimeException("Didn't get a 200 response code: " . $server_output);
		}

		// Bye bye CURL!
		curl_close ($session);

		// Decode & return the JSON
		return json_decode($server_output);
	}

	private function getCredentials(){
		return base64_encode($this->accountId . ':' . $this->applicationKey);
	}

	/******************************** GETTERS AND SETTERS ********************************/
	public function setApplicationKey($key){
		$this->applicationKey = $key;
	}

	public function setAccountId($accountId){
		$this->accountId = $accountId;
	}

	public function setBucketId($id){
		$this->bucketId = $id;
	}

	private function getBucketId(){
		return $this->bucketId;
	}

	private function setApiUrl($url){
		$this->apiUrl = $url;
	}

	private function getApiUrl(){
		return $this->apiUrl;
	}

	private function setauthorizationToken($token){
		$this->authorizationToken = $token;
	}

	private function getAuthorizationToken(){
		return $this->authorizationToken;
	}

	private function setDownloadUrl($url){
		$this->downloadUrl = $url;
	}

}