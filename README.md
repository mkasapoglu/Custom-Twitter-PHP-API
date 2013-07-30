Custom-Twitter-PHP-API
======================

@author	Robin Bonnes <http://robinbonnes.nl/>

@version	1.0

Copyright (C) 2013 Robin Bonnes. All rights reserved.

DESCRIPTION:
======================

Due to deprecating Twitter API v1.0, developers need to create oAuth authentication to retrieve tweets.
This script does exactly the same, without the OAuth hazzle, so its much easier to use.
Its only less advanced as Twitter API v1.1. 
It will retrieve tweets (and retweets) with their avatar, username and post date in JSON format.

LIVE DEMO:
======================

Check the demo here: http://www.robinbonnes.nl/projects/custom-twitter-api/demo.html

HOW TO USE:
======================

twitter_api.php?username=yourusername&count=2&retweets=true

 - username	=	Twitter username to retrieve tweets from.
 - count =	Number of tweets to retrieve. Default: 200.
 - retweets	=	Boolean to enable/disable retrieving retweets. Default: false.

OUTPUT:

[{"username":"test","type":"tweet","avatar":"http://.../.png","date":"21 January 13","tweet":"Hello"},
{"username":"test2","type":"retweet","avatar":"http://.../.png","date":"23 January 13","tweet":"Hello"}]

CHANGELOG:
======================

v1.0	- Release

======================

Note: PHP extension CURL is required.
   
   
