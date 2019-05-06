<?php
/* TweetWX-setup2.php

 This package is based on code by Joe Chung at http://nullinfo.wordpress.com/oauth-twitter/
 
 Repackaged for TweetWX by Ken True - Saratoga-weather.org  08-Jun-2010
 Docs at http://saratoga-weather.org/scripts-TweetWX.php
 TweetWX-setup2.php  V1.00 - 11-Jun-2010 - Initial relase

 This utility function should be used once to get the OAuth access_token and access_token_secret
 for use with the TweetWX-forecast.php and TweetWX-conditions.php.  It should only need to be run once.

// Version 1.01 - 12-Jul-2013 - added debugging output if request fails for token
// Version 1.02 - 18-Jan-2014 - fixes for OAuth using SSL only
 
*/
require 'TweetWX-globals.php';

// Fill in the next 3 variables from data by running TweetWX-setup1.php:
//
$request_token = '-replace-with-token-from-TweetWX-setup1-'; // oauth_token
$request_token_secret = '-replace-with-token-secret-from-TweetWX-setup1-'; // oauth_token_secret
$oauth_verifier= '-replace-with-verification-code-from-twitter';
//
if (phpversion() < 5) {
  echo 'Failure: This Script requires PHP version 5 or greater. You only have PHP version: ' . phpversion();
  exit;
}
//
?>
<h1>TweetWX Setup - part 2</h1>
<p>This last step of the setup will obtain a request token and request token secret string for use by
TweetWX to connect to your twitter account.</p>

<?php


// Get the access token using HTTP GET and HMAC-SHA1 signature
$retarr = get_access_token(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET,
                           $request_token, $request_token_secret,
                           $oauth_verifier, true, true, true);
if (! empty($retarr)) {
  list($info, $headers, $body, $body_parsed) = $retarr;
  if ($info['http_code'] == 200 && !empty($body)) {
	  print "<pre>\n";
	  print "Copy the following two lines of PHP assignment statements into TweetWX-conditions.php and TweetWX-forecast.php to replace the two values in the script.\n";
      print "\n#--copy code below-------------\n";
	  print '$access_token = \''.$body_parsed['oauth_token'].'\'; // oauth_token'."\n";
	  print '$access_token_secret = \''.$body_parsed['oauth_token_secret'].'\'; // oauth_token_secret'."\n";
	  print "#----end copy code above---------\n\n";
	  
	  print "Your setup of TweetWX is now complete.  The two access codes above will enable the scripts to\n";
	  print "securely connect and update your twitter account with status updates for conditions and forecasts.\n";
	  print "</pre>\n";
  } else {
	  print "<p>Something did not work right... here is the return from the request.</p>\n";
	  print "<pre>\n";
	  print "RC=".$info['http_code']."\n";
	  print "----- info array -----\n";
	  print_r($info,true);
	  print "-----headers of response-----\n";
	  print "$headers\n";
	  print "-----body of response------\n";
	  print "$body\n";
	  print "</pre>\n";
  }
}

exit(0);

/**
 * Get an access token using a request token and OAuth Verifier.
 * @param string $consumer_key obtained when you registered your app
 * @param string $consumer_secret obtained when you registered your app
 * @param string $request_token obtained from getreqtok
 * @param string $request_token_secret obtained from getreqtok
 * @param string $oauth_verifier obtained from twitter oauth/authorize
 * @param bool $usePost use HTTP POST instead of GET
 * @param bool $useHmacSha1Sig use HMAC-SHA1 signature
 * @param bool $passOAuthInHeader pass OAuth credentials in HTTP header
 * @return array of response parameters or empty array on error
 */
function get_access_token($consumer_key, $consumer_secret, $request_token, $request_token_secret, $oauth_verifier, $usePost=false, $useHmacSha1Sig=true, $passOAuthInHeader=true)
{
  $retarr = array();  // return value
  $response = array();

  $url = 'https://api.twitter.com/oauth/access_token';
  $params['oauth_version'] = '1.0';
  $params['oauth_nonce'] = mt_rand();
  $params['oauth_timestamp'] = time();
  $params['oauth_consumer_key'] = $consumer_key;
  $params['oauth_token']= $request_token;
  $params['oauth_verifier'] = $oauth_verifier;

  // compute signature and add it to the params list
  if ($useHmacSha1Sig) {
    $params['oauth_signature_method'] = 'HMAC-SHA1';
    $params['oauth_signature'] =
      oauth_compute_hmac_sig($usePost? 'POST' : 'GET', $url, $params,
                             $consumer_secret, $request_token_secret);
  } else {
    $params['oauth_signature_method'] = 'PLAINTEXT';
    $params['oauth_signature'] =
      oauth_compute_plaintext_sig($consumer_secret, $request_token_secret);
  }

  // Pass OAuth credentials in a separate header or in the query string
  if ($passOAuthInHeader) {
    $query_parameter_string = oauth_http_build_query($params, true);
    $header = build_oauth_header($params, "Twitter API");
    $headers[] = $header;
  } else {
    $query_parameter_string = oauth_http_build_query($params);
  }

  // POST or GET the request
  if ($usePost) {
    $request_url = $url;
    logit("getacctok:INFO:request_url:$request_url");
    logit("getacctok:INFO:post_body:$query_parameter_string");
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    $response = do_post($request_url, $query_parameter_string, 443, $headers);
  } else {
    $request_url = $url . ($query_parameter_string ?
                           ('?' . $query_parameter_string) : '' );
    logit("getacctok:INFO:request_url:$request_url");
    $response = do_get($request_url, 443, $headers);
  }

  // extract successful response
  if (! empty($response)) {
    list($info, $header, $body) = $response;
    $body_parsed = oauth_parse_str($body);
    if (! empty($body_parsed)) {
      logit("getacctok:INFO:response_body_parsed:");
    }
    $retarr = $response;
    $retarr[] = $body_parsed;
  }

  return $retarr;
}
?>
