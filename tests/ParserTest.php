<?php

use Jonnybarnes\WebmentionsParser\Parser;
use Jonnybarnes\WebmentionsParser\ParsingException;
use Jonnybarnes\WebmentionsParser\InvalidMentionException;

class ParserTest extends PHPUnit_Framework_TestCase {

	private $dir = __DIR__;

	/**
	 * Test determining mention types
	 */
	public function testMentionTypeReply()
	{
		$html = file_get_contents($this->dir . '/HTML/testMentionTypeReply.html');
		$parser = new Parser();
		$mf = $parser->getMicroformats($html);
		$expected = 'in-reply-to';
		$this->assertEquals($expected, $parser->getMentionType($mf));
	}

	public function testMentionTypeRepost()
	{
		$html = file_get_contents($this->dir . '/HTML/testMentionTypeRepost.html');
		$parser = new Parser();
		$mf = $parser->getMicroformats($html);
		$expected = 'repost-of';
		$this->assertEquals($expected, $parser->getMentionType($mf));
	}

	public function testMentionTypeLike()
	{
		$html = file_get_contents($this->dir . '/HTML/testMentionTypeLike.html');
		$parser = new Parser();
		$mf = $parser->getMicroformats($html);
		$expected = 'like-of';
		$this->assertEquals($expected, $parser->getMentionType($mf));
	}

	public function testInvalidMentionType()
	{
		$html = file_get_contents($this->dir . '/HTML/testInvalidMentionType.html');
		$parser = new Parser();
		$mf = $parser->getMicroformats($html);
		try {
			$type = $parser->getMentionType($mf);
		} catch(InvalidMentionException $e) {
			return;
		}
		$this->fail("An expected exception has not been thrown");
	}

	/**
	 * Test targeting
	 */
	public function testCheckReplyTo()
	{
		$html = file_get_contents($this->dir . '/HTML/testCheckReplyTo.html');
		$target = 'http://billy.com/notes/2014/06/22/4/';
		$parser = new Parser();
		$mf = $parser->getMicroformats($html);
		$this->assertTrue($parser->checkInReplyTo($mf, $target));
	}

	public function testCheckRepostOf()
	{
		$html = file_get_contents($this->dir . '/HTML/testCheckRepostOf.html');
		$target = 'http://billy.com/notes/2014/06/22/4/';
		$parser = new Parser();
		$mf = $parser->getMicroformats($html);
		$this->assertTrue($parser->checkRepostOf($mf, $target));
	}

	public function testCheckLikeOf()
	{
		$html = file_get_contents($this->dir . '/HTML/testCheckLikeOf.html');
		$target = 'http://billy.com/notes/2014/06/22/4/';
		$parser = new Parser();
		$mf = $parser->getMicroformats($html);
		$this->assertTrue($parser->CheckLikeOF($mf, $target));
	}

}