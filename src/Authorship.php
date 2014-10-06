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
	 * This method is used by PHPUnit tests to replace the Guzzle Client
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
		$this->permalink = $permalink;
		$this->mf = $mf;
		$this->hEntry = false;
		$this->hFeed = false;
		$this->author = null;
		$this->authorPage = null;
		$this->authorInfo = null;
		
		for($i = 0; $i < count($this->mf['items']); $i++) {
			foreach($this->mf['items'][$i]['type'] as $type) {
				if($type == 'h-entry') {
					$this->hEntry = $this->mf['items'][$i];
				} elseif ($type == 'h-feed') {
					$this->hFeed = $this->mf['items'][$i];
				}
			}
		}

		if($this->hEntry === false && $this->hFeed === false) {
			//we may neither an h-entry or an h-feed in the parent items array
			throw new ParsingException('No h-entry found');
		}

		//parse the h-entry
		if($this->hEntry !== false) {

			//if h-entry has an author property use that
			if(array_key_exists('author', $this->hEntry['properties'])) {
				$this->author = $this->hEntry['properties']['author'];
			}
		}

		//otherwise look for parent h-feed, if that has author property use that
		if($this->hFeed !== false) {
			foreach($this->hFeed['children'] as $child) {
				if($child['type'][0] == 'h-card') {
					//we have a h-card on the page, use it
					$this->author = $child['properties'];
				}
			}
		}

		//if an author property was found
		if($this->author !== false) {
			//if it has an h-card, use it, exit
			if(array_search('h-card', $this->author) !== false) {
				$this->authorInfo = $this->author;
			}

			//otherwise if `author` is a URL, let that be author-page
			if(filter_var($this->author, FILTER_VALIDATE_URL)) {
				$this->authorPage = $this->author;
			} else {
				//otherwise use `author` property as author name, exit
				$this->authorInfo = $this->author;
			}
		}

		//if no author-page and h-entry is a permalink then look for rel-author link
		//and let that be author-page
		if($this->authorPage === false && $this->permalink == true) {
			if(array_key_exists('author', $this->mf['rels'])) {
				if(is_array($this->mf['rels']['author'])) {
					//need to deal with this better
					$this->authorPage = $this->mf['rels']['author'][0];
				} else {
					$this->authorPage = $this->mf['rels']['author'];
				}
			}
		}

		//if there is an author-page
		if($this->authorPage !== null) {
			//grab mf2 from author-page
			try {
				$this->parser = new Parser();
				$this->response = $this->guzzle->get($this->authorPage);
				$this->html = (string) $this->response->getBody();
			} catch(\GuzzleHttp\Exception\RequestException $e) {
				//var_dump($e);
				throw new ParserException('Unable to get the Content from the authors page');
			}
			$this->authorMf2 = \Mf2\parse($this->html, $this->authorPage);
			
			//if page has 1+ h-card where url == uid == author-page then use first
			//such h-card, exit
			if(array_search('uid', $this->hEntry)) {
				foreach($this->authorMf2['items'] as $item) {
					if(array_search('h-card', $item['type']) !== false) {
						$urls = $item['properties']['url'];
						foreach($urls as $url) {
							if($url == $uid && $url == $authorPage) {
								$this->authorInfo = $item;
							}
						}
					}
				}
			}

			//else if page has 1+ h-card with url property which matches a rel-me
			//link on the page, use first such h-card, exit
			foreach($this->authorMf2['items'] as $item) {
				if(array_search('h-card', $item['type']) !== false
				  && array_key_exists('me', $this->authorMf2['rels'])) {
					$urls = $item['properties']['url'];
					$relMeLinks = $this->authorMf2['rels']['me'];
					//in_array can take an arry for its needle
					if(in_array($urls, $relMeLinks)) {
						$this->authorInfo = $item;
					}
				}
			}

			//if the h-entry page has 1+ h-card with url == author-page, use first
			//such h-card, exit
			foreach($this->authorMf2['items'] as $item) {
				if(array_search('h-card', $item['type']) !== false) {
					$urls = $item['properties']['url'];
					if(in_array($this->authorPage, $urls)) {
						$this->authorInfo = $item;
					}
				}
			}

		}

		if($this->authorInfo) {
			return $this->authorInfo;
		} else {
			return false;
		}

	}

}