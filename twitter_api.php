<?php

	/* --------------------------------------------------------------------------------------------------- 
	 * Custom Twitter API
	 *
	 * @author	Robin Bonnes <http://robinbonnes.nl/>
	 * @version	1.1
	 *
	 * Copyright (C) 2013 Robin Bonnes. All rights reserved.
	 * 
	 * DESCRIPTION:
	 * 
	 * Due to deprecating Twitter API v1.0, developers need to create oAuth authentication to retrieve tweets.
	 * This script does exactly the same, without the OAuth hazzle, so its much easier to use.
	 * Its only less advanced as Twitter API v1.1. 
	 * It will retrieve tweets (and retweets) with their avatar, username and post date in JSON format.
	 * 
	 * HOW TO USE:
	 * 
	 * twitter_api.php?username=yourusername&count=10&retweets=false
	 *
	 * username	=	Twitter username to retrieve tweets from.
	 * count 	=	Number of tweets to retrieve. Default: 200.
	 * retweets	=	Boolean to enable/disable retrieving retweets. Default: false.
	 *
	 * OUTPUT:
	 * 
	 * [{"username":"test","type":"tweet","avatar":"http://.../.png","date":"21 January 13","tweet":"Hello"},
	 *  {"username":"test2","type":"retweet","avatar":"http://.../.png","date":"23 January 13","tweet":"Hello"}]
	 *
	 * CHANGELOG:
	 *
	 * v1.0	- Release
	 * v1.1 - Search function added
	 *
	 * Note: PHP extension CURL is required.
	 * --------------------------------------------------------------------------------------------------- */

	/*
	 * Method
	 */
	if(isset($_GET["type"]) && ($_GET["type"] == 'timeline' || $_GET["type"] == 'search'))
	{
		$type = $_GET["type"];
	}
	else
	{
		echo "No api method specified!";
		die();
	}
	
	if($type == 'timeline')
	{
		/*
		 * Twitter Username
		 */
		if(isset($_GET["username"]) && $_GET["username"] != '')
		{
			$name = $_GET["username"];
		}
		else
		{
			echo "No twitter username specified!";
			die();
		}
		
		/*
		 * Boolean to retrieve retweets or not.
		 */
		if(isset($_GET["retweets"]) && $_GET["retweets"] != '')
		{
			if($_GET["retweets"] == "1" || $_GET["retweets"] == "true") {
				$retweets = true;
			} else {
				$retweets = false;
			}
		}
		else
		{
			$retweets = false;
		}
	}
	else
	{
		/*
		 * Search Keyword
		 */
		if(isset($_GET["q"]) && $_GET["q"] != '')
		{
			$keyword = $_GET["q"];
		}
		else
		{
			echo "No search keyword specified!";
			die();
		}
	}
	
	
	/*
	 * Number of tweets to retrieve. (max is 200)
	 */
	if(isset($_GET["count"]) && $_GET["count"] != '')
	{
		if(is_numeric($_GET["count"])) {
			$count = (int) $_GET["count"];
		} else {
			$count = 200;
		}
	}
	else
	{
		$count = 200;
	}
	
	/*
	 * Get the tweets using CURL.
	 */
	
	if($type == 'timeline')
	{
		$url = 'https://twitter.com/i/profiles/show/' . $name . '/timeline/?count=' . $count;
	}
	else 
	{
		$url = 'https://twitter.com/i/search/timeline?type=relevance&q=' . $keyword . '&count=' . $count;
	}
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_HEADER, false);
	//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_REFERER, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($curl);
	curl_close($curl);
	
	/*
	 * Decode JSON Encoded string to DOM
	 */
	$decoded	=	json_decode($result, true);
	$decoded	=	$decoded['items_html'];
	$decoded	=	unicode_decode($decoded);
	$decoded	=	urldecode($decoded);
	
	if($decoded == '') {
		echo "Username doesn't exist!";
		die();
	}
	
	$domdoc		=	new DOMDocument();
	$domdoc		->	loadHTML($decoded);
	
	/*
	 * Export tweets to JSON.
	 */
	$data		=	"[";						// String containing the data to respond
	$classname	=	"content";					// Class containing the data
	$finder		=	new DomXPath($domdoc);
	$tweets		=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
	$first		=	true;						// Boolean checking if its the first element
	
	foreach ($tweets as $tweet) {
	
		// Start Element
		if(!$first) {
			$data .= ",";
		}
		$first = false;
		$data .= "{";
		
		// Create finder
		$newdomdoc 	=	new DomDocument;
		$newdomdoc	->	loadHTML("<html></html>");
		$newdomdoc	->	documentElement->appendChild($newdomdoc->importNode($tweet,true));
		$finder		=	new DomXpath($newdomdoc);
		
		// Extract username
		$find	=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'fullname')]");
		$data	.=	'"username":"' . htmlspecialchars($find->item(0)->nodeValue) . '",';
		
		// Determine Type
		$find	=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'js-retweet-text')]");
		if(isset($find->item(0)->nodeValue)) {
			$data	.=	'"type":"retweet",';
		} else {
			$data	.=	'"type":"tweet",';
		}

		// Extract avatar
		$find	=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'avatar')]");
		$data	.=	'"avatar":"' . htmlspecialchars($find->item(0)->getAttribute('src')) . '",';
		
		// Extract date
		$find	=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'js-short-timestamp')]");
		$data	.=	'"date":"' . htmlspecialchars($find->item(0)->nodeValue) . '",';

		// Extract tweet
		$find	=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'js-tweet-text')]");
		$data	.=	'"tweet":"' . htmlspecialchars($find->item(0)->nodeValue) . '"';
		
		// End Element
		$data .= "}";
		
	}
	$data .= "]";
	echo $data;								// Output
	
	/*
	 * Helper Functions
	 */
	function replace_unicode_escape_sequence($match) {
		return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
	}
	
	function unicode_decode($str) {
		return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $str);
	}
?>
