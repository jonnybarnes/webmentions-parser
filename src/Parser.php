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

}