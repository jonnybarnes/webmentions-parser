<?php

use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Parser;
use Jonnybarnes\WebmentionsPArser\AuthorException;

use GuzzleHttp\Adapter\MockAdapter;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream;

class AuthorshipTest extends PHPUnit_Framework_TestCase {

	private $dir = __DIR__;

	public function testAlgo()
	{
		$mock = new MockAdapter(function() {
			$mockhtml = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-card_with_u-url_that_is_also_rel-me.html');
			$stream = Stream\create($mockhtml);

			return new Response(200, array(), $stream);
		});
		$html = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-entry_with_rel-author_pointing_to_h-card_with_u-url_that_is_also_rel-me.html');
		$parser = new Parser();
		$auth = new Authorship();
		$auth->mockAdapter($mock);
		$mf = $parser->getMicroformats($html);
		try {
			$author = $auth->findAuthor($mf);
		} catch (AuthorException $e) {
			$author = false;
		}

		$this->assertFalse($author);
	}
}