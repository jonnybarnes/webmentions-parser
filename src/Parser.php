<?php

namespace JonnyBarnes\WebmentionsParser;

use Mf2;

class ParsingException extends \Exception {}
class InvalidMentionException extends \Exception {}

class Parser {

	/**
	 * What we really want to parse are the microformats, but here's a starter method
	 * to deal with the original HTML.
	 */
	public function getMicroformats($html)
	{
		try {
			$mf = \Mf2\parse($html);
		} catch(Exception $e) {
			//log $e maybe?
			throw new ParsingException("php-mf2 failed to parse the HTML");
		}

		return $mf;
	}

	/**
	 * Return the type of mention or throw an error if undetermined
	 */
	public function getMentionType(array $mf)
	{
		if($this->array_key_exists_r('in-reply-to', $mf)) {
			return 'in-reply-to';
		}
		if($this->array_key_exists_r('like-of', $mf)) {
			return 'like-of';
		}
		if($this->array_key_exists_r('repost-of', $mf)) {
			return 'repost-of';
		}

		//can't determine what type of mention it is, throw exception
		throw new InvalidMentionException();
	}

	/**
	 * Check a mention is to the intended target
	 */
	public function checkInReplyTo(array $mf, $target)
	{
		$items = $mf['items'];
		foreach($items as $item) {
			$properties = $item['properties'];
			if(array_key_exists('in-reply-to', $properties)) {
				if(is_array($properties['in-reply-to'][0])) {
					if($properties['in-reply-to'][0]['properties']['url'][0] == $target) {
						return true;
					}
				} else {
					foreach($properties['in-reply-to'] as $url) {
						if($url == $target) {
							return true;
						}
					}
				}
			}
		}

		return false;
	}

	public function checkLikeOf(array $mf, $target)
	{
		$likeOf = (isset($mf['items'][0]['properties']['like-of'])) ? $mf['items'][0]['properties']['like-of'] : null;
		if($likeOf) {
			foreach($likeOf as $url) {
				if($url == $target) {
					return true;
				}
			}
		} else {
			return false;
		}
	}

	public function checkRepostOf(array $mf, $target)
	{
		$repostOf = (isset($mf['items'][0]['properties']['repost-of'])) ? $mf['items'][0]['properties']['repost-of'] : null;
		if($repostOf) {
			foreach($repostOf as $url) {
				if($url == $target) {
					return true;
				}
			}
		} else {
			return false;
		}
	}

	/**
	 * Our recursive array_key_exists function
	 */
	private function array_key_exists_r($needle, $haystack)
	{
		$result = array_key_exists($needle, $haystack);
		if($result) return $result;
		foreach($haystack as $v) {
			if(is_array($v)) {
				$result = $this->array_key_exists_r($needle, $v);
			}
			if($result) return $result;
		}
		return $result;
	}

	/**
	 * Now we actually parse the mf2 for desired data
	 */
	public function replyContent($mf)
	{
		$authorName = (isset($mf['items'][0]['properties']['author'][0]['properties']['name'][0])) ? $mf['items'][0]['properties']['author'][0]['properties']['name'][0] : null;
		$authorUrl = (isset($mf['items'][0]['properties']['author'][0]['properties']['url'][0])) ? $mf['items'][0]['properties']['author'][0]['properties']['url'][0] : null;
		$authorPhoto = (isset($mf['items'][0]['properties']['author'][0]['properties']['photo'][0])) ? $mf['items'][0]['properties']['author'][0]['properties']['photo'][0] : null;
		$replyHTML = (isset($mf['items'][0]['properties']['content'][0]['html'])) ? $mf['items'][0]['properties']['content'][0]['html'] : null;
		$date = (isset($mf['items'][0]['properties']['published'][0])) ? $mf['items'][0]['properties']['published'][0] : null;
		
		if((isset($authorName)) && (isset($authorUrl)) && (isset($authorPhoto)) && (isset($replyHTML)) && (isset($date))) {
			$data = array('name' => $authorName, 'url' => $authorUrl, 'photo' => $authorPhoto, 'reply' => $replyHTML, 'date' => $date);

			return $data;
		} else {
			throw new ParsingException('Some data missing from parsed source');
		}
	}

	public function likeContent($mf)
	{
		$authorName = (isset($mf['items'][0]['properties']['author'][0]['properties']['name'][0])) ? $mf['items'][0]['properties']['author'][0]['properties']['name'][0] : null;
		$authorUrl = (isset($mf['items'][0]['properties']['author'][0]['properties']['url'][0])) ? $mf['items'][0]['properties']['author'][0]['properties']['url'][0] : null;
		$authorPhoto = (isset($mf['items'][0]['properties']['author'][0]['properties']['photo'][0])) ? $mf['items'][0]['properties']['author'][0]['properties']['photo'][0] : null;
		if((isset($authorName)) && (isset($authorUrl)) && (isset($authorPhoto))) {
			$data = array('name' => $authorName, 'url' => $authorUrl, 'photo' => $authorPhoto);

			return $data;
		} else {
			throw new ParsingException('Some data missing from parsed source');
		}
	}

	public function repostContent($mf)
	{
		$authorName = (isset($mf['items'][0]['properties']['author'][0]['properties']['name'][0])) ? $mf['items'][0]['properties']['author'][0]['properties']['name'][0] : null;
		$authorUrl = (isset($mf['items'][0]['properties']['author'][0]['properties']['url'][0])) ? $mf['items'][0]['properties']['author'][0]['properties']['url'][0] : null;
		$authorPhoto = (isset($mf['items'][0]['properties']['author'][0]['properties']['photo'][0])) ? $mf['items'][0]['properties']['author'][0]['properties']['photo'][0] : null;
		$url = (isset($mf['items'][0]['properties']['url'][0])) ? $mf['items'][0]['properties']['url'][0] : null;
		$date = (isset($mf['items'][0]['properties']['published'][0])) ? $mf['items'][0]['properties']['published'][0] : null;
		if((isset($authorName)) && (isset($authorUrl)) && (isset($authorPhoto)) && (isset($url)) && (isset($date))) {
			$data = array('name' => $authorName, 'url' => $authorUrl, 'photo' => $authorPhoto, 'repost' => $url, 'date' => $date);

			return $data;
		} else {
			throw new ParsingException('Some data missing from parsed source');
		}
	}

}