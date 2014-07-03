<?php

use JonnyBarnes\WebmentionsParser\Parser;

class ParserTest extends PHPUnit_Framework_TestCase {

	/**
	 * Test determining mention types
	 */
	public function testMentionTypeReply()
	{
		$html = '<!doctype html>
<html>
	<body class="h-entry">
		<div class="p-in-reply-to h-cite">
			<p>
				<a class="u-url" rel="in-reply-to" href="http://billy.com/notes/2014/06/22/4/">
				<time class="dt-published" datetime="2014-06-22T22:59:13-07:00">2014-06-22 22:59</time></a>
			</p>
		
			<p class="p-author h-card">
				<img class="u-photo" src="http://billy.com/images/billy.png" alt="" />		
				<a class="u-url p-name" href="http://billy.com/">Billy</a>
			</p>
		
			<div class="p-summary p-name e-content">
				<img alt="" class="u-photo" src="http://billy.com/notes/2014/06/22/4/files/photo.jpg" />
				Not bad...
			</div>
		</div>
				
		<div class="p-name entry-title p-summary summary e-content entry-content">
			<p><a class="auto-link h-x-username" href="https://twitter.com/billy">@billy</a> Looks great</p>
		</div>
	</body>
</html>';
		$parser = new Parser();
		$mf = $parser->getMicroformats($html);
		$expected = 'in-reply-to';
		$this->assertEquals($expected, $parser->getMentionType($mf));
	}

}