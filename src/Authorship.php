<?php

declare(strict_types=1);

namespace Jonnybarnes\WebmentionsParser;

use GuzzleHttp\Client;
use Jonnybarnes\WebmentionsParser\Exceptions\AuthorshipParserException;
use function Mf2\parse;

class Authorship
{
    /** @var Client */
    protected $guzzle;

    /** @var array */
    private $hEntry;

    /** @var array */
    private $hFeed;

    /** @var array */
    private $author;

    /** @var string */
    private $authorPage;

    /** @var array */
    private $authorInfo;

    /** @var array */
    private $authorMf2;

    /**
     * Set up the Guzzle Client dependency for when we may need it.
     *
     * @param Client|null $client
     */
    public function __construct(Client $client = null)
    {
        $this->guzzle = $client ?? new Client();

        $this->hEntry = null;
        $this->hFeed = null;
        $this->author = null;
        $this->authorPage = null;
        $this->authorInfo = null;
        $this->authorMf2 = null;
    }

    /**
     * Parse the mf for the author's h-card, assume a permalink for now.
     *
     * This method currently only works with the first h-entry.
     *
     * @todo work with multiple h-entry's
     *
     * @param array $mf The parsed microformats
     * @param bool $permalink
     * @return mixed
     * @throws AuthorshipParserException
     */
    public function findAuthor(array $mf, bool $permalink = true)
    {
        for ($i = 0; $i < count($mf['items']); $i++) {
            foreach ($mf['items'][$i]['type'] as $type) {
                if ($type === 'h-entry') {
                    $this->hEntry = $mf['items'][$i];
                } elseif ($type === 'h-feed') {
                    $this->hFeed = $mf['items'][$i];
                }
            }
        }

        if ($this->hEntry === null && $this->hFeed === null) {
            // We may have neither an h-entry or an h-feed in the parent items array
            throw new AuthorshipParserException();
        }

        // Parse the h-entry
        if ($this->hEntry !== null) {
            //if h-entry has an author property use that
            if (array_key_exists('author', $this->hEntry['properties'])) {
                $this->author = $this->hEntry['properties']['author'];
            }
        }

        // Otherwise look for parent h-feed, if that has author property use that
        if ($this->hFeed !== null) {
            foreach ($this->hFeed['children'] as $child) {
                if ($child['type'][0] === 'h-card') {
                    //we have a h-card on the page, use it
                    $this->author = $child;
                }
            }
        }

        // If an author property was found
        if ($this->author !== null) {
            // If it has an h-card, use it and exit
            if (is_array($this->author)) {
                if (array_search('h-card', $this->author) !== false) {
                    return $this->normalise($this->author);
                }

                // Flatten if single string entry
                if (count($this->author) === 1 && is_string($this->author[0])) {
                    $this->author = $this->author[0];
                }
            }

            // Otherwise if `author` is a URL, let that be author-page
            if (filter_var($this->author, FILTER_VALIDATE_URL)) {
                $this->authorPage = $this->author;
            } else {
                // Otherwise use `author` property as author name, exit
                return $this->normalise($this->author);
            }
        }

        // If no author-page and h-entry is a permalink then look for rel-author link
        // and let that be author-page
        if ($this->authorPage === null && $permalink === true) {
            if (array_key_exists('author', $mf['rels'])) {
                if (is_array($mf['rels']['author'])) {
                    // need to deal with this better
                    $this->authorPage = $mf['rels']['author'][0];
                } else {
                    $this->authorPage = $mf['rels']['author'];
                }
            }
        }

        // If there is an author-page
        if ($this->authorPage !== null) {
            // Grab mf2 from author-page
            try {
                $response = $this->guzzle->get($this->authorPage);
                $html = (string) $response->getBody();
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                throw new AuthorshipParserException('Unable to get the Content from the authors page');
            }

            $this->authorMf2 = parse($html, $this->authorPage);

            // If page has 1+ h-card where url == uid == author-page then use first
            // such h-card, exit
            if (array_search('uid', $this->hEntry)) {
                foreach ($this->authorMf2['items'] as $item) {
                    if (array_search('h-card', $item['type']) !== false) {
                        $urls = $item['properties']['url'];
                        foreach ($urls as $url) {
                            if ($url === $uid && $url === $this->authorPage) {
                                return $this->normalise($item);
                            }
                        }
                    }
                }
            }

            // Else if page has 1+ h-card with url property which matches a rel-me
            // link on the page, use first such h-card, exit
            foreach ($this->authorMf2['items'] as $item) {
                if (
                    array_search('h-card', $item['type']) !== false
                    && array_key_exists('me', $this->authorMf2['rels'])
                ) {
                    $urls = $item['properties']['url'];
                    $relMeLinks = $this->authorMf2['rels']['me'];
                    //in_array can take an array for its needle
                    foreach ($urls as $url) {
                        if (in_array($url, $relMeLinks)) {
                            return $this->normalise($item);
                        }
                    }
                }
            }

            // If the h-entry page has 1+ h-card with url == author-page, use first
            // such h-card, exit
            foreach ($this->authorMf2['items'] as $item) {
                if (array_search('h-card', $item['type']) !== false) {
                    $urls = $item['properties']['url'];
                    if (in_array($this->authorPage, $urls)) {
                        return $this->normalise($item);
                    }
                }
            }
        }

        // If we have got this far, we haven't been able to determine the author info
        // to return, so return false
        return false;
    }

    /**
     * Normalise the author info.
     *
     * @param array|string $author
     * @return array|string
     */
    protected function normalise($author)
    {
        // If the info si a string, just return that
        if (is_array($author) === false) {
            return $author;
        }

        if (array_key_exists(0, $author)) {
            $author = $author[0];
        }

        if (array_key_exists('value', $author)) {
            unset($author['value']);
        }

        if (array_key_exists('children', $author)) {
            unset($author['children']);
        }

        return $author;
    }
}
