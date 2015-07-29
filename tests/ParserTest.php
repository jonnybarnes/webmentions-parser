<?php

use Jonnybarnes\WebmentionsParser\Exceptions\InvalidMentionException;
use Jonnybarnes\WebmentionsParser\Parser;

class ParserTest extends PHPUnit_Framework_TestCase
{
    private $dir = __DIR__;

    /**
     * Test determining mention types.
     */
    public function testMentionTypeReply()
    {
        $html = file_get_contents($this->dir . '/HTML/testMentionTypeReply.html');
        $parser = new Parser();
        $microformats = $parser->getMicroformats($html, null);
        $expected = 'in-reply-to';
        $this->assertEquals($expected, $parser->getMentionType($microformats));
    }

    public function testMentionTypeRepost()
    {
        $html = file_get_contents($this->dir . '/HTML/testMentionTypeRepost.html');
        $parser = new Parser();
        $microformats = $parser->getMicroformats($html, null);
        $expected = 'repost-of';
        $this->assertEquals($expected, $parser->getMentionType($microformats));
    }

    public function testMentionTypeLike()
    {
        $html = file_get_contents($this->dir . '/HTML/testMentionTypeLike.html');
        $parser = new Parser();
        $microformats = $parser->getMicroformats($html, null);
        $expected = 'like-of';
        $this->assertEquals($expected, $parser->getMentionType($microformats));
    }

    public function testInvalidMentionType()
    {
        $html = file_get_contents($this->dir . '/HTML/testInvalidMentionType.html');
        $parser = new Parser();
        $microformats = $parser->getMicroformats($html, null);
        try {
            $type = $parser->getMentionType($microformats);
        } catch (InvalidMentionException $e) {
            return;
        }
        $this->fail('An expected exception has not been thrown');
    }

    /**
     * Test targeting.
     */
    public function testCheckReplyTo()
    {
        $html = file_get_contents($this->dir . '/HTML/testCheckReplyTo.html');
        $target = 'http://billy.com/notes/2014/06/22/4/';
        $parser = new Parser();
        $microformats = $parser->getMicroformats($html, null);
        $this->assertTrue($parser->checkInReplyTo($microformats, $target));
    }

    public function testCheckRepostOf()
    {
        $html = file_get_contents($this->dir . '/HTML/testCheckRepostOf.html');
        $target = 'http://billy.com/notes/2014/06/22/4/';
        $parser = new Parser();
        $microformats = $parser->getMicroformats($html, null);
        $this->assertTrue($parser->checkRepostOf($microformats, $target));
    }

    public function testCheckLikeOf()
    {
        $html = file_get_contents($this->dir . '/HTML/testCheckLikeOf.html');
        $target = 'http://billy.com/notes/2014/06/22/4/';
        $parser = new Parser();
        $microformats = $parser->getMicroformats($html, null);
        $this->assertTrue($parser->CheckLikeOF($microformats, $target));
    }

    public function testReplyContent()
    {
        $html = file_get_contents($this->dir . '/HTML/testReplyContent.html');
        $parser = new Parser();
        $microformats = $parser->getMicroformats($html, null);
        $expected = array(
            'name'  => 'Joe Bloggs',
            'url'   => 'http://joebloggs.com/',
            'photo' => 'http://joebloggs.com/photo.png',
            'reply' => '<p><a class="auto-link h-x-username" href="https://twitter.com/billy">@billy</a> Looks great</p> - <time class="dt-published" datetime="2014-06-23T14:15:16+0100">2014-06-23 14:15</time>',
            'date'  => '2014-06-23T14:15:16+0100',
        );
        $this->assertEquals($expected, $parser->replyContent($microformats));
    }

    public function testRepostContent()
    {
        $html = file_get_contents($this->dir . '/HTML/testRepostContent.html');
        $parser = new Parser();
        $microformats = $parser->getMicroformats($html, null);
        $expected = array(
            'name'   => 'Joe Bloggs',
            'url'    => 'http://joebloggs.com/',
            'photo'  => 'http://joebloggs.com/photo.png',
            'repost' => 'http://billy.com/notes/2014/06/22/4/',
            'date'   => '2014-06-24T12:13:14+0000',
        );
        $this->assertEquals($expected, $parser->repostContent($microformats));
    }

    public function testLikeContent()
    {
        $html = file_get_contents($this->dir . '/HTML/testLikeContent.html');
        $parser = new Parser();
        $microformats = $parser->getMicroformats($html, null);
        $expected = array(
            'name'  => 'Joe Bloggs',
            'url'   => 'http://joebloggs.com/',
            'photo' => 'http://joebloggs.com/photo.png',
        );
        $this->assertEquals($expected, $parser->likeContent($microformats));
    }
}
