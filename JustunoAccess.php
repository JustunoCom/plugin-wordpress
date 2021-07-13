<?php
	class JustunoAccess{
		private $apiKey;
		private $domain;
		private $email;
		private $apiEndpointUrl;
		private $guid;
		private $password;
		
		public function __construct($settings){
			$this->apiKey = $settings['apiKey'];
			$this->domain = $settings['domain'];
			$this->email = $settings['email'];		
			$this->guid = isset($settings['guid']) ? $settings['guid'] : null;
			$this->password = isset($settings['password']) ? $settings['password'] : null;
			$this->apiEndpointUrl = 'https://www.justuno.com/api/endpoint.html';
		}
		
		public function getWidgetConfig(){
			if(!extension_loaded("curl")){
				throw new JustunoAccessException('Plug-in requires php `curl` extension which seems to be not activated on this server. Please activate it.');
			}
			$params = array(
				'key'=>$this->apiKey,
				'email'=>$this->email,
				'domain'=>$this->domain,
				'action'=>'install'
			);
			if(isset($this->password)){
				$params['password'] = $this->password;
			}
			$query = http_build_query($params);		
			$tuCurl = curl_init(); 
			curl_setopt($tuCurl, CURLOPT_URL, "{$this->apiEndpointUrl}?$query");
			curl_setopt($tuCurl,CURLOPT_SSL_VERIFYPEER,false);  			  
			curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1); 
			$tuData = curl_exec($tuCurl); 		
			try{
				if(curl_errno($tuCurl)){
					throw new Exception(curl_error($tuCurl));				
				}															
				$dom = new DOMDocument;
				$dom->loadXML($tuData);								
				$nodes = $dom->getElementsByTagName('result');
				if(!$nodes || $nodes->length == 0)
					throw new Exception('Incorrect response from remote server');
				
				if($nodes->item(0)->nodeValue == 0){
					$nodes = $dom->getElementsByTagName('error');
					throw new Exception($nodes->item(0)->nodeValue);					
				}
				$justunoConf = array();				
				$nodes = $dom->getElementsByTagName('guid');
				if($nodes && $nodes->length !== 0){
					$this->guid = $justunoConf['guid'] = $nodes->item(0)->nodeValue;
				}
				$nodes = $dom->getElementsByTagName('embed');
				if($nodes && $nodes->length !== 0){
					$justunoConf['embed'] = $nodes->item(0)->nodeValue;							
				}
				$nodes = $dom->getElementsByTagName('conversion');
				if($nodes && $nodes->length !== 0){
					$justunoConf['conversion'] = $nodes->item(0)->nodeValue;							
				}				
				curl_close($tuCurl);
				return $justunoConf;
			}
			catch(Exception $e){				
				curl_close($tuCurl);
				throw new JustunoAccessException('Request error: '.$e->getMessage());
			}
			
		}
		
		public function getDashboardLink(){
			$params = array(
				'key'=>$this->apiKey,
				'email'=>$this->email,
				'domain'=>$this->domain,
				'action'=>'login',
				'guid'=>$this->guid
			);

			if(isset($this->password)){
				$params['password'] = $this->password;
			}
			$query = http_build_query($params);
			$tuCurl = curl_init();
			curl_setopt($tuCurl, CURLOPT_URL, "{$this->apiEndpointUrl}?$query");
			curl_setopt($tuCurl,CURLOPT_SSL_VERIFYPEER,false);
			curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
			$tuData = curl_exec($tuCurl);
			try{
				if(curl_errno($tuCurl)){
					throw new Exception(curl_error($tuCurl));
				}
				$dom = new DOMDocument;
				$dom->loadXML($tuData);
				$nodes = $dom->getElementsByTagName('result');
				if(!$nodes || $nodes->length == 0)
					throw new Exception('Incorrect response from remote server');

				if($nodes->item(0)->nodeValue == 0){
					$nodes = $dom->getElementsByTagName('error');
					throw new Exception($nodes->item(0)->nodeValue);
				}
				$nodes = $dom->getElementsByTagName('secure_login_url');
				if($nodes && $nodes->length !== 0){
					$secureLoginUrl = $nodes->item(0)->nodeValue;
				}
				curl_close($tuCurl);
				return $secureLoginUrl;
			}
			catch(Exception $e){
				curl_close($tuCurl);
				throw new JustunoAccessException('Request error: '.$e->getMessage());
			}
		}
	}
	
	class JustunoAccessException extends Exception{
		
	}
