<?php
/* TweetWX-setup1.php

 This package is based on code by Joe Chung at http://nullinfo.wordpress.com/oauth-twitter/
 
 Repackaged for TweetWX by Ken True - Saratoga-weather.org  08-Jun-2010
 Docs at http://saratoga-weather.org/scripts-TweetWX.php
 TweetWX-setup1.php  V1.00 - 11-Jun-2010 - Initial relase

 This utility function should be used once to get the OAuth request_token and request_token_secret
 for use in the next step of setup.
 
// Version 1.01 - 12-Jul-2013 - added debugging output if request fails for token
// Version 1.02 - 18-Jan-2014 - fixes for OAuth using SSL only
// Version 1.03 - 27-May-2014 - fixed Twitter link for $oauth_verifier to use https/SSL
 
*/
require 'TweetWX-globals.php';

if (phpversion() < 5) {
  echo 'Failure: This Script requires PHP version 5 or greater. You only have PHP version: ' . phpversion();
  exit;
}

?>
<h1>TweetWX Setup - part 1</h1>
<p>This step of the setup will obtain a request token and request token secret string for use by
TweetWX to connect to your twitter account.  It has two parts:
<ul>
  <li>Get the request token and request secret token for use by the app in the future (values shown below)</li>
  <li>You must click on the hotlink below to go to Twitter, authorize this app access to your account, and
  copy the verification number off the Twitter authorization screen for use in the $oauth_verifier variable</li>
</ul>

<?php
// Callback can either be 'oob' or a url
$callback='oob';

// Get the request token using HTTP GET and HMAC-SHA1 signature
$retarr = get_request_token(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET,
                            $callback, true, true, true);

if (! empty($retarr)) {
  list($info, $headers, $body, $body_parsed) = $retarr;
  if ($info['http_code'] == 200 && !empty($body)) {
    print "\n<p>Click on <a href=\"" .
        "https://api.twitter.com/oauth/authorize?" .
        rfc3986_decode($body) . "\" target=\"_blank\"><strong>this link</strong></a>
		to get the verification code for Step2 in setup</p>\n";
	  print "<pre>\n";
	  print "Copy the following two lines of PHP assignment statements into TweetWX-setup2.php to replace the two values in the script\n";
      print "\n#--copy code below-------------\n";
	  print '$request_token = \''.$body_parsed['oauth_token'].'\'; // oauth_token'."\n";
	  print '$request_token_secret = \''.$body_parsed['oauth_token_secret'].'\'; // oauth_token_secret'."\n";
	  print "#----end copy code above---------\n\n";
	  print "Then copy the verification number from the Twitter authorization script into the \$oauth_verifier variable\n";
	  print "</pre>\n";
?>	
<p>After you have updated TweetWX-setup2.php with the three values from above, and uploaded the updated copy,
then proceed to <a href="TweetWX-setup2.php"><strong>TweetWX-setup2.php</strong></a> to get the final OAuth
tokens to use with TweetWX and your twitter account.</p>
<?php
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
 * Get a request token.
 * @param string $consumer_key obtained when you registered your app
 * @param string $consumer_secret obtained when you registered your app
 * @param string $callback callback url can be the string 'oob'
 * @param bool $usePost use HTTP POST instead of GET
 * @param bool $useHmacSha1Sig use HMAC-SHA1 signature
 * @param bool $passOAuthInHeader pass OAuth credentials in HTTP header
 * @return array of response parameters or empty array on error
 */
function get_request_token($consumer_key, $consumer_secret, $callback, $usePost=false, $useHmacSha1Sig=true, $passOAuthInHeader=false)
{
  $retarr = array();  // return value
  $response = array();

  $url = 'https://api.twitter.com/oauth/request_token';
  $params['oauth_version'] = '1.0';
  $params['oauth_nonce'] = mt_rand();
  $params['oauth_timestamp'] = time();
  $params['oauth_consumer_key'] = $consumer_key;
  $params['oauth_callback'] = $callback;

  // compute signature and add it to the params list
  if ($useHmacSha1Sig) {
    $params['oauth_signature_method'] = 'HMAC-SHA1';
    $params['oauth_signature'] =
      oauth_compute_hmac_sig($usePost? 'POST' : 'GET', $url, $params,
                             $consumer_secret, null);
  } else {
    $params['oauth_signature_method'] = 'PLAINTEXT';
    $params['oauth_signature'] =
      oauth_compute_plaintext_sig($consumer_secret, null);
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
    logit("getreqtok:INFO:request_url:$request_url");
    logit("getreqtok:INFO:post_body:$query_parameter_string");
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    $response = do_post($request_url, $query_parameter_string, 443, $headers);
  } else {
    $request_url = $url . ($query_parameter_string ?
                           ('?' . $query_parameter_string) : '' );
    logit("getreqtok:INFO:request_url:$request_url");
    $response = do_get($request_url, 443, $headers);
  }

  // extract successful response
  if (! empty($response)) {
    list($info, $header, $body) = $response;
    $body_parsed = oauth_parse_str($body);
    if (! empty($body_parsed)) {
      logit("getreqtok:INFO:response_body_parsed:");
    }
    $retarr = $response;
    $retarr[] = $body_parsed;
  }

  return $retarr;
}
?>
