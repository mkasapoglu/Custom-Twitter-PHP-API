<?php

	/* --------------------------------------------------------------------------------------------------- 
	 * Custom Twitter API
	 *
	 * @author	Robin Bonnes <http://robinbonnes.nl/>
	 * @version	1.5
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
	 * Get tweets by username:
	 * 
	 * twitter_api.php?type=timeline&username=yourusername&count=5&retweets=true
	 * 
	 * - username	=	Twitter username to retrieve tweets from.
	 * - count 		=	Number of tweets to retrieve. Default: 5.
	 * - retweets	=	Boolean to enable/disable displaying retweets. Default: true.
	 * 
	 * Get tweets by search keyword:
	 * 
	 * twitter_api.php?type=search&q=yourkeyword&count=5
	 * 
	 * - q 		=	Search keyword to retrieve tweets from.
	 * - count 	=	Number of tweets to retrieve. Default: 5.
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
	 * v1.2 - Several bugfixes
	 * v1.3 - Added hashtag search support, little bit optimized and several bugs fixed
	 * v1.4 - Special characters fix
	 * v1.5 - New layout compatible
	 *
	 * Note: PHP extension CURL is required.
	 * --------------------------------------------------------------------------------------------------- */

	/*
	 * Allow Cross Domain
	 */
	header('Access-Control-Allow-Origin: *');
	 
	/*
	 * Method
	 */
	if(isset($_GET["type"]) && ($_GET["type"] == 'timeline' || $_GET["type"] == 'search'))
	{
		$type = $_GET["type"];
	}
	else
	{
		die("No api method specified!");
	}
	
	if($type == 'timeline')
	{
		/*
		 * Twitter Username
		 */
		if(isset($_GET["username"]) && !empty($_GET["username"]))
		{
			$name = $_GET["username"];
		}
		else
		{
			die("No twitter username specified!");
		}
		
		/*
		 * Boolean to retrieve retweets or not.
		 */
		if(isset($_GET["retweets"]) && !empty($_GET["retweets"]))
		{
			if($_GET["retweets"] == "0" || $_GET["retweets"] == "false")
			{
				$retweets = false;
			}
			else
			{
				$retweets = true;
			}
		}
		else
		{
			$retweets = true;
		}
	}
	else
	{
		/*
		 * Search Keyword
		 */
		if(isset($_GET["q"]) && !empty($_GET["q"]))
		{
			$keyword = urlencode($_GET["q"]);
		}
		else
		{
			die("No search keyword specified!");
		}
	}
	
	/*
	 * Number of tweets to retrieve. (max is 200)
	 */
	if(isset($_GET["count"]) && !empty($_GET["count"]))
	{
		if(is_numeric($_GET["count"]))
		{
			$count = (int) $_GET["count"];
		}
		else
		{
			$count = 5;
		}
	}
	else
	{
		$count = 5;
	}
		
	/*
	 * Get the tweets using CURL.
	 */
	if($type == 'timeline')
	{
		$url = 'https://twitter.com/i/profiles/show/' . $name . '/timeline?count=' . $count;
	}
	else 
	{
		$url = 'https://twitter.com/i/search/timeline?q=' . $keyword . '&count=' . $count;
	}
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_REFERER, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($curl);
	curl_close($curl);
	
	/*
	 * Decode JSON Encoded string to DOM
	 */
	 
	if(empty($result))
	{
		die("Can't fetch data from Twitter server.");
	}
	
	$decoded	=	json_decode($result, true);
	$decoded	=	$decoded['items_html'];
	$decoded	=	utf8_decode($decoded);
	$decoded	=	trim($decoded);

	if(empty($decoded) || !$decoded)
	{
		if($type == 'timeline')
		{
			die("Username doesn't exist or doesn't have tweets yet.");
		}
		else
		{
			die("No results found for keyword: " . $keyword . ".");
		}
	}
	
	$domdoc		=	new DOMDocument();
	$domdoc		->	loadHTML($decoded);
	
	/*
	 * Export tweets to JSON.
	 */
	$data		=	"[";								// Start JSON string
	$finder		=	new DomXPath($domdoc);				// Find tweets in DOMDocument
	$tweets		=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' content ')]"); // Find query
	if($tweets->item(0) == null)
	{
		$tweets		=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' Grid ')]"); // Find query
	}
	$first		=	true;								// Boolean checking if its the first element
	
	for($i = 0; $i < $count; $i++)
	{
		$skip = false;									// Boolean to check if item has to be skipped
		$tweet = $tweets->item($i);						// Get tweetdata
		
		// Extract tweet
		$newdomdoc 	=	new DomDocument;
		$newdomdoc	->	loadHTML("<html></html>");
		$newdomdoc	->	documentElement->appendChild($newdomdoc->importNode($tweet,true));
		$finder		=	new DomXpath($newdomdoc);

		// Check if retweets should be in result
		if($type == 'timeline' && $retweets == false)
		{
			$find	=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'js-retweet-text')]");
			
			// If its an retweet, skip it
			if(isset($find->item(0)->nodeValue))
			{
				$skip = true;
			}
		}
		
		if(!$skip)
		{
			// Start Element
			if(!$first) {
				$data .= ",";
			}

			$first = false;
			$data .= "{";
						
			// Extract username
			$find	=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'fullname')]");
			$data	.=	'"username":"' . htmlspecialchars($find->item(0)->nodeValue, ENT_QUOTES) . '",';
			
			// Determine Type
			$find	=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'js-retweet-text')]");
			if(isset($find->item(0)->nodeValue)) {
				$data	.=	'"type":"retweet",';
			} else {
				$data	.=	'"type":"tweet",';
			}

			// Extract avatar
			$find	=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'avatar')]");
			$data	.=	'"avatar":"' . htmlspecialchars($find->item(0)->getAttribute('src'), ENT_QUOTES) . '",';
			
			// Extract date
			$find	=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'js-short-timestamp')]");
			$data	.=	'"date":"' . htmlspecialchars($find->item(0)->nodeValue, ENT_QUOTES) . '",';

			// Extract tweet
			$find	=	$finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'js-tweet-text')]");
			$fixed_tweet = fix_tweet($newdomdoc->saveXML($find->item(0)));
			$data	.=	'"tweet":"' . $fixed_tweet . '"';
			
			// End Element
			$data .= "}";
		
		}
	}
	
	$data .= "]";							// End JSON string
	$data = str_replace("\r", "", $data);	// Filter linebreaks
	$data = str_replace("\n", "", $data);	// Filter linebreaks
	//header('Content-Type: text/html;charset=utf-8');
	echo $data;								// Output
	
	/*
	 * Helper Functions
	 */
	 
	/* Brings hastag and URL support */
	function fix_tweet($temptweet)
	{
		$stripped_elements = array();
		$simplexml = simplexml_load_string('<root>'. str_replace('"',"'", $temptweet) .'</root>', 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOXMLDECL);

		if($simplexml)
		{
			// Elements in tweet
			foreach($simplexml->xpath('descendant::*[@*]') as $tag)
			{
				// Attributes in elements
				foreach($tag->attributes() as $name => $value)
				{
					if($name != "href")
					{
						// Strip attribute
						$tag->attributes()->$name = '';
						$stripped_elements[$name] = '/ '. $name .'=""/';
					}
					else
					{
						// Fix link
						$first_char = substr($tag->attributes()->$name, 0, 1);
						if($first_char == "/")
						{
							$temp_val = str_replace("#", "%23", $value);
							$tag->attributes()->$name = "https://twitter.com" . $temp_val;
						}
					}
				}
			}
			
			return str_replace('"',"'", strip_tags(preg_replace($stripped_elements, array(''), $simplexml->asXML()), '<a>'));
		}
		
		return "";
	}
?>
