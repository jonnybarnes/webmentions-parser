<?php

use JonnyBarnes\WebmentionsParser\Parser;

class ParserTest extends PHPUnit_Framework_TestCase {

	public function testParser()
	{
		$html = '<p class="h-card">Joe Bloggs</p>';
		$parser = new Parser();
		$mf = $parser->getMicrofromats($html);
		$expected = <<<'EOD'{
	"items": [{
		"type": ["h-card"],
		"properties": {
			"name": ["Joe Bloggs"]
		}
	}],
	"rels": {}
}
EOD;
		$this->assertEquals($expected, $parser->getMicroformats($html));
	}
	}
}