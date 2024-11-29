<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network\Feed;

use SimpleXMLElement;
use stdClass;

/**
 * @class Parser
 *
 * This class can read RSS 1.0, RSS 2.0, Atom 0.3 and Atom 1.0 feeds. Works with
 * {@link Reader}
 */
class Parser
{
    /**
     * Feed type
     *
     * @var string
     */
    public $feed_type;

    /**
     * Feed title
     *
     * @var string
     */
    public $title;

    /**
     * Feed link
     *
     * @var string
     */
    public $link;

    /**
     * Feed description
     *
     * @var string
     */
    public $description;

    /**
     * Feed publication date
     *
     * @var string
     */
    public $pubdate;

    /**
     * Feed generator
     *
     * @var string
     */
    public $generator;

    /**
     * Feed items
     *
     * @var array<stdClass>
     */
    public $items = [];

    /**
     * Feed XML content
     *
     * @var SimpleXMLElement|false
     */
    protected $xml;

    /**
     * Constructor.
     *
     * Takes some <var>$data</var> as input. Returns void if data is
     * not a valid XML stream. If everything's fine, feed is parsed and items
     * are in {@link $items} property.
     *
     * @param string    $data            XML stream
     */
    public function __construct(string $data)
    {
        $this->xml = @simplexml_load_string($data);

        if (!$this->xml) {
            return;
        }

        if (preg_match('/<rdf:RDF/', (string) $data)) {
            $this->parseRSSRDF();
        } elseif (preg_match('/<rss/', (string) $data)) {
            $this->parseRSS();
        } elseif (preg_match('!www.w3.org/2005/Atom!', (string) $data)) {
            $this->parseAtom10();
        } else {
            $this->parseAtom03();
        }

        unset($data, $this->xml);
    }

    /**
     * RSS 1.0 parser.
     */
    protected function parseRSSRDF(): void
    {
        $this->feed_type = 'rss 1.0 (rdf)';

        if (!$this->xml) {
            return;
        }

        if ($this->xml->channel) {
            $this->title       = (string) $this->xml->channel->title;
            $this->link        = (string) $this->xml->channel->link;
            $this->description = (string) $this->xml->channel->description;

            $this->pubdate = '';
            if ($children = $this->xml->channel->children('http://purl.org/dc/elements/1.1/')) {
                $this->pubdate = (string) $children->date;
            }

            # Feed generator agent
            $this->generator = null;
            if ($children = $this->xml->channel->children('http://webns.net/mvcb/')) {
                $generator = $children->generatorAgent;
                if ($generator) {
                    $generator       = $generator->attributes('http://www.w3.org/1999/02/22-rdf-syntax-ns#');
                    $this->generator = (string) ($generator['resource'] ?? '');
                }
            }
        }

        if (empty($this->xml->item)) {
            return;
        }

        foreach ($this->xml->item as $i) {
            $item              = new stdClass();
            $item->title       = (string) $i->title;
            $item->link        = (string) $i->link;
            $item->description = (string) $i->description;

            $item->subject = [];
            $item->creator = '';
            $item->pubdate = '';
            $item->content = '';
            if ($children = $i->children('http://purl.org/dc/elements/1.1/')) {
                if ($children->subject) {
                    $item->subject = $this->nodes2array($children->subject);
                }
                $item->creator = (string) $children->creator;
                $item->pubdate = (string) $children->date;
            }
            $item->TS = strtotime((string) $item->pubdate);
            if ($children = $i->children('http://purl.org/rss/1.0/modules/content/')) {
                $item->content = (string) $children->encoded;
            }

            $item->guid = (string) $item->link;
            if (!empty($i->attributes('http://www.w3.org/1999/02/22-rdf-syntax-ns#')->about)) {
                $item->guid = (string) $i->attributes('http://www.w3.org/1999/02/22-rdf-syntax-ns#')->about;
            }

            $this->items[] = $item;
        }
    }

