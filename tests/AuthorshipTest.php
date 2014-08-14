<?php

use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Parser;

class AuthorshipTest extends PHPUnit_Framework_TestCase {

	private $dir = __DIR__;

	public function testAlgo()
	{
		$html = file_get_contents($this->dir . '/HTML/testAuthorshipAlgo.html');
		$parser = new Parser();
		$auth = new Authorship();
		$mf = $parser->getMicroformats($html);
		$this->assertEquals(true, $auth->findAuthor($mf));
	}
}