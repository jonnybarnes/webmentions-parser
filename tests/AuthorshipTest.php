<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Parser;

class AuthorshipTest extends PHPUnit_Framework_TestCase
{
    private $dir = __DIR__;

    public function testHEntryWithPAuthor()
    {
        $html = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-entry_with_p-author.html');
        $parser = new Parser();
        $auth = new Authorship();
        $microformats = $parser->getMicroformats($html, null);

        $expected = array(
            'type' => array(
                'h-card',
            ),
            'properties' => array(
                'name' => array(
                    'John Doe',
                ),
                'url' => array(
                    'http://example.com/johndoe/',
                ),
                'photo' => array(
                    'http://www.gravatar.com/avatar/fd876f8cd6a58277fc664d47ea10ad19.jpg?s=80&d=mm',
                ),
            ),
        );

        $this->assertEquals($expected, $auth->findAuthor($microformats));
    }

    public function testHEntryWithRelAuthorAndHCardWithUUrlPointingToRelAuthorHref()
    {
        $extrahtml = file_get_contents($this->dir . '/HTML/authorship-test-cases/no_h-card.html');
        $html = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-entry_with_rel-author_and_h-card_with_u-url_pointing_to_rel-author_href.html');
        $mock = new MockHandler(array(
            new Response(200, array(), $extrahtml),
        ));
        $handler = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handler));
        $auth = new Authorship($client);
        $parser = new Parser();
        $microformats = $parser->getMicroformats($html, null);
        $author = $auth->findAuthor($microformats);
        $this->assertFalse($author);
    }

    public function testHEntryWithRelAuthorPointingToHCardWithUUrlEqualToUUidEqualToSelf()
    {
        $extrahtml = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-card_with_u-url_equal_to_u-uid_equal_to_self.html');
        $html = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-entry_with_rel-author_pointing_to_h-card_with_u-url_equal_to_u-uid_equal_to_self.html');
        $mock = new MockHandler(array(
            new Response(200, array(), $extrahtml),
        ));
        $handler = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handler));
        $auth = new Authorship($client);
        $parser = new Parser();
        $microformats = $parser->getMicroformats($html, null);

        $expected = array(
            'type' => array(
                'h-card',
            ),
            'properties' => array(
                'name' => array(
                    'John Doe',
                ),
                'url' => array(
                    'h-card_with_u-url_equal_to_u-uid_equal_to_self.html ',
                ),
                'uid' => array(
                    'h-card_with_u-url_equal_to_u-uid_equal_to_self.html ',
                ),
                'photo' => array(
                    'http://www.gravatar.com/avatar/fd876f8cd6a58277fc664d47ea10ad19.jpg?s=80&d=mm',
                ),
            ),
        );

        $this->assertEquals($expected, $auth->findAuthor($microformats));
    }

    public function testHEntryWithRelAuthorPointingToHCardWithUUrlThatIsAlsoRelMe()
    {
        $extrahtml = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-card_with_u-url_that_is_also_rel-me.html');
        $html = file_get_contents($this->dir . '/HTML/authorship-test-cases/h-entry_with_rel-author_pointing_to_h-card_with_u-url_that_is_also_rel-me.html');
        $mock = new MockHandler(array(
            new Response(200, array(), $extrahtml),
        ));
        $handler = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handler));
        $auth = new Authorship($client);
        $parser = new Parser();
        $microformats = $parser->getMicroformats($html, null);

        $expected = array(
            'type' => array(
                'h-card',
            ),
            'properties' => array(
                'name' => array(
                    'John Doe',
                ),
                'url' => array(
                    'h-card_with_u-url_that_is_also_rel-me.html',
                ),
                'photo' => array(
                    'http://www.gravatar.com/avatar/fd876f8cd6a58277fc664d47ea10ad19.jpg?s=80&d=mm',
                ),
            ),
        );

        $this->assertEquals($expected, $auth->findAuthor($microformats));
    }

    public function testHFeed()
    {
        $html = file_get_contents($this->dir . '/HTML/h-feed.html');
        $parser = new Parser();
        $auth = new Authorship();
        $microformats = $parser->getMicroformats($html, null);

        $expected = array(
            'type' => array(
                'h-card',
            ),
            'properties' => array(
                'name' => array(
                    'Joe Bloggs',
                ),
            ),
        );

        $this->assertEquals($expected, $auth->findAuthor($microformats));
    }
}
