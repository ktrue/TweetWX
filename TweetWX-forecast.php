<?php
/* TweetWX-forecast1.php

 This package is based on code by Joe Chung at http://nullinfo.wordpress.com/oauth-twitter/
 
 Repackaged for TweetWX by Ken True - Saratoga-weather.org  08-Jun-2010
 Docs at http://saratoga-weather.org/scripts-TweetWX.php
 TweetWX-forecast.php  V1.00 - 11-Jun-2010 - Initial relase

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
if (file_exists($wxdir . "Settings.php")) {
	include_once($wxdir . "Settings.php");
}

// Fill in the next 2 variables from the values produced in TweetWX-setup2.php.
$access_token = '-replace-with-token-from-TweetWX-setup2-'; // oauth_token
$access_token_secret = '-replace-with-token_secret-from-TweetWX-setup2-'; // oauth_token_secret

$doPrintNWS = false;
$_REQUEST['force'] = '1';
include_once($wxdir. 'advforecast2.php');
$DOWeek = array(
				'Sunday' => 'Sun',
				'Monday' => 'Mon',
				'Tuesday' => 'Tue',
				'Wednesday' => 'Wed',
				'Thursday' => 'Thu',
				'Friday' => 'Fri',
				'Saturday' => 'Sat',
				);
// Extract the 'good bits' from the forecasticons array for the text-only forecast
//
for ($i=0;$i<count($forecasticons);$i++) {
	$fparts = explode('<br />',strip_tags($forecasticons[$i],'<br>'));
//	print "<-- icon $i = '".$forecasticons[$i]." -->\n";
//	foreach ($fparts as $n => $t) {print "<-- part $n = '" . $t . "' -->\n"; }
    $t = $fparts[0];
	$t .= (trim($fparts[1])<>'')?' '.trim($fparts[1]):'';
	$t .= ': '. $fparts[3];
	$t .= (trim($fparts[4])<>'')?' '.trim($fparts[4]):'';
	$t .= ', '.$forecasttemp[$i] . '; ';
	$t = preg_replace('|\s\s+|is',' ',$t);
	$t = preg_replace('| &deg;|is','°',$t);
	$fcst[$i] = strip_tags($t);
}
// print_r($fcst);
$message = "NWS fcst: " . $fcst[0] . $fcst[1] . $fcst[2];
$message .= ' ' . $tweetTags;
$message = trim($message);
if (strlen($message) > 140) {
	echo $message . ' too long ('.strlen($message). ") -- reducing size<br/>\n";
	$message = preg_replace('|this afternoon|i','Aft.noon',$message);
//	$message = preg_replace('| night|is',' nite',$message);
	$message = preg_replace('|chance|is','Chc',$message);
	$message = preg_replace('|Partly|is','Ptly',$message);
	$message = preg_replace('|showers|is','Shwrs',$message);
	$message = preg_replace('|Mostly|is','Mstly',$message);
	$message = preg_replace('|Scattered|is','Sctd',$message);
	$message = preg_replace('|increasing|is','Incr.',$message);
	foreach ($DOWeek as $name => $abbrev) {
		$message = preg_replace("|$name|s",$abbrev,$message);
	}
}
		   
echo $message."<br/>len=".strlen($message)."<br/>status=";

$utfmessage = iconv('ISO-8859-1','UTF-8',$message);

// POST a tweet using OAuth authentication
$retarr = post_tweet(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET,
                           $utfmessage, $access_token, $access_token_secret,
                           true, true);
		
?>