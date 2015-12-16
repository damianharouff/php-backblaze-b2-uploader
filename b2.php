<?php

class B2Uploader{
	
	private $bucketId;

	// Authorization stuff
	private $accountId;
	private $applicationKey;
	private $credentials;
	private $authorizationToken;	// Filled in by Authorize() function

	// URLs
	private $apiUrl;
	private $downloadUrl;
	private $uploadUrl;


	public function getUploadURL(){
		$session = curl_init($this->getApiUrl() .  "/b2api/v1/b2_get_upload_url");

		// Add post fields
		$data = array("bucketId" => $this->getBucketId());
		$post_fields = json_encode($data);
		curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields); 

		// Add headers
		$headers = array();
		$headers[] = "Authorization: " . $this->getAuthorizationToken();
		curl_setopt($session, CURLOPT_HTTPHEADER, $headers); 

		curl_setopt($session, CURLOPT_POST, true); // HTTP POST
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);  // Receive server response
		$server_output = curl_exec($session); // Let's do this!

		// Check if we got a HTTP 200 status code
		if(curl_getinfo($session, CURLINFO_HTTP_CODE) != 200){
			throw new RuntimeException("Didn't get a 200 response code!");
		}

		curl_close ($session); // Clean up

		// Decode the JSON
		$response = json_decode($server_output);

		$this->uploadUrl = $response->uploadUrl;
		$this->authorizationToken = $response->authorizationToken;

	}

	public function authorize(){
		$url = "https://api.backblaze.com/b2api/v1/b2_authorize_account";

		$session = curl_init($url);

		// Add headers
		$headers = array();
		$headers[] = "Accept: application/json";
		$headers[] = "Authorization: Basic " . $this->getCredentials();
		curl_setopt($session, CURLOPT_HTTPHEADER, $headers);  // Add headers
		curl_setopt($session, CURLOPT_HTTPGET, true);  // HTTP GET
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true); // Receive server response

		$server_output = curl_exec($session);

		// Check if we got a HTTP 200 status code
		if(curl_getinfo($session, CURLINFO_HTTP_CODE) != 200){
			throw new RuntimeException("Didn't get a 200 response code!");
		}

		curl_close ($session);

		// Decode the JSON
		$response = json_decode($server_output);

		// Save the JSON data in our object
		$this->setApiUrl($response->apiUrl);
		$this->setAuthorizationToken($response->authorizationToken);
		$this->setDownloadUrl($response->downloadUrl);
	}

	public function uploadFile($path){
		$handle = fopen($path, 'r');
		$read_file = fread($handle,filesize($path));

		$sha1_of_file_data = sha1_file($path);

		$session = curl_init($this->uploadUrl);

		// Add read file as post field
		curl_setopt($session, CURLOPT_POSTFIELDS, $read_file); 

		// Add headers
		$headers = array();
		$headers[] = "Authorization: " . $this->getAuthorizationToken();
		$headers[] = "X-Bz-File-Name: " . basename($path);
		$headers[] = "Content-Type: " . 'b2/x-auto';
		$headers[] = "X-Bz-Content-Sha1: " . $sha1_of_file_data;
		curl_setopt($session, CURLOPT_HTTPHEADER, $headers); 

		curl_setopt($session, CURLOPT_POST, true); // HTTP POST
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);  // Receive server response
		$server_output = curl_exec($session); // Let's do this!
		curl_close ($session); // Clean up
		echo ($server_output); // Tell me about the rabbits, George!
	}

	private function curlRequest(){

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