Custom-Twitter-PHP-API
======================

- @author	Robin Bonnes <http://robinbonnes.nl/>
- @version	1.2

Copyright (C) 2013 Robin Bonnes. All rights reserved.

Description
======================

Due to deprecating Twitter API v1.0, developers need to create oAuth authentication to retrieve tweets.
This script does exactly the same, without the OAuth hazzle, so its much easier to use.
Its only less advanced as Twitter API v1.1. 
It will retrieve tweets (and retweets) with their avatar, username and post date in JSON format.

Live Demo
======================

Check the demo here: http://www.robinbonnes.nl/projects/custom-twitter-api/demo.html

How to use
======================

You have two options:

1. Get tweets by username:

twitter_api.php?type=timeline&username=yourusername&count=5&retweets=true

 - username	=	Twitter username to retrieve tweets from.
 - count =	Number of tweets to retrieve. Default: 5.
 - retweets	=	Boolean to enable/disable displaying retweets. Default: true.

2. Get tweets by search keyword:

twitter_api.php?type=search&q=yourkeyword&count=5

 - q =	Search keyword to retrieve tweets from.
 - count =	Number of tweets to retrieve. Default: 5.

Output:

[{"username":"test","type":"tweet","avatar":"http://.../.png","date":"21 January 13","tweet":"Hello"},
{"username":"test2","type":"retweet","avatar":"http://.../.png","date":"23 January 13","tweet":"Hello"}]

License
======================
The **twitter_api.php** is licensed under a [Creative Commons Attribution License](http://creativecommons.org/licenses/by/3.0/us/): you may use it, but you must give attribution.

Changelog
======================

 - v1.0	- Release
 - v1.1 - Search function added
 - v1.2 - Several bugfixes

======================

Note: PHP extension CURL is required.
