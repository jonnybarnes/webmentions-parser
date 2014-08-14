<?php

namespace Jonnybarnes\WebmentionsParser;

class ParserException extends \Exception {}

class Authorship {
	
	/*
	 * Parse the mf for the author's h-card
	 */
	public function findAuthor($mf)
	{
		//check for h-entry
		$hEntry = false;
		foreach($mf['items'] as $item) {
			if($item['type'][0] == 'h-entry') {
				$hEntry = true;
			}
		}
		if($hEntry === false) {
			throw new ParsingException('No h-entry found');
		}

		return true;
	}

}