    /**
     * RSS 2.0 parser
     */
    protected function parseRSS(): void
    {
        if (!$this->xml) {
            return;
        }

        $this->feed_type = 'rss ' . $this->xml['version'];

        if ($this->xml->channel) {
            $this->title       = (string) $this->xml->channel->title;
            $this->link        = (string) $this->xml->channel->link;
            $this->description = (string) $this->xml->channel->description;
            $this->pubdate     = (string) $this->xml->channel->pubDate;

            $this->generator = (string) $this->xml->channel->generator;

            if (empty($this->xml->channel->item)) {
                return;
            }

            foreach ($this->xml->channel->item as $i) {
                $item              = new stdClass();
                $item->title       = (string) $i->title;
                $item->link        = (string) $i->link;
                $item->description = (string) $i->description;
                $item->pubdate     = (string) $i->pubDate;
                $item->TS          = strtotime($item->pubdate);
                $item->guid        = (string) $item->link;
                if (!empty($i->guid)) {
                    $item->guid = (string) $i->guid;
                }

                $item->subject = [];
                $item->creator = '';
                $item->content = '';
                if ($children = $i->children('http://purl.org/dc/elements/1.1/')) {
                    $item->creator = (string) $children->creator;
                    if ($children->subject && $i->category) {
                        $item->subject = [
                            ...$this->nodes2array($children->subject),
                            ...$this->nodes2array($i->category),
                        ];
                    }

                    if (!$item->pubdate && !empty($children->date)) {
                        $item->pubdate = (string) $children->date;
                    }
                }
                if ($children = $i->children('http://purl.org/rss/1.0/modules/content/')) {
                    $item->content = (string) $children->encoded;
                }

                $this->items[] = $item;
            }
        }
    }

    /**
     * Atom 0.3 parser
     */
    protected function parseAtom03(): void
    {
        $this->feed_type = 'atom 0.3';

        if (!$this->xml) {
            return;
        }

        $this->title       = (string) $this->xml->title;
        $this->description = (string) $this->xml->subtitle;
        $this->pubdate     = (string) $this->xml->modified;

        $this->generator = (string) $this->xml->generator;

        if ($this->xml->link) {
            foreach ($this->xml->link as $link) {
                if ($link['rel'] == 'alternate' && ($link['type'] == 'text/html' || $link['type'] == 'application/xhtml+xml')) {
                    $this->link = (string) $link['href'];

                    break;
                }
            }
        }

        if (empty($this->xml->entry)) {
            return;
        }

        foreach ($this->xml->entry as $i) {
            $item = new stdClass();

            if ($i->link) {
                foreach ($i->link as $link) {
                    if ($link['rel'] == 'alternate' && ($link['type'] == 'text/html' || $link['type'] == 'application/xhtml+xml')) {
                        $item->link = (string) $link['href'];

                        break;
                    }

                    $item->link = (string) $link['href'];
                }
            }

            $item->title       = (string) $i->title;
            $item->creator     = (string) $i->author?->name;
            $item->description = (string) $i->summary;
            $item->content     = (string) $i->content;
            $item->pubdate     = (string) $i->modified;
            $item->TS          = strtotime($item->pubdate);

            $item->subject = [];
            if ($children = $i->children('http://purl.org/dc/elements/1.1/')) {
                if ($children->subject) {
                    $item->subject = $this->nodes2array($children->subject);
                }
            }

            $this->items[] = $item;
        }
    }

    /**
     * Atom 1.0 parser
     */
    protected function parseAtom10(): void
    {
        $this->feed_type = 'atom 1.0';

        if (!$this->xml) {
            return;
        }

        $this->title       = (string) $this->xml->title;
        $this->description = (string) $this->xml->subtitle;
        $this->pubdate     = (string) $this->xml->updated;

        $this->generator = (string) $this->xml->generator;

        if ($this->xml->link) {
            foreach ($this->xml->link as $link) {
                if ($link['rel'] == 'alternate' && ($link['type'] == 'text/html' || $link['type'] == 'application/xhtml+xml')) {
                    $this->link = (string) $link['href'];

                    break;
                }
            }
        }

        if (empty($this->xml->entry)) {
            return;
        }

        foreach ($this->xml->entry as $i) {
            $item = new \stdClass();

            if ($i->link) {
                foreach ($i->link as $link) {
                    if ($link['rel'] == 'alternate' && ($link['type'] == 'text/html' || $link['type'] == 'application/xhtml+xml')) {
                        $item->link = (string) $link['href'];

                        break;
                    }

                    $item->link = (string) $link['href'];
                }
            }

            $item->title       = (string) $i->title;
            $item->creator     = (string) $i->author?->name;
            $item->description = (string) $i->summary;
            $item->content     = (string) $i->content;
            $item->pubdate     = !empty($i->published) ? (string) $i->published : (string) $i->updated;
            $item->TS          = strtotime($item->pubdate);

            $item->subject = [];
            if ($children = $i->children('http://purl.org/dc/elements/1.1/')) {
                if ($children->subject) {
                    $item->subject = $this->nodes2array($children->subject);
                }
            }

            $this->items[] = $item;
        }
    }

    /**
     * SimpleXML to array
     *
     * Converts a SimpleXMLElement to an array.
     *
     * @param SimpleXMLElement    $node    SimpleXML Node
     *
     * @return array<string>
     */
    protected function nodes2array(SimpleXMLElement &$node): array
    {
        if (empty($node)) {
            return [];
        }

        $res = [];
        foreach ($node as $v) {
            $res[] = (string) $v;
        }

        return $res;
    }
}
