<?php

namespace Jonnybarnes\WebmentionsParser;

use Mf2;
use Jonnybarnes\WebmentionsParser\Exceptions\ParserException;
use Jonnybarnes\WebmentionsParser\Exceptions\InvalidMentionException;

class Parser
{

    /**
     * What we really want to parse are the microformats, but here's a starter method
     * to deal with the original HTML.
     */
    public function getMicroformats($html, $domain)
    {
        try {
            $microformats = \Mf2\parse($html, $domain);
        } catch (Exception $e) {
            //log $e maybe?
            throw new ParserException("php-mf2 failed to parse the HTML");
        }

        return $microformats;
    }

    /**
     * Return the type of mention or throw an error if undetermined
     */
    public function getMentionType(array $microformats)
    {
        if ($this->arrayKeyExistsRecursive('in-reply-to', $microformats)) {
            return 'in-reply-to';
        }
        if ($this->arrayKeyExistsRecursive('like-of', $microformats)) {
            return 'like-of';
        }
        if ($this->arrayKeyExistsRecursive('repost-of', $microformats)) {
            return 'repost-of';
        }

        //can't determine what type of mention it is, throw exception
        throw new InvalidMentionException();
    }

    /**
     * Check a mention is to the intended target
     */
    public function checkInReplyTo(array $microformats, $target)
    {
        $items = $microformats['items'];
        foreach ($items as $item) {
            $properties = $item['properties'];
            if (array_key_exists('in-reply-to', $properties)) {
                if (is_array($properties['in-reply-to'][0])) {
                    if ($properties['in-reply-to'][0]['properties']['url'][0] == $target) {
                        return true;
                    }
                } else {
                    foreach ($properties['in-reply-to'] as $url) {
                        if ($url == $target) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    public function checkLikeOf(array $microformats, $target)
    {
        $likeOf = (isset($microformats['items'][0]['properties']['like-of'])) ? $microformats['items'][0]['properties']['like-of'] : null;
        if ($likeOf) {
            foreach ($likeOf as $url) {
                if ($url == $target) {
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    public function checkRepostOf(array $microformats, $target)
    {
        $repostOf = (isset($microformats['items'][0]['properties']['repost-of'])) ? $microformats['items'][0]['properties']['repost-of'] : null;
        if ($repostOf) {
            foreach ($repostOf as $url) {
                if ($url == $target) {
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Our recursive array_key_exists function
     */
    private function arrayKeyExistsRecursive($needle, $haystack)
    {
        $result = array_key_exists($needle, $haystack);
        if ($result) {
            return $result;
        }
        foreach ($haystack as $v) {
            if (is_array($v)) {
                $result = $this->arrayKeyExistsRecursive($needle, $v);
            }
            if ($result) {
                return $result;
            }
        }
        return $result;
    }

    /**
     * Now we actually parse the mf2 for desired data
     */
    public function replyContent($microformats, $domain = null)
    {
        $replyHTML = (isset($microformats['items'][0]['properties']['content'][0]['html'])) ? $microformats['items'][0]['properties']['content'][0]['html'] : null;
        if ($replyHTML === null) {
            //if there is no actual reply content...
            throw new ParsingException('No reply content found');
        } else {
            //lets "clean" the HTML
            $replyHTML = trim($replyHTML);
        }

        $date = (isset($microformats['items'][0]['properties']['published'][0])) ? $microformats['items'][0]['properties']['published'][0] : null;
        if ($date === null) {
            //there is no date, just fluff with the current date
            $date = date('Y-m-d H:i:s \U\T\CO');
        }

        $authorship = new Authorship();
        try {
            $author = $authorship->findAuthor($microformats);
        } catch (AuthorshipParserException $e) {
            $author = null;
        }
        if ($author === null) {
            //we couldn't find actual authorship data, so fall back to domain
            if ($domain !== null) {
                $authorName = parse_url($domain)['host'];
                $authorUrl = 'http://' . parse_url($domain)['host'];
            } else {
                $authorName = null;
                $authorUrl = null;
            }
            $authorPhoto = null;
        } else {
            $authorName = $author['properties']['name'][0];
            $authorUrl = $author['properties']['url'][0];
            $authorPhoto = $author['properties']['photo'][0];
        }

        return array('name' => $authorName, 'url' => $authorUrl, 'photo' => $authorPhoto, 'reply' => $replyHTML, 'date' => $date);
    }

    public function likeContent(array $microformats, $domain = null)
    {
        $authorship = new Authorship();
        try {
            $author = $authorship->findAuthor($microformats);
        } catch (AuthorshipParserException $e) {
            $author = null;
        }
        if ($author === null) {
            //we couldn't find actual authorship data, so fall back to domain
            if ($domain !== null) {
                $authorName = parse_url($domain)['host'];
                $authorUrl = 'http://' . parse_url($domain)['host'];
            } else {
                $authorName = null;
                $authorUrl = null;
            }
            $authorPhoto = null;
        } else {
            $authorName = $author['properties']['name'][0];
            $authorUrl = $author['properties']['url'][0];
            $authorPhoto = $author['properties']['photo'][0];
        }

        return array('name' => $authorName, 'url' => $authorUrl, 'photo' => $authorPhoto);
    }

    public function repostContent(array $microformats, $domain = null)
    {
        $url = (isset($microformats['items'][0]['properties']['repost-of'][0])) ? $microformats['items'][0]['properties']['repost-of'][0] : null;

        $date = (isset($microformats['items'][0]['properties']['published'][0])) ? $microformats['items'][0]['properties']['published'][0] : null;
        if ($date === null) {
            //there is no date, just fluff with the current date
            $date = date('Y-m-d H:i:s \U\T\CO');
        }

        $authorship = new Authorship();
        try {
            $author = $authorship->findAuthor($microformats);
        } catch (AuthorshipParserException $e) {
            $author = null;
        }
        if ($author === null) {
            //we couldn't find actual authorship data, so fall back to domain
            if ($domain !== null) {
                $authorName = parse_url($domain)['host'];
                $authorUrl = 'http://' . parse_url($domain)['host'];
            } else {
                $authorName = null;
                $authorUrl = null;
            }
            $authorPhoto = null;
        } else {
            $authorName = $author['properties']['name'][0];
            $authorUrl = $author['properties']['url'][0];
            $authorPhoto = $author['properties']['photo'][0];
        }

        return array('name' => $authorName, 'url' => $authorUrl, 'photo' => $authorPhoto, 'repost' => $url, 'date' => $date);
    }
}
