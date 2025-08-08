<?php

declare(strict_types=1);

use Dotclear\Helper\Html\WikiToHtml;
use PHPUnit\Framework\TestCase;

class WikiToHtmlTest extends TestCase
{
    public function testHelp()
    {
        $wiki = new WikiToHtml();

        $help = $wiki->help();
        $this->assertNotEmpty($help);
    }

    public function testAntispam()
    {
        $wiki = new WikiToHtml();

        $email = 'contact@dotclear.org';

        // Test with antispam active (default)
        $this->assertSame(
            '<p>Email: <a href="mailto:%63%6f%6e%74%61%63%74%40%64%6f%74%63%6c%65%61%72%2e%6f%72%67">Email</a>.</p>',
            $wiki->transform('Email: [Email|mailto:' . $email . '].')
        );

        // Test with antispam disabled
        $wiki->setOpt('active_antispam', 0);
        $this->assertSame(
            '<p>Email: <a href="mailto:' . $email . '">Email</a>.</p>',
            $wiki->transform('Email: [Email|mailto:' . $email . '].')
        );
    }

    public function testOpt()
    {
        $wiki = new \Dotclear\Helper\Html\WikiToHtml();

        $url = 'https://dotclear.org/';

        $wiki->setOpt('first_title_level', 5);
        $this->assertSame(
            '<h4>H5</h4>',
            $wiki->transform('!!!H5')
        );

        $wiki->setOpt('active_setext_title', 1);
        $this->assertSame(
            '<h4>Title</h4>' . "\n\n" . '<h5>Subtitle</h5>',
            $wiki->transform('Title' . "\n" . '=====' . "\n" . 'Subtitle' . "\n" . '-----')
        );

        $wiki->setOpt('active_auto_urls', 1);
        $this->assertSame(
            '<p>URL: <a href="' . $url . '" title="' . $url . '">' . $url . '</a></p>',
            $wiki->transform('URL: ' . $url)
        );

        $wiki->setOpt('active_urls', 0);
        $this->assertSame(
            '<p>URL: <a href="' . $url . '" title="' . $url . '">' . $url . '</a></p>',
            $wiki->transform('URL: ' . $url)
        );

        $wiki->setOpt('active_hr', 0);
        $this->assertSame(
            '<p><del></del></p>',
            $wiki->transform('----')
        );

        $wiki->setOpt('active_hr', 1);
        $this->assertSame(
            '<hr>',
            $wiki->transform('----')
        );

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
        $this->assertSame(
            $html,
            $wiki->transform($text)
        );
    }
}
