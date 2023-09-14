<?php
/**
 * Unit tests
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace tests\unit\Dotclear\Helper\Html;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

use atoum;
use Faker\Factory;

/**
 * @tags Wiki
 */
class WikiToHtml extends atoum
{
    public function testHelp()
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $this
            ->string($wiki->help())
            ->isNotEmpty();
    }

    public function testAntispam()
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $faker = Factory::create();
        $email = 'contact@dotclear.org';

        $this
            ->string($wiki->transform('Email: [Email|mailto:' . $email . '].'))
            ->isIdenticalTo('<p>Email: <a href="mailto:%63%6f%6e%74%61%63%74%40%64%6f%74%63%6c%65%61%72%2e%6f%72%67">Email</a>.</p>');

        $wiki->setOpt('active_antispam', 0);
        $this
            ->string($wiki->transform('Email: [Email|mailto:' . $email . '].'))
            ->isIdenticalTo('<p>Email: <a href="mailto:' . $email . '">Email</a>.</p>');
    }

    public function testOpt()
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $url = 'https://dotclear.org/';

        $wiki->setOpt('first_title_level', 5);
        $this
            ->string($wiki->transform('!!!H5'))
            ->isIdenticalTo('<h4>H5</h4>');

        $wiki->setOpt('active_setext_title', 1);
        $this
            ->string($wiki->transform('Title' . "\n" . '=====' . "\n" . 'Subtitle' . "\n" . '-----'))
            ->isIdenticalTo('<h4>Title</h4>' . "\n\n" . '<h5>Subtitle</h5>');

        $wiki->setOpt('active_auto_urls', 1);
        $this
            ->string($wiki->transform('URL: ' . $url))
            ->isIdenticalTo('<p>URL: <a href="' . $url . '" title="' . $url . '">' . $url . '</a></p>');

        $wiki->setOpt('active_urls', 0);
        $this
            ->string($wiki->transform('URL: ' . $url))
            ->isIdenticalTo('<p>URL: <a href="' . $url . '" title="' . $url . '">' . $url . '</a></p>');

        $wiki->setOpt('active_hr', 0);
        $this
            ->string($wiki->transform('----'))
            ->isIdenticalTo('<p><del></del></p>');

        $wiki->setOpt('active_hr', 1);
        $this
            ->string($wiki->transform('----'))
            ->isIdenticalTo('<hr />');

        $wiki->setOpts([
            'active_urls'        => 0,
            'active_auto_urls'   => 0,
            'active_img'         => 0,
            'active_anchor'      => 0,
            'active_em'          => 0,
            'active_strong'      => 0,
            'active_q'           => 0,
            'active_i'           => 0,
            'active_code'        => 0,
            'active_acronym'     => 0,
            'active_ins'         => 0,
            'active_del'         => 0,
            'active_inline_html' => 0,
            'active_footnotes'   => 0,
            'active_wikiwords'   => 0,
            'active_mark'        => 0,
            'active_sup'         => 0,
            'active_sub'         => 0,
            'active_empty'       => 0,
            'active_title'       => 0,
            'active_hr'          => 0,
            'active_quote'       => 0,
            'active_lists'       => 0,
            'active_defl'        => 0,
            'active_pre'         => 0,
            'active_aside'       => 0,
            'active_span'        => 0,
            'active_details'     => 0,
        ]);
        $text = <<<EOW

            URL: https://dotclear.org/
            ((/public/image.jpg))

            With an ~anchor~ here

            Some __strong__ and ''em'' texts with {{citation}} and ££text££ and @@code@@ plus an ??ACME|american company manufacturing everything?? where we can ++insert++ and --delete-- texts, and ;;with;; some ``<span class="focus">focus</span>`` and a footnote\$\$Footnote content\$\$

            Another ""mark""

            !!!Top level title

            !!Second level title

            !Third level title

            ----

            > Big quote
            > on several lines

            * List item 1
            * List item 2

             Pre code
             Another code line

            = term
            : definition

            :-)

            ) And finally an aside paragraph with a square^2 inside and some CO,,2,,
            )
            ) End

            |summary of details block
            content of details block
            |
            EOW;
        $html = <<<EOH
            <p>URL: https://dotclear.org/
            ((/public/image.jpg))</p>


            <p>With an ~anchor~ here</p>


            <p>Some __strong__ and ''em'' texts with {{citation}} and ££text££ and @@code@@ plus an ??ACME|american company manufacturing everything?? where we can ++insert++ and --delete-- texts, and ;;with;; some ``&lt;span class="focus"&gt;focus&lt;/span&gt;`` and a footnote\$\$Footnote content\$\$</p>


            <p>Another ""mark""</p>


            <p>!!!Top level title</p>


            <p>!!Second level title</p>


            <p>!Third level title</p>


            <p>----</p>


            <p>&gt; Big quote
            &gt; on several lines</p>


            <p>* List item 1
            * List item 2</p>


            <p>Pre code
            Another code line</p>


            <p>= term
            : definition</p>


            <p>:-)</p>


            <p>) And finally an aside paragraph with a square^2 inside and some CO,,2,,
            )
            ) End</p>


            <p>|summary of details block
            content of details block
            |</p>
            EOH;
        $this
            ->string($wiki->transform($text))
            ->isIdenticalTo($html);
    }

    public function testOpts()
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $wiki->setOpts([
            'active_hr' => 0,
            'active_br' => 0, ]);
        $this
            ->string($wiki->transform('----' . "\n" . 'Line%%%'))
            ->isIdenticalTo('<p><del></del>' . "\n" . 'Line%%%</p>');

        $wiki->setOpts([
            'active_hr' => 1,
            'active_br' => 1, ]);
        $this
            ->string($wiki->transform('----' . "\n" . 'Line%%%'))
            ->isIdenticalTo('<hr />' . "\n\n" . '<p>Line<br /></p>');
    }

    public function testTagTransform($tag, $delimiters)
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $faker  = Factory::create();
        $phrase = $faker->text(20);

        $this
            ->string($wiki->transform(sprintf('Before %s%s%s After', $delimiters[0], $phrase, $delimiters[1])))
            ->isIdenticalTo(sprintf('<p>Before <%1$s>%2$s</%1$s> After</p>', $tag, $phrase));

        $this
            ->string($wiki->transform(sprintf('%s%s%s', $delimiters[0], $phrase, $delimiters[1])))
            ->isIdenticalTo(sprintf('<p><%1$s>%2$s</%1$s></p>', $tag, $phrase));
    }

    public function testLinks()
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $faker = Factory::create();

        $lang  = $faker->languageCode();
        $title = $faker->text(10);
        $label = $faker->text(20);
        $url   = $faker->url();

        $this
            ->string($wiki->transform(sprintf('[%s|%s]', $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s">%2$s</a></p>', $url, $label))

            ->string($wiki->transform(sprintf('[%s|%s|%s]', $label, $url, $lang)))
            ->isIdenticalTo(sprintf('<p><a href="%s" hreflang="%s">%s</a></p>', $url, $lang, $label))

            ->string($wiki->transform(sprintf('[%s|%s|%s|%s]', $label, $url, $lang, $title)))
            ->isIdenticalTo(sprintf('<p><a href="%s" hreflang="%s" title="%s">%s</a></p>', $url, $lang, $title, $label))

            ->string($wiki->transform(sprintf('[\'\'%s\'\'|%s]', $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s"><em>%2$s</em></a></p>', $url, $label))

            ->string($wiki->transform(sprintf('[\'\'%s\'\' (em first)|%s]', $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s"><em>%2$s</em> (em first)</a></p>', $url, $label))

            ->string($wiki->transform(sprintf('[(em last) \'\'%s\'\'|%s]', $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s">(em last) <em>%2$s</em></a></p>', $url, $label))

            ->string($wiki->transform(sprintf('[(not first) \'\'%s\'\' (not last)|%s]', $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s">(not first) <em>%2$s</em> (not last)</a></p>', $url, $label))

            ->string($wiki->transform(sprintf('[__%s__|%s]', $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s"><strong>%2$s</strong></a></p>', $url, $label))

            ->string($wiki->transform(sprintf('[em: \'\'%s\'\' and strong: __%s__|%s]', $label, $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s">em: <em>%2$s</em> and strong: <strong>%2$s</strong></a></p>', $url, $label))

            ->string($wiki->transform(sprintf('[%s|%s]', $label, 'javascript:alert(1);')))
            ->isIdenticalTo(sprintf('<p><a href="#">%s</a></p>', $label))

            ->string($wiki->transform(sprintf('[%s|%s|9%s]', $label, $url, $lang)))
            ->isIdenticalTo(sprintf('<p><a href="%s">%s</a></p>', $url, $label))
        ;
    }

    public function testImages()
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $faker = Factory::create();

        $title  = $faker->text(10);
        $alt    = $faker->text(20);
        $url    = $faker->url();
        $legend = $faker->text(30);

        $this
            ->string($wiki->transform(sprintf('((%s|%s))', $url, $alt)))
            ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" /></p>', $url, $alt))

            ->string($wiki->transform(sprintf('((%s|%s||%s))', $url, $alt, $title)))
            ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" title="%s" /></p>', $url, $alt, $title))

            ->string($wiki->transform(sprintf('((%s|))', $url)))
            ->isIdenticalTo(sprintf('<p><img src="%s" alt="" /></p>', $url))

            ->string($wiki->transform(sprintf('((%s|%s|L))', $url, $alt)))
            ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:left; margin: 0 1em 1em 0;" /></p>', $url, $alt))

            ->string($wiki->transform(sprintf('((%s|%s|L|%s))', $url, $alt, $title)))
            ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:left; margin: 0 1em 1em 0;" title="%s" /></p>', $url, $alt, $title))

            ->string($wiki->transform(sprintf('((%s|%s|G))', $url, $alt)))
            ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:left; margin: 0 1em 1em 0;" /></p>', $url, $alt))

            ->string($wiki->transform(sprintf('((%s|%s|G|%s))', $url, $alt, $title)))
            ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:left; margin: 0 1em 1em 0;" title="%s" /></p>', $url, $alt, $title))

            ->string($wiki->transform(sprintf('((%s|%s|D))', $url, $alt)))
            ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:right; margin: 0 0 1em 1em;" /></p>', $url, $alt))

            ->string($wiki->transform(sprintf('((%s|%s|D|%s))', $url, $alt, $title)))
            ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:right; margin: 0 0 1em 1em;" title="%s" /></p>', $url, $alt, $title))

            ->string($wiki->transform(sprintf('((%s|%s|R))', $url, $alt)))
            ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:right; margin: 0 0 1em 1em;" /></p>', $url, $alt))

            ->string($wiki->transform(sprintf('((%s|%s|R|%s))', $url, $alt, $title)))
            ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:right; margin: 0 0 1em 1em;" title="%s" /></p>', $url, $alt, $title))

            ->string($wiki->transform(sprintf('((%s|%s|C))', $url, $alt)))
            ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="display:block; margin:0 auto;" /></p>', $url, $alt))

            ->string($wiki->transform(sprintf('((%s|%s|C|%s))', $url, $alt, $title)))
            ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="display:block; margin:0 auto;" title="%s" /></p>', $url, $alt, $title))

            ->string($wiki->transform(sprintf('((%s|%s|R|%s|%s))', $url, $alt, $title, $legend)))
            ->isIdenticalTo(sprintf('<figure style="float:right; margin: 0 0 1em 1em;"><img src="%s" alt="%s" title="%s" /><figcaption>%s</figcaption></figure>', $url, $alt, $title, $legend))

            ->string($wiki->transform(sprintf('((%s|%s|G|%s|%s))', $url, $alt, $title, $legend)))
            ->isIdenticalTo(sprintf('<figure style="float:left; margin: 0 1em 1em 0;"><img src="%s" alt="%s" title="%s" /><figcaption>%s</figcaption></figure>', $url, $alt, $title, $legend))

            ->string($wiki->transform(sprintf('((%s|%s|C|%s|%s))', $url, $alt, $title, $legend)))
            ->isIdenticalTo(sprintf('<figure style="display:block; margin:0 auto;"><img src="%s" alt="%s" title="%s" /><figcaption>%s</figcaption></figure>', $url, $alt, $title, $legend))
        ;
    }

    public function testBlocks($in, $out, $count)
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $faker = Factory::create();

        $url  = $faker->url();
        $word = $faker->word();
        $lang = $faker->languageCode();

        $search  = ['%url%', '%lang%', '%word%'];
        $replace = [$url, $lang, $word];

        $in  = str_replace($search, $replace, $in);
        $out = str_replace($search, $replace, $out);

        if (str_contains($in, '%s')) {
            for ($n = 1; $n <= $count; $n++) {
                $phrase[$n] = $faker->text(20);
            }

            $in  = vsprintf($in, $phrase);
            $out = vsprintf($out, $phrase);
        }
        $this
            ->string($this->removeSpace($wiki->transform($in)))
            ->isIdenticalTo($out);
    }

    public function testAutoBR()
    {
        $wiki  = new \Dotclear\Helper\Html\WikiToHtml();
        $faker = Factory::create();

        $text = $faker->paragraphs(3);

        $this
            ->string($wiki->transform(implode("\n", $text)))
            ->isIdenticalTo('<p>' . implode("\n", $text) . '</p>')

            ->if($wiki->setOpt('active_auto_br', 1))
            ->then()
            ->string($wiki->transform(implode("\n", $text)))
            ->isIdenticalTo('<p>' . nl2br(implode("\n", $text)) . '</p>')
        ;
    }

    public function testMacro()
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $in_html  = "///html\n<p>some text</p>\n<p><strong>un</strong> autre</p>\n///";
        $out_html = "<p>some text</p>\n<p><strong>un</strong> autre</p>\n";

        $in                = "///dummy-macro\n<?php\necho \"Hello World!\";\n?>\n///";
        $out_without_macro = "<pre>dummy-macro\n&lt;?php\necho &quot;Hello World!&quot;;\n?&gt;\n</pre>";
        $out               = "[[<?php\necho \"Hello World!\";\n?>\n]]";

        $this
            ->string($wiki->transform($in_html))
            ->isIdenticalTo($out_html)

            ->string($wiki->transform($in))
            ->isIdenticalTo($out_without_macro);

        $this
            ->if($wiki->registerFunction('macro:dummy-macro', fn ($s) => "[[$s]]"))
            ->object($wiki->functions['macro:dummy-macro'])
            ->isCallable()
            ->string($wiki->transform($in))
            ->isIdenticalTo($out);
    }

    public function testAcronyms()
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $in_html           = "Some __strong__ and ''em'' ??dc?? texts with {{citation}} and @@code@@ plus an ??cb?? ??ACME|american company manufacturing everything?? where we can ++insert++ and --delete-- texts, and with some ``<span class=\"focus\">focus</span>`` on specific part";
        $out_html          = '<p>Some <strong>strong</strong> and <em>em</em> <abbr>dc</abbr> texts with <q>citation</q> and <code>code</code> plus an <abbr>cb</abbr> <abbr title="american company manufacturing everything">ACME</abbr> where we can <ins>insert</ins> and <del>delete</del> texts, and with some <span class="focus">focus</span> on specific part</p>';
        $out_html_acronyms = '<p>Some <strong>strong</strong> and <em>em</em> <abbr title="dotclear">dc</abbr> texts with <q>citation</q> and <code>code</code> plus an <abbr title="clearbicks">cb</abbr> <abbr title="american company manufacturing everything">ACME</abbr> where we can <ins>insert</ins> and <del>delete</del> texts, and with some <span class="focus">focus</span> on specific part</p>';

        $this
            ->string($wiki->transform($in_html))
            ->isIdenticalTo($out_html);

        $acronyms = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'Html', 'acronyms.txt']);
        $wiki->setOpt('acronyms_file', $acronyms);

        $this
            ->string($wiki->transform($in_html))
            ->isIdenticalTo($out_html_acronyms);
    }

    public function testWikiWords()
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $in_html           = "Some __strong__ and ''em'' texts with {{citation}} and @@code@@ plus an ??ACME|american company manufacturing everything?? where we can ++insert++ and --delete-- texts, and with some ``<span class=\"focus\">focus</span>`` on specific WikiWord part";
        $out_html          = '<p>Some <strong>strong</strong> and <em>em</em> texts with <q>citation</q> and <code>code</code> plus an <abbr title="american company manufacturing everything">ACME</abbr> where we can <ins>insert</ins> and <del>delete</del> texts, and with some <span class="focus">focus</span> on specific WikiWord part</p>';
        $out_html_acronyms = '<p>Some <strong>strong</strong> and <em>em</em> texts with <q>citation</q> and <code>code</code> plus an <abbr title="american company manufacturing everything">ACME</abbr> where we can <ins>insert</ins> and <del>delete</del> texts, and with some <span class="focus">focus</span> on specific wikiword part</p>';

        $this
            ->string($wiki->transform($in_html))
            ->isIdenticalTo($out_html);

        $wiki->setOpt('active_wikiwords', 1);
        $this
            ->string($wiki->transform($in_html))
            ->isIdenticalTo($out_html);

        $wiki->registerFunction('wikiword', fn ($str) => strtolower($str));
        $this
            ->string($wiki->transform($in_html))
            ->isIdenticalTo($out_html_acronyms);
    }

    public function testSpecialURLs()
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $in_html          = 'Test with an [Wiki first link|wiki:first_link] !';
        $out_html         = '<p>Test with an <a href="wiki:first_link">Wiki first link</a>&nbsp;!</p>';
        $out_html_special = '<p>Test with an <a href="https://example.org/wiki/first_link" title="Wiki">Wiki first link</a>&nbsp;!</p>';

        $this
            ->string($wiki->transform($in_html))
            ->isIdenticalTo($out_html);

        $wiki->registerFunction('url:wiki', fn ($url, $content) => ['url' => 'https://example.org/wiki/' . substr($url, 5), 'content' => $content, 'title' => 'Wiki']);
        $this
            ->string($wiki->transform($in_html))
            ->isIdenticalTo($out_html_special);
    }

    public function testAttributes()
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $in_html = <<<EOW
            ) lorem ipsum 1

            ) lorem ipsum 2§§class="title"§§

            ) lorem ipsum 3 §§class="title"§§

            ) lorem ipsum 4§§class="title"§§
            ) lorem ipsum 5

            ) lorem ipsum 6
            ) lorem ipsum 7§§class="title"§§
            EOW;

        $out_html = <<<EOH
            <aside><p>lorem ipsum 1</p></aside>


            <aside class="title"><p>lorem ipsum 2</p></aside>


            <aside class="title"><p>lorem ipsum 3</p></aside>


            <aside class="title"><p>lorem ipsum 4
            lorem ipsum 5</p></aside>


            <aside><p>lorem ipsum 6
            lorem ipsum 7</p></aside>
            EOH;

        $this
            ->string($wiki->transform($in_html))
            ->isIdenticalTo($out_html);
    }

    /*
     * DataProviders
     **/

    protected function testTagTransformDataProvider()
    {
        return [
            ['em', ["''", "''"]],
            ['strong', ['__', '__']],
            ['abbr', ['??', '??']],
            ['q', ['{{', '}}']],
            ['code', ['@@', '@@']],
            ['del', ['--', '--']],
            ['ins', ['++', '++']],
            ['mark', ['""', '""']],
            ['sup', ['^', '^']],
            ['sub', [',,', ',,']],
            ['i', ['££', '££']],
            ['span', [';;', ';;']],
        ];
    }

    protected function testBlocksDataProvider()
    {
        return [
            ['\[not a link | not a title label\]',
                '<p>[not a link | not a title label]</p>', 0, ],
            ['``<strong>%s</strong>%s</p><ul><li>%s</li><li>%s</li></ul>``',
                '<p><strong>%s</strong>%s</p><ul><li>%s</li><li>%s</li></ul></p>', 4, ],
            ["* item 1\n** item 1.1\n** item 1.2\n* item 2\n* item 3\n*# item 3.1",
                '<ul><li>item 1<ul><li>item 1.1</li><li>item 1.2</li></ul></li>' .
                '<li>item 2</li><li>item 3<ol><li>item 3.1</li></ol></li></ul>', 1, ],
            ["# item 1\n#* item 1.1\n#* item 1.2\n# item 2\n# item 3\n## item 3.1\n# item 4",
                '<ol><li>item 1<ul><li>item 1.1</li><li>item 1.2</li></ul></li>' .
                '<li>item 2</li><li>item 3<ol><li>item 3.1</li></ol></li><li>item 4</li></ol>', 1, ],

            ['{{%s}}', '<p><q>%s</q></p>', 1],
            ['{{%s|%lang%}}', '<p><q lang="%lang%">%s</q></p>', 1],
            ['{{%s|%lang%|%url%}}', '<p><q lang="%lang%" cite="%url%">%s</q></p>', 1],

            ['££%s££', '<p><i>%s</i></p>', 1],
            ['££%s|%lang%££', '<p><i lang="%lang%">%s</i></p>', 1],

            [" %s\n %s\n %s", '<pre>%s%s%s</pre>', 3],
            ['??%1$s|%2$s??', '<p><abbr title="%2$s">%1$s</abbr></p>', 2],
            [">%s\n>%s", '<blockquote><p>%s%s</p></blockquote>', 2],

            ['----', '<hr />', 0],
            [' %s', '<pre>%s</pre>', 1],
            [') %s', '<aside><p>%s</p></aside>', 1],
            [") %s\n)\n) %s", '<aside><p>%s</p><p>%s</p></aside>', 2],
            ['!!!!%s', '<h2>%s</h2>', 1],
            ['!!!%s', '<h3>%s</h3>', 1],
            ['!!%s', '<h4>%s</h4>', 1],
            ['!%s', '<h5>%s</h5>', 1],
            ['~%word%~', '<p><a id="%word%"></a></p>', 1],

            ['@@%s@@', '<p><code>%s</code></p>', 1],

            ['%s$$%s$$', '<p>%s<sup>[<a href="#wiki-footnote-1" id="rev-wiki-footnote-1">1</a>]</sup></p>' .
                '<div class="footnotes"><h4>Note</h4><p>[<a href="#rev-wiki-footnote-1" id="wiki-footnote-1">1</a>] ' .
                '%s</p></div>', 2, ],
            ['%s$$%s$$', '<p>%s<sup>[<a href="#wiki-footnote-1" id="rev-wiki-footnote-1">1</a>]</sup></p>' .
                '<div class="footnotes"><h4>Note</h4><p>[<a href="#rev-wiki-footnote-1" id="wiki-footnote-1">1</a>] ' .
                '%s</p></div>', 2, ],

            ["* %s\n///\n%s\n///\n", '<ul><li>%s</li></ul><pre>%s</pre>', 2],
            ["# %s\n///\n%s\n///\n", '<ol><li>%s</li></ol><pre>%s</pre>', 2],

            ['= term', '<dl><dt>term</dt></dl>', 0],
            [': definition', '<dl><dd>definition</dd></dl>', 0],
            ['= %s', '<dl><dt>%s</dt></dl>', 1],
            [': %s', '<dl><dd>%s</dd></dl>', 1],
            ["= %s\n: %s", '<dl><dt>%s</dt><dd>%s</dd></dl>', 2],
            ["= %s\n= %s\n: %s\n: %s", '<dl><dt>%s</dt><dt>%s</dt><dd>%s</dd><dd>%s</dd></dl>', 4],

            ["|summary\n%s\n|", '<details><summary>summary</summary><p>%s</p></details>', 1],

            // With attributes

            ["* item 1§§class=\"title\"§§\n** item 1.1\n** item 1.2\n* item 2\n* item 3\n*# item 3.1",
                '<ul><li class="title">item 1<ul><li>item 1.1</li><li>item 1.2</li></ul></li>' .
                '<li>item 2</li><li>item 3<ol><li>item 3.1</li></ol></li></ul>', 1, ],
            ["# item 1§§class=\"title\"§§\n#* item 1.1\n#* item 1.2\n# item 2\n# item 3\n## item 3.1\n# item 4",
                '<ol><li class="title">item 1<ul><li>item 1.1</li><li>item 1.2</li></ul></li>' .
                '<li>item 2</li><li>item 3<ol><li>item 3.1</li></ol></li><li>item 4</li></ol>', 1, ],

            ["* item 1§§class=\"title\"|class=\"parent\"§§\n** item 1.1\n** item 1.2\n* item 2\n* item 3\n*# item 3.1",
                '<ul class="parent"><li class="title">item 1<ul><li>item 1.1</li><li>item 1.2</li></ul></li>' .
                '<li>item 2</li><li>item 3<ol><li>item 3.1</li></ol></li></ul>', 1, ],
            ["# item 1§§class=\"title\"|class=\"parent\"§§\n#* item 1.1\n#* item 1.2\n# item 2\n# item 3\n## item 3.1\n# item 4",
                '<ol class="parent"><li class="title">item 1<ul><li>item 1.1</li><li>item 1.2</li></ul></li>' .
                '<li>item 2</li><li>item 3<ol><li>item 3.1</li></ol></li><li>item 4</li></ol>', 1, ],

            ["* item 1§§|class=\"parent\"§§\n** item 1.1\n** item 1.2\n* item 2\n* item 3\n*# item 3.1",
                '<ul class="parent"><li>item 1<ul><li>item 1.1</li><li>item 1.2</li></ul></li>' .
                '<li>item 2</li><li>item 3<ol><li>item 3.1</li></ol></li></ul>', 1, ],
            ["# item 1§§|class=\"parent\"§§\n#* item 1.1\n#* item 1.2\n# item 2\n# item 3\n## item 3.1\n# item 4",
                '<ol class="parent"><li>item 1<ul><li>item 1.1</li><li>item 1.2</li></ul></li>' .
                '<li>item 2</li><li>item 3<ol><li>item 3.1</li></ol></li><li>item 4</li></ol>', 1, ],

            ["* item 1§§class=\"title-1\"§§\n** item 1.1\n** item 1.2§§class=\"title-1-2\"§§\n* item 2\n* item 3\n*# item 3.1",
                '<ul><li class="title-1">item 1<ul><li>item 1.1</li><li class="title-1-2">item 1.2</li></ul></li>' .
                '<li>item 2</li><li>item 3<ol><li>item 3.1</li></ol></li></ul>', 1, ],
            ["# item 1§§class=\"title-1\"§§\n#* item 1.1\n#* item 1.2§§class=\"title-1-2\"§§\n# item 2\n# item 3\n## item 3.1\n# item 4",
                '<ol><li class="title-1">item 1<ul><li>item 1.1</li><li class="title-1-2">item 1.2</li></ul></li>' .
                '<li>item 2</li><li>item 3<ol><li>item 3.1</li></ol></li><li>item 4</li></ol>', 1, ],

            ['----§§class="title"§§', '<hr class="title" />', 0],
            [' %s§§class="title"§§', '<pre class="title">%s</pre>', 1],
            [') %s§§class="title"§§', '<aside class="title"><p>%s</p></aside>', 1],
            [") %s§§class=\"title\"§§\n)\n) %s", '<aside class="title"><p>%s</p><p>%s</p></aside>', 2],
            ['!!!!%s§§class="title"§§', '<h2 class="title">%s</h2>', 1],
            ['!!!%s§§class="title"§§', '<h3 class="title">%s</h3>', 1],
            ['!!%s§§class="title"§§', '<h4 class="title">%s</h4>', 1],
            ['!%s§§class="title"§§', '<h5 class="title">%s</h5>', 1],

            ['= term§§class="title"§§', '<dl><dt class="title">term</dt></dl>', 0],
            [': definition§§class="title"§§', '<dl><dd class="title">definition</dd></dl>', 0],

            ['= term§§class="title"|class="parent"§§', '<dl class="parent"><dt class="title">term</dt></dl>', 0],
            [': definition§§class="title"|class="parent"§§', '<dl class="parent"><dd class="title">definition</dd></dl>', 0],

            ['= term§§|class="parent"§§', '<dl class="parent"><dt>term</dt></dl>', 0],
            [': definition§§|class="parent"§§', '<dl class="parent"><dd>definition</dd></dl>', 0],

            ["|summary§§open§§\n%s\n|", '<details open><summary>summary</summary><p>%s</p></details>', 1],

            [">%s§§class=\"title\"§§\n>%s", '<blockquote class="title"><p>%s%s</p></blockquote>', 2],

            ['%s§§class="title"§§', '<p class="title">%s</p>', 1],

            ['%s __bold§class="bold"§__ lorem \'\'ipsum§class="italic"§\'\'§§class="title"§§', '<p class="title">%s <strong class="bold">bold</strong> lorem <em class="italic">ipsum</em></p>', 1],

            ['%s \'\'%s§class="inline"§\'\' %s', '<p>%s <em class="inline">%s</em> %s</p>', 3],
            ['%s __%s§class="inline"§__ %s', '<p>%s <strong class="inline">%s</strong> %s</p>', 3],

            ['%s {{%s§class="inline"§}} %s', '<p>%s <q class="inline">%s</q> %s</p>', 3],
            ['%s {{%s|fr§class="inline"§}} %s', '<p>%s <q class="inline" lang="fr">%s</q> %s</p>', 3],
            ['%s {{%s|fr|https//dotclear.net/§class="inline"§}} %s', '<p>%s <q class="inline" lang="fr" cite="https//dotclear.net/">%s</q> %s</p>', 3],

            ['%s @@%s§class="inline"§@@ %s', '<p>%s <code class="inline">%s</code> %s</p>', 3],

            ['%s --%s§class="inline"§-- %s', '<p>%s <del class="inline">%s</del> %s</p>', 3],
            ['%s ++%s§class="inline"§++ %s', '<p>%s <ins class="inline">%s</ins> %s</p>', 3],

            ['%s ""%s§class="inline"§"" %s', '<p>%s <mark class="inline">%s</mark> %s</p>', 3],

            ['%s ^%s§class="inline"§^ %s', '<p>%s <sup class="inline">%s</sup> %s</p>', 3],
            ['%s ,,%s§class="inline"§,, %s', '<p>%s <sub class="inline">%s</sub> %s</p>', 3],

            ['%s ££%s§class="inline"§££ %s', '<p>%s <i class="inline">%s</i> %s</p>', 3],
            ['%s ££%s|fr§class="inline"§££ %s', '<p>%s <i class="inline" lang="fr">%s</i> %s</p>', 3],

            ['%s ??%s§class="inline"§?? %s', '<p>%s <abbr class="inline">%s</abbr> %s</p>', 3],
            ['%s ??%s|Title§class="inline"§?? %s', '<p>%s <abbr class="inline" title="Title">%s</abbr> %s</p>', 3],

            ['%s ;;%s§class="inline"§;; %s', '<p>%s <span class="inline">%s</span> %s</p>', 3],

            ['~%word%§class="anchor"§~', '<p><a class="anchor" id="%word%"></a></p>', 1],

            ['[%1$s§class="link"§]', '<p><a class="link" href="%1$s" title="%1$s">%1$s</a></p>', 1],
            ['[%1$s|%2$s§class="link"§]', '<p><a class="link" href="%2$s">%1$s</a></p>', 2],
            ['[%1$s|%2$s|fr§class="link"§]', '<p><a class="link" href="%2$s" hreflang="fr">%1$s</a></p>', 2],
            ['[%1$s|%2$s|fr|%3$s§class="link"§]', '<p><a class="link" href="%2$s" hreflang="fr" title="%3$s">%1$s</a></p>', 2],

            ['((%s|%s§class="img"§))', '<p><img class="img" src="%s" alt="%s" /></p>', 2],
            ['((%s|§class="img"§))', '<p><img class="img" src="%s" alt="" /></p>', 1],
            ['((%s|%s|C§class="img"§))', '<p><img class="img" src="%s" alt="%s" style="display:block; margin:0 auto;" /></p>', 2],
            ['((%s|%s|C|%s§class="img"§))', '<p><img class="img" src="%s" alt="%s" style="display:block; margin:0 auto;" title="%s" /></p>', 3],
            ['((%s|%s|C|%s|legend§class="img"§))', '<figure class="img" style="display:block; margin:0 auto;"><img class="img" src="%s" alt="%s" title="%s" /><figcaption>legend</figcaption></figure>', 3],

            ['[((%s|%s))|https://dotclear.net/]', '<p><a href="https://dotclear.net/"><img src="%s" alt="%s" /></a></p>', 2],
            ['[((%s|%s))|https://dotclear.net/§class="link"§]', '<p><a class="link" href="https://dotclear.net/"><img src="%s" alt="%s" /></a></p>', 2],

            ['[text __bold§class="bold"§__|https://dotclear.net/§class="link"§]', '<p><a class="bold" class="link" href="https://dotclear.net/">text <strong>bold</strong></a></p>', 2],
            ['[((%s|%s§class="img"§))|https://dotclear.net/§class="link"§]', '<p><a class="img" class="link" href="https://dotclear.net/"><img src="%s" alt="%s" /></a></p>', 2],
        ];
    }

    /*
     **/

    private function removeSpace($s)
    {
        return str_replace(["\r\n", "\n"], ['', ''], $s);
    }
}
