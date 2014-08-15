<?php

use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Parser;
use Jonnybarnes\WebmentionsPArser\AuthorException;

use GuzzleHttp\Adapter\MockAdapter;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream;

class AuthorshipTest extends PHPUnit_Framework_TestCase {

	private $dir = __DIR__;

		/*$mock = new MockAdapter(function() {
			$mockhtml = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-card_with_u-url_that_is_also_rel-me.html');
			$stream = Stream\create($mockhtml);

			return new Response(200, array(), $stream);
		});*/

	public function testHEntryWithPAuthor()
	{
		$html = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-entry_with_p-author.html');
		$parser = new Parser();
		$auth = new Authorship();
		$mf = $parser->getMicroformats($html);

		$expected = array(
			array(
				'type' => array(
					'h-card'
				),
				'properties' => array(
					'name' => array(
						'John Doe'
					),
					'url' => array(
						'http://example.com/johndoe/'
					),
					'photo' => array(
						'http://www.gravatar.com/avatar/fd876f8cd6a58277fc664d47ea10ad19.jpg?s=80&d=mm'
					)
				),
				'value' => 'http://www.gravatar.com/avatar/fd876f8cd6a58277fc664d47ea10ad19.jpg?s=80&d=mm
			John Doe'
			)
		);

		$this->assertEquals($expected, $auth->findAuthor($mf));
	}

	/**
	 * We need to adapt the algo to find the h-card also on the page,
	 * it currently fails to find an author
	 *
	 * @expectedException Jonnybarnes\WebmentionsParser\AuthorException
	 */
	public function testHEntryWithRelAuthorAndHCardWithUUrlPointingToRelAuthorHref()
	{
		$mock = new MockAdapter(function() {
			$mockhtml = file_get_contents($this->dir . '/HTML/authorship-test-cases/no_h-card.html');
			$stream = Stream\create($mockhtml);

			return new Response(200, array(), $stream);
		});
		$html = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-entry_with_rel-author_and_h-card_with_u-url_pointing_to_rel-author_href.html');
		$parser = new Parser();
		$auth = new Authorship();
		$auth->mockAdapter($mock);
		$mf = $parser->getMicroformats($html);

		$author = $auth->findAuthor($mf);
	}

	public function testHEntryWithRelAuthorPointingToHCardWithUUrlEqualToUUidEqualToSelf()
	{
		$mock = new MockAdapter(function() {
			$mockhtml = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-card_with_u-url_equal_to_u-uid_equal_to_self.html');
			$stream = Stream\create($mockhtml);

			return new Response(200, array(), $stream);
		});
		$html = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-entry_with_rel-author_pointing_to_h-card_with_u-url_equal_to_u-uid_equal_to_self.html');
		$parser = new Parser();
		$auth = new Authorship();
		$auth->mockAdapter($mock);
		$mf = $parser->getMicroformats($html);

		$expected = array(
			'type' => array(
				'h-card'
			),
			'properties' => array(
				'name' => array(
					'John Doe'
				),
				'url' => array(
					'h-card_with_u-url_equal_to_u-uid_equal_to_self.html'
				),
				'uid' => array(
					'h-card_with_u-url_equal_to_u-uid_equal_to_self.html'
				),
				'photo' => array(
					'http://www.gravatar.com/avatar/fd876f8cd6a58277fc664d47ea10ad19.jpg?s=80&d=mm'
				)
			)
		);

		$this->assertEquals($expected, $author = $auth->findAuthor($mf));
	}
}