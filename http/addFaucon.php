<?php

$url = 'http://api.faucondor.ixesn.fr/user';
//$data = array('firstname' => 'Test', 'lastname' => 'Test', 'emailGalaxy' => 'TestAPI@ixesn.fr', 'section' => 'FR-NANC-ESN');
//$userName = array('firstname' => 'Test', 'lastname' => 'TestModule', 'emailGalaxy' => 'TestModule@ixesn.fr', 'section' => 'FR-NANC-ESN', 'mobile'=> '00');
//$data = array("userName['firstname']" => 'Test');
//echo json_encode($userName);
//echo http_build_query($data);



$data = '"userName": [{"firstname": "Test", "lastname": "Test", "emailGalaxy": "TestAPI@ixesn.fr", "section": "FR-NANC-ESN"}]';


$date = date(DATE_ATOM); 
$nonce = md5(rand()); 
$headers = array('Content-type: application/json\r\n', 'x-wsse: UsernameToken Username="api", PasswordDigest="'.base64_encode(sha1($nonce.$date.'IcaLzawFsGl7PmRj7WKlPxUfLGBM5TtIFj1dd7nlzVpv5t+b2/cM47QGXDx1ggoboR3xUydlQERfi9L2Xf4m5A==')).'", Nonce="'.$nonce.'", Created="'.$date.'"\r\n');
//application/x-www-form-urlencoded\r\n

$options = array(
    'http' => array(
        'header'  => $headers,
        'method'  => 'POST',
        'content' => $data
    )
);



$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
if ($result === FALSE) { echo("erreur"); }

echo($result);



