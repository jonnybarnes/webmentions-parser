<?php

use JonnyBarnes\WebmentionsParser\Parser;

class ParserTest extends PHPUnit_Framework_TestCase {

	public function testParser()
	{
		$html = 'text';
		$parser = new Parser();
		$this->assertEquals('text', $parser->parse($html));
	}
}