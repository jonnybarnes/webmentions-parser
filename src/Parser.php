<?php

declare(strict_types=1);

namespace Jonnybarnes\WebmentionsParser;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Jonnybarnes\WebmentionsParser\Exceptions\AuthorshipParserException;
use Jonnybarnes\WebmentionsParser\Exceptions\InvalidMentionException;
use Jonnybarnes\WebmentionsParser\Exceptions\ParserException;
use function Mf2\parse;

class Parser
{
    /**
     * What we really want to parse are the microformats, but here's a starter
     * method to deal with the original HTML.
     *
     * @param string The HTML
     * @param string|null The domain the HTML is from
     *
     * @throws ParserException
     *
     * @return array The parsed microformats
     */
    public function getMicroformats(string $html, ?string $domain): array
    {
        try {
            $microformats = parse($html, $domain);
        } catch (Exception $exception) {
            throw new ParserException('php-mf2 failed to parse the HTML');
        }

        return $microformats;
    }

    /**
     * Return the type of mention or throw an error if undetermined.
     *
     * @param array The microformats
     *
     * @throws InvalidMentionException
     *
     * @return string The mention type
     */
    public function getMentionType(array $microformats): string
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

        // Can’t determine what type of mention it is, throw exception
        throw new InvalidMentionException();
    }

    /**
     * Check a mention is to the intended target.
     *
     * @param array The microformats
     * @param string The URL of the target
     *
     * @return bool
     */
    public function checkInReplyTo(array $microformats, string $target): bool
    {
        $items = $microformats['items'];

        foreach ($items as $item) {
            $properties = $item['properties'];
            if (array_key_exists('in-reply-to', $properties)) {
                if (is_array($properties['in-reply-to'][0])) {
                    if ($properties['in-reply-to'][0]['properties']['url'][0] == $target) {
                        return true;
                    }
                }
                foreach ($properties['in-reply-to'] as $url) {
                    if ($url === $target) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check the microformats contain a like for the target.
     *
     * @param array The microformats
     * @param string The target domain
     *
     * @return bool
     */
    public function checkLikeOf(array $microformats, string $target): bool
    {
        $likeOf = (isset($microformats['items'][0]['properties']['like-of']))
            ? $microformats['items'][0]['properties']['like-of']
            : null;

        if ($likeOf) {
            foreach ($likeOf as $url) {
                if ($url === $target) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check the microformats contain a repost of the target.
     *
     * @param array The microformats
     * @param string The target domain
     *
     * @return bool
     */
    public function checkRepostOf(array $microformats, string $target): bool
    {
        $repostOf = (isset($microformats['items'][0]['properties']['repost-of']))
            ? $microformats['items'][0]['properties']['repost-of']
            : null;

        if ($repostOf) {
            foreach ($repostOf as $url) {
                if ($url === $target) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Our recursive array_key_exists function.
     *
     * @param string $needle
     * @param array  $haystack
     *
     * @return bool
     */
    private function arrayKeyExistsRecursive(string $needle, array $haystack): bool
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
     * Now we actually parse the mf2 for desired data. In this case replies.
     *
     * @param array The microformats
     * @param string|null The source domain
     *
     * @throws GuzzleException
     * @throws ParserException
     *
     * @return array The reply content
     */
    public function replyContent(array $microformats, $domain = null): array
    {
        $replyHTML = (isset($microformats['items'][0]['properties']['content'][0]['html']))
            ? trim($microformats['items'][0]['properties']['content'][0]['html'])
            : null;

        if ($replyHTML === null) {
            //if there is no actual reply content...
            throw new ParserException('No reply content found');
        }

        $date = (isset($microformats['items'][0]['properties']['published'][0]))
            ? $microformats['items'][0]['properties']['published'][0]
            : date('Y-m-d H:i:s \U\T\CO');

        $authorship = new Authorship();
        try {
            $author = $authorship->findAuthor($microformats);
        } catch (AuthorshipParserException $e) {
            $author = null;
        }
        $authorNorm = $this->normaliseAuthor($author, $domain);

        return [
            'name' => $authorNorm['name'],
            'url' => $authorNorm['url'],
            'photo' => $authorNorm['photo'],
            'reply' => $replyHTML,
            'date' => $date,
        ];
    }

    /**
     * Parse the mf2 for desired like content.
     *
     * @param array The microformats
     * @param string|null The source domain
     *
     * @throws GuzzleException
     *
     * @return array The like content
     */
    public function likeContent(array $microformats, $domain = null): array
    {
        $authorship = new Authorship();
        try {
            $author = $authorship->findAuthor($microformats);
        } catch (AuthorshipParserException $exception) {
            $author = null;
        }
        $authorNorm = $this->normaliseAuthor($author, $domain);

        return [
            'name' => $authorNorm['name'],
            'url' => $authorNorm['url'],
            'photo' => $authorNorm['photo'],
        ];
    }

    /**
     * Parse the mf2 for desired repost content.
     *
     * @param array The microformats
     * @param string|null The source domain
     *
     * @throws GuzzleException
     *
     * @return array The repost content
     */
    public function repostContent(array $microformats, $domain = null): array
    {
        $url = (isset($microformats['items'][0]['properties']['repost-of'][0]))
            ? $microformats['items'][0]['properties']['repost-of'][0]
            : null;

        $date = (isset($microformats['items'][0]['properties']['published'][0]))
            ? $microformats['items'][0]['properties']['published'][0]
            : date('Y-m-d H:i:s \U\T\CO');

        $authorship = new Authorship();
        try {
            $author = $authorship->findAuthor($microformats);
        } catch (AuthorshipParserException $exception) {
            $author = null;
        }
        $authorNorm = $this->normaliseAuthor($author, $domain);

        return [
            'name' => $authorNorm['name'],
            'url' => $authorNorm['url'],
            'photo' => $authorNorm['photo'],
            'repost' => $url,
            'date' => $date,
        ];
    }

    /**
     * Parse the author data and return a flat array of the pertinent information.
     *
     * @param array The author info
     * @param string|null The source domain
     *
     * @return array Flattened author info
     */
    protected function normaliseAuthor(array $author, $domain = null): array
    {
        $authorNorm = ['name' => null, 'url' => null, 'photo' => null];

        if ($author !== null) {
            $authorNorm['name'] = $author['properties']['name'][0];
            $authorNorm['url'] = $author['properties']['url'][0];
            $authorNorm['photo'] = $author['properties']['photo'][0];

            return $authorNorm;
        }

        // We couldn’t find actual authorship data, so fall back to domain
        if ($domain !== null) {
            $authorNorm['name'] = parse_url($domain)['host'];
            $authorNorm['url'] = 'http://' . parse_url($domain)['host'];
        }

        return $authorNorm;
    }
}
