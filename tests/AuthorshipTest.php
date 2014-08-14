<?php

use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Parser;

use GuzzleHttp\Adapter\MockAdapter;
use GuzzleHttp\Adapter\TransactionInterface;
use GuzzleHttp\Message\Response;

class AuthorshipTest extends PHPUnit_Framework_TestCase {

	private $dir = __DIR__;

	public function testAlgo()
	{
		$mock = new MockAdapter(function() {
			$html = file_get_content($this->dir . '/HTML/authorship-test-cases/h-card_with_u-url_that_is_also_rel-me');

			return new Response(200, null, $html);
		})
		$html = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-entry_with_rel-author_pointing_to_h-card_with_u-url_that_is_also_rel-me.html');
		$parser = new Parser();
		$auth = new Authorship();
		$mf = $parser->getMicroformats($html);
		try {
			$auth->findAuthor($html);
		} catch(Exception $e) {
			var_dump($e);
			return fail;
		}

		return pass;
	}
}