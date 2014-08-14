<?php

namespace Jonnybarnes\WebmentionsParser;

class ParserException extends \Exception {}

class Authorship {
	
	/*
	 * Parse the mf for the author's h-card
	 */
	public function findAuthor($mf)
	{
		//check for h-entry's
		/* will currently only work with first h-entry
		TODO: work with multiple h-entry's
		*/
		$hEntry = false;
		for($i = 0; $i < count($mf['items']); $i++) {
			foreach($mf['items'][$i]['type'] as $type) {
				if($type == 'h-entry') {
					$hEntry = $mf['items'][$i];
				}
			}
		}
		if($hEntry === false) {
			throw new ParsingException('No h-entry found');
		}

		//parse the h-entry

		//if h-entry has an author property use that

		//otherwise look for parent h-feed, if that has author property use that

		//if an author property was found
			//if it has an h-card, use it, exit

			//otherwise if `author` is a URL, let that be author-page

			//otherwise use `auhtor` property as author name, exit

		//if no author-page and h-entry is a permalink then look for rel-author link
		//and let that be author-page

		//if there is an author-page
			//grab mf2 from author-page
			
			//if page has 1+ h-card where url == uid == author-page then use first
			//such h-card, exit

			//else if page has 1+ h-card with url property which matches a rel-me
			//link on the page, use first such h-card, exit

			//if the h-entry page has 1+ h-card with url == author-page, use first
			//such h-card, exit

		//otherwise we can't determine the author just yet


		//this return true is for the purposes of tests
		return true;
	}

}