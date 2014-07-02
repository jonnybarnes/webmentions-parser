<?php

namespace JonnyBarnes\WebmentionsParser;

use Mf2;

class ParsingException {}

class Parser {

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

}