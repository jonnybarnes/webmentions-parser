<?php

namespace Jonnybarnes\WebmentionsParser;

use Mf2;

class ParserException extends \Exception {}
class AuthorException extends \Exception {}

class Authorship {

	/*
	 * Set up the Guzzle Client dependency for when we may need it
	 */
	public function __construct()
	{
		$this->guzzle = new \GuzzleHttp\Client();
	}

	/*
	 * This methos is used by PHPUnit tests to replace the Guzzle Client
	 * with a new Client that has a mock adapter to return "dummy" content
	 */
	public function mockAdapter($adapter)
	{
		$this->guzzle = new \GuzzleHttp\Client(['adapter' => $adapter]);
	}
	
	/*
	 * Parse the mf for the author's h-card, assume a permalink for now
	 */
	public function findAuthor($mf, $permalink = true)
	{
		//check for h-entry's
		/* will currently only work with first h-entry
		TODO: work with multiple h-entry's
		*/
		
		//instantiate vars
		$hEntry = false;
		$author = false;
		$authorPage = false;
		
		for($i = 0; $i < count($mf['items']); $i++) {
			foreach($mf['items'][$i]['type'] as $type) {
				if($type == 'h-entry') {
					$hEntry = $mf['items'][$i];
				}
			}
		}
		if($hEntry === false) {
			throw new ParsingException('No h-entry found');
		}

		//parse the h-entry

		//if h-entry has an author property use that
		if(array_key_exists('author', $hEntry['properties'])) {
			$author = $hEntry['properties']['author'];
		}

		//otherwise look for parent h-feed, if that has author property use that

		//if an author property was found
		if($author !== false) {
			//if it has an h-card, use it, exit
			if(array_search('h-card', $author) !== false) {
				return $author;
			}

			//otherwise if `author` is a URL, let that be author-page
			if($author == url) {
				$authorPage = $author;
			} else {
				//otherwise use `author` property as author name, exit
				return $author;
			}
		}

		//if no author-page and h-entry is a permalink then look for rel-author link
		//and let that be author-page
		if($authorPage === false && $permalink == true) {
			if(array_key_exists('author', $mf['rels'])) {
				if(is_array($mf['rels']['author'])) {
					$authorPage = $mf['rels']['author'][0];
				} else {
					$authorPage = $mf['rels']['author'];
				}
			}
		}

		//if there is an author-page
		if($authorPage !== false) {
			//grab mf2 from author-page
			try {
				$parser = new Parser();
				$response = $this->guzzle->get($authorPage);
				$html = (string) $response->getBody();
			} catch(\GuzzleHttp\Exception\RequestException $e) {
				var_dump($e);
				throw new ParserException('Unable to get the Content from the authors page');
			}
			$authorMf2 = \Mf2\parse($html, $authorPage);
			
			//if page has 1+ h-card where url == uid == author-page then use first
			//such h-card, exit
			if(array_search('uid', $hEntry)) {
				foreach($authorMf2['items'] as $item) {
					if(array_search('h-card', $item['type']) !== false) {
						$urls = $item['properties']['url'];
						foreach($urls as $url) {
							if($url == $uid && $url == $authorPage) {
								return $item;
							}
						}
					}
				}
			}

			//else if page has 1+ h-card with url property which matches a rel-me
			//link on the page, use first such h-card, exit
			foreach($authorMf2['items'] as $item) {
				if(array_search('h-card', $item['type']) !== false
				  && array_key_exists('me', $authorMf2['rels'])) {
					$urls = $item['properties']['url'];
					$relMeLinks = $authorMf2['rels']['me'];
					foreach($urls as $url) {
						if(in_array($url, $relMeLinks)) {
							return $item;
						}
					}
				}
			}

			//if the h-entry page has 1+ h-card with url == author-page, use first
			//such h-card, exit
			foreach($authorMf2['items'] as $item) {
				if(array_search('h-card', $item['type']) !== false) {
					$urls = $item['properties']['url'];
					if(in_array($authorPage, $urls)) {
						return $item;
					}
				}
			}

		}
		//otherwise we can't determine the author just yet
		throw new AuthorException('Unable to determine author');

	}

}