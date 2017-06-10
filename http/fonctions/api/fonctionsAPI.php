<?php

function verifToken ($token){
	
	$tks = explode('.', $token);
	if (count($tks) != 3) {
		return false;
	}
    
	list($headb64, $payloadb64, $sign64) = $tks;
	
	if (null === ($header = json_decode(urlsafeB64Decode($headb64)))) {
		return false;
	}
	if (null === $payload = json_decode(urlsafeB64Decode($payloadb64))) {
		return false;
	}
	$sig = urlsafeB64Decode($sign64);
	
	
	if($sig === hash_hmac('sha256', $headb64 . "." . $payloadb64, 'esnnancy4everthebestAPI', true)){
		
		
		return $payload;
		
		
	}else{
		
		return false;
		
	}
	
	
	
}

function urlSafeB64Decode($b64){
	$b64 = str_replace(array('-', '_'),
			array('+', '/'),
			$b64);
	return base64_decode($b64);
}



?>