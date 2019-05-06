<?php
/* TweetWX-conditions.php

 This package is based on code by Joe Chung at http://nullinfo.wordpress.com/oauth-twitter/
 
 Repackaged for TweetWX by Ken True - Saratoga-weather.org  08-Jun-2010
 Docs at http://saratoga-weather.org/scripts-TweetWX.php
 TweetWX-conditions.php  V1.00 - 11-Jun-2010 - Initial relase

 This utility function should be used once to get the OAuth request_token and request_token_secret
 for use in the next step of setup.
 
*/

if (phpversion() < 5) {
  echo 'Failure: This Script requires PHP version 5 or greater. You only have PHP version: ' . phpversion();
  exit;
}

// --------------- settings ----------------------
$tweetWXdir = './';
$wxdir = '../';  // path to the advforecast2.php
$tweetTags = '#weather';  // customize for your Twitter tags and/or website url (shortened)
// ----------- end settings ----------------------

require_once($tweetWXdir . 'TweetWX-globals.php');

// Fill in the next 2 variables from the values produced in TweetWX-setup2.php.
$access_token = '-replace-with-token-from-TweetWX-setup2-'; // oauth_token
$access_token_secret = '-replace-with-token_secret-from-TweetWX-setup2-'; // oauth_token_secret


include_once($wxdir . 'testtags.php');
$uomTemp = '°F';
$uomWind = 'mph';
$uomBaro = 'in';
$uomRain = 'in';

$sTemp = round(strip_units($temperature),0);;
$sHum  = "$humidity";
$sWind = "$dirlabel " . round($avgspd,0)."->".round(strip_units($maxgst),0).$uomWind;
  if (preg_match('|0->0|',$sWind) ){
	$sWind = "Calm";
  }
$sBaro = sprintf("%01.2f",round(strip_units($baro),2));
$sRain = sprintf("%01.2f",strip_units($dayrn));

$message = fixup_time($time)." $sTemp$uomTemp (H ".round(strip_units($maxtemp),0)."/L ".round(strip_units($mintemp),0).
		   ") $Currentsolardescription Hum:$sHum% Wind:$sWind ".
           "Baro: $sBaro$uomBaro Rain: $sRain$uomRain";
$message .= ' ' . $tweetTags;
$message = trim($message);
if (strlen($message) > 140) {
	echo $message . ' too long ('.strlen($message). ") -- reducing size<br/>\n";
	$message = preg_replace('|Mostly cloudy with clear patches|is','Mostly Cloudy',$message);
	$message = preg_replace('|Scattered|is','Sctd',$message);
}
		   
echo $message."<br/>len=".strlen($message)."<br/>status=";

$utfmessage = iconv('ISO-8859-1','UTF-8',$message);

// POST a tweet using OAuth authentication
$retarr = post_tweet(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET,
                           $utfmessage, $access_token, $access_token_secret,
                           true, true);

// print_r($retarr);

exit(0);

//=========================================================================
// change the hh:mm AM/PM to h:mmam/pm format
function fixup_time ( $WDtime ) {
  //if ($WDtime == "00:00: AM") { return ''; }
  $WDtime = preg_replace('|^00:|','12:',$WDtime);
  return date('g:ia' , strtotime($WDtime));
}

//=========================================================================
// strip trailing units from a measurement
// i.e. '30.01 in. Hg' becomes '30.01'
function strip_units ($data) {
  preg_match('/([\d\.\+\-]+)/',$data,$t);
  return $t[1];
}

?>