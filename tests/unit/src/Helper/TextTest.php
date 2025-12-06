<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper;

use Faker\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    public function testIsEmail(): void
    {
        $faker = Factory::create();
        $text  = $faker->email();

        $this->assertTrue(
            \Dotclear\Helper\Text::isEmail($text)
        );
        $this->assertFalse(
            \Dotclear\Helper\Text::isEmail('@dotclear.org')
        );
    }

    /**
     * @return list<array{0:string, 1:bool}>
     */
    public static function dataProviderIsEmailAll(): array
    {
        // test suite from https://code.iamcal.com/php/rfc822/tests/
        // Commented expected values are those that PHP filter_var() failed to validate/unvalidate
        $emailTest = [
            ['first.last@iana.org', true],
            ['1234567890123456789012345678901234567890123456789012345678901234@iana.org', true],
            ['first.last@sub.do,com', false],
            ['"first\"last"@iana.org', true],
            ['first\@last@iana.org', false],
            ['"first@last"@iana.org', true],
            ['"first\\last"@iana.org', true],
            ['x@x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x2', true],
            ['1234567890123456789012345678901234567890123456789012345678@12345678901234567890123456789012345678901234567890123456789.12345678901234567890123456789012345678901234567890123456789.123456789012345678901234567890123456789012345678901234567890123.iana.org', true],
            ['first.last@[12.34.56.78]', true],
            ['first.last@[IPv6:::12.34.56.78]', true],
            ['first.last@[IPv6:1111:2222:3333::4444:12.34.56.78]', true],
            ['first.last@[IPv6:1111:2222:3333:4444:5555:6666:12.34.56.78]', true],
            ['first.last@[IPv6:::1111:2222:3333:4444:5555:6666]', true],
            ['first.last@[IPv6:1111:2222:3333::4444:5555:6666]', true],
            ['first.last@[IPv6:1111:2222:3333:4444:5555:6666::]', true],
            ['first.last@[IPv6:1111:2222:3333:4444:5555:6666:7777:8888]', true],
            ['first.last@x23456789012345678901234567890123456789012345678901234567890123.iana.org', true],
            ['first.last@3com.com', true],
            ['first.last@123.iana.org', true],
            ['123456789012345678901234567890123456789012345678901234567890@12345678901234567890123456789012345678901234567890123456789.12345678901234567890123456789012345678901234567890123456789.12345678901234567890123456789012345678901234567890123456789.12345.iana.org', false],
            ['first.last', false],
            ['12345678901234567890123456789012345678901234567890123456789012345@iana.org', false],
            ['.first.last@iana.org', false],
            ['first.last.@iana.org', false],
            ['first..last@iana.org', false],
            ['"first"last"@iana.org', false],
            ['"first\last"@iana.org', true],
            ['"""@iana.org', false],
            ['"\"@iana.org', false],
            ['""@iana.org', true], // [30] false),
            ['first\\@last@iana.org', false],
            ['first.last@', false],
            ['x@x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456', false],
            ['first.last@[.12.34.56.78]', false],
            ['first.last@[12.34.56.789]', false],
            ['first.last@[::12.34.56.78]', false],
            ['first.last@[IPv5:::12.34.56.78]', false],
            ['first.last@[IPv6:1111:2222:3333::4444:5555:12.34.56.78]', false], // [38] true),
            ['first.last@[IPv6:1111:2222:3333:4444:5555:12.34.56.78]', false],
            ['first.last@[IPv6:1111:2222:3333:4444:5555:6666:7777:12.34.56.78]', false],
            ['first.last@[IPv6:1111:2222:3333:4444:5555:6666:7777]', false],
            ['first.last@[IPv6:1111:2222:3333:4444:5555:6666:7777:8888:9999]', false],
            ['first.last@[IPv6:1111:2222::3333::4444:5555:6666]', false],
            ['first.last@[IPv6:1111:2222:3333::4444:5555:6666:7777]', false], // [44] true),
            ['first.last@[IPv6:1111:2222:333x::4444:5555]', false],
            ['first.last@[IPv6:1111:2222:33333::4444:5555]', false],
            ['first.last@example.123', false], // [47] true),
            ['first.last@com', false], // [48] true),
            ['first.last@-xample.com', false],
            ['first.last@exampl-.com', false],
            ['first.last@x234567890123456789012345678901234567890123456789012345678901234.iana.org', false],
            ['"Abc\@def"@iana.org', true],
            ['"Fred\ Bloggs"@iana.org', true],
            ['"Joe.\\Blow"@iana.org', true],
            ['"Abc@def"@iana.org', true],
            ['"Fred Bloggs"@iana.org', false], // [56] true),
            ['user+mailbox@iana.org', true],
            ['customer/department=shipping@iana.org', true],
            ['$A12345@iana.org', true],
            ['!def!xyz%abc@iana.org', true],
            ['_somename@iana.org', true],
            ['dclo@us.ibm.com', true],
            ['abc\@def@iana.org', false],
            ['abc\\@iana.org', false],
            ['peter.piper@iana.org', true],
            ['Doug\ \"Ace\"\ Lovell@iana.org', false],
            ['"Doug \"Ace\" L."@iana.org', false], // [67] true),
            ['abc@def@iana.org', false],
            ['abc\\@def@iana.org', false],
            ['abc\@iana.org', false],
            ['@iana.org', false],
            ['doug@', false],
            ['"qu@iana.org', false],
            ['ote"@iana.org', false],
            ['.dot@iana.org', false],
            ['dot.@iana.org', false],
            ['two..dot@iana.org', false],
            ['"Doug "Ace" L."@iana.org', false],
            ['Doug\ \"Ace\"\ L\.@iana.org', false],
            ['hello world@iana.org', false],
            ['gatsby@f.sc.ot.t.f.i.tzg.era.l.d.', false],
            ['test@iana.org', true],
            ['TEST@iana.org', true],
            ['1234567890@iana.org', true],
            ['test+test@iana.org', true],
            ['test-test@iana.org', true],
            ['t*est@iana.org', true],
            ['+1~1+@iana.org', true],
            ['{_test_}@iana.org', true],
            ['"[[ test ]]"@iana.org', false], // [90] true),
            ['test.test@iana.org', true],
            ['"test.test"@iana.org', true],
            ['test."test"@iana.org', true],
            ['"test@test"@iana.org', true],
            ['test@123.123.123.x123', true],
            ['test@123.123.123.123', false], // [96] true),
            ['test@[123.123.123.123]', true],
            ['test@example.iana.org', true],
            ['test@example.example.iana.org', true],
            ['test.iana.org', false],
            ['test.@iana.org', false],
            ['test..test@iana.org', false],
            ['.test@iana.org', false],
            ['test@test@iana.org', false],
            ['test@@iana.org', false],
            ['-- test --@iana.org', false],
            ['[test]@iana.org', false],
            ['"test\test"@iana.org', true],
            ['"test"test"@iana.org', false],
            ['()[]\;:,><@iana.org', false],
            ['test@.', false],
            ['test@example.', false],
            ['test@.org', false],
            ['test@123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012.com', false],
            ['test@example', false], // [115] true),
            ['test@[123.123.123.123', false],
            ['test@123.123.123.123]', false],
            ['NotAnEmail', false],
            ['@NotAnEmail', false],
            ['"test\\blah"@iana.org', true],
            ['"test\blah"@iana.org', true],
            ['"test\&#13;blah"@iana.org', true],
            ['"test&#13;blah"@iana.org', true], // [123] false),
            ['"test\"blah"@iana.org', true],
            ['"test"blah"@iana.org', false],
            ['customer/department@iana.org', true],
            ['_Yosemite.Sam@iana.org', true],
            ['~@iana.org', true],
            ['.wooly@iana.org', false],
            ['wo..oly@iana.org', false],
            ['pootietang.@iana.org', false],
            ['.@iana.org', false],
            ['"Austin@Powers"@iana.org', true],
            ['Ima.Fool@iana.org', true],
            ['"Ima.Fool"@iana.org', true],
            ['"Ima Fool"@iana.org', false], // [136] true),
            ['Ima Fool@iana.org', false],
            ['phil.h\@\@ck@haacked.com', false],
            ['"first"."last"@iana.org', true],
            ['"first".middle."last"@iana.org', true],
            ['"first\\"last"@iana.org', true], // [141] false),
            ['"first".last@iana.org', true],
            ['first."last"@iana.org', true],
            ['"first"."middle"."last"@iana.org', true],
            ['"first.middle"."last"@iana.org', true],
            ['"first.middle.last"@iana.org', true],
            ['"first..last"@iana.org', true],
            ['foo@[\1.2.3.4]', false],
            ['"first\\\"last"@iana.org', false], // [149] true),
            ['first."mid\dle"."last"@iana.org', true],
            ['Test.&#13;&#10; Folding.&#13;&#10; Whitespace@iana.org', false], // [151] true),
            ['first."".last@iana.org', true], // [152] false),
            ['first\last@iana.org', false],
            ['Abc\@def@iana.org', false],
            ['Fred\ Bloggs@iana.org', false],
            ['Joe.\\Blow@iana.org', false],
            ['first.last@[IPv6:1111:2222:3333:4444:5555:6666:12.34.567.89]', false],
            ['"test\&#13;&#10; blah"@iana.org', false],
            ['"test&#13;&#10; blah"@iana.org', false], // [159] true),
            ['{^c\@**Dog^}@cartoon.com', false],
            ['(foo)cal(bar)@(baz)iamcal.com(quux)', false], // [161] true),
            ['cal@iamcal(woo).(yay)com', false], // [162] true),
            ['"foo"(yay)@(hoopla)[1.2.3.4]', false],
            ['cal(woo(yay)hoopla)@iamcal.com', false], // [164] true),
            ['cal(foo\@bar)@iamcal.com', false], // [165] true),
            ['cal(foo\)bar)@iamcal.com', false], // [166] true),
            ['cal(foo(bar)@iamcal.com', false],
            ['cal(foo)bar)@iamcal.com', false],
            ['cal(foo\)@iamcal.com', false],
            ['first().last@iana.org', false], // [170] true),
            ['first.(&#13;&#10; middle&#13;&#10; )last@iana.org', false], // [171] true),
            ['first(12345678901234567890123456789012345678901234567890)last@(1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890)iana.org', false],
            ['first(Welcome to&#13;&#10; the ("wonderful" (!)) world&#13;&#10; of email)@iana.org', false], // [173] true),
            ['pete(his account)@silly.test(his host)', false], // [174] true),
            ['c@(Chris\'s host.)public.example', false], // [175] true),
            ['jdoe@machine(comment).  example', false], // [176] true),
            ['1234   @   local(blah)  .machine .example', false], // [177] true),
            ['first(middle)last@iana.org', false],
            ['first(abc.def).last@iana.org', false], // [179] true),
            ['first(a"bc.def).last@iana.org', false], // [180] true),
            ['first.(")middle.last(")@iana.org', false], // [181] true),
            ['first(abc("def".ghi).mno)middle(abc("def".ghi).mno).last@(abc("def".ghi).mno)example(abc("def".ghi).mno).(abc("def".ghi).mno)com(abc("def".ghi).mno)', false],
            ['first(abc\(def)@iana.org', false], // [183] true),
            ['first.last@x(1234567890123456789012345678901234567890123456789012345678901234567890).com', false], // [184] true),
            ['a(a(b(c)d(e(f))g)h(i)j)@iana.org', false], // [185] true),
            ['a(a(b(c)d(e(f))g)(h(i)j)@iana.org', false],
            ['name.lastname@domain.com', true],
            ['.@', false],
            ['a@b', false], // [189] true),
            ['@bar.com', false],
            ['@@bar.com', false],
            ['a@bar.com', true],
            ['aaa.com', false],
            ['aaa@.com', false],
            ['aaa@.123', false],
            ['aaa@[123.123.123.123]', true],
            ['aaa@[123.123.123.123]a', false],
            ['aaa@[123.123.123.333]', false],
            ['a@bar.com.', false],
            ['a@bar', false], // [200] true),
            ['a-b@bar.com', true],
            ['+@b.c', true],
            ['+@b.com', true],
            ['a@-b.com', false],
            ['a@b-.com', false],
            ['-@..com', false],
            ['-@a..com', false],
            ['a@b.co-foo.uk', true],
            ['"hello my name is"@stutter.com', false], // [209] true),
            ['"Test \"Fail\" Ing"@iana.org', false], // [210] true),
            ['valid@about.museum', true],
            ['invalid@about.museum-', false],
            ['shaitan@my-domain.thisisminekthx', true],
            ['test@...........com', false],
            ['foobar@192.168.0.1', false], // [215] true),
            ['"Joe\\Blow"@iana.org', true],
            ['Invalid \&#10; Folding \&#10; Whitespace@iana.org', false],
            ['HM2Kinsists@(that comments are allowed)this.is.ok', false], // [218] true),
            ['user%uucp!path@berkeley.edu', true],
            ['"first(last)"@iana.org', true],
            [' &#13;&#10; (&#13;&#10; x &#13;&#10; ) &#13;&#10; first&#13;&#10; ( &#13;&#10; x&#13;&#10; ) &#13;&#10; .&#13;&#10; ( &#13;&#10; x) &#13;&#10; last &#13;&#10; (  x &#13;&#10; ) &#13;&#10; @iana.org', false], // [221] true),
            ['first.last @iana.org', false], // [222] true),
            ['test. &#13;&#10; &#13;&#10; obs@syntax.com', false], // [223] true),
            ['test.&#13;&#10;&#13;&#10; obs@syntax.com', false],
            ['"Unicode NULL \␀"@char.com', false], // [225] true),
            ['"Unicode NULL ␀"@char.com', false],
            ['Unicode NULL \␀@char.com', false],
            ['cdburgess+!#$%&\'*-/=?+_{}|~test@gmail.com', true],
            ['first.last@[IPv6:::a2:a3:a4:b1:b2:b3:b4]', false], // [229] true),
            ['first.last@[IPv6:a1:a2:a3:a4:b1:b2:b3::]', false], // [230] true),
            ['first.last@[IPv6::]', false],
            ['first.last@[IPv6:::]', true],
            ['first.last@[IPv6::::]', false],
            ['first.last@[IPv6::b4]', false],
            ['first.last@[IPv6:::b4]', true],
            ['first.last@[IPv6::::b4]', false],
            ['first.last@[IPv6::b3:b4]', false],
            ['first.last@[IPv6:::b3:b4]', true],
            ['first.last@[IPv6::::b3:b4]', false],
            ['first.last@[IPv6:a1::b4]', true],
            ['first.last@[IPv6:a1:::b4]', false],
            ['first.last@[IPv6:a1:]', false],
            ['first.last@[IPv6:a1::]', true],
            ['first.last@[IPv6:a1:::]', false],
            ['first.last@[IPv6:a1:a2:]', false],
            ['first.last@[IPv6:a1:a2::]', true],
            ['first.last@[IPv6:a1:a2:::]', false],
            ['first.last@[IPv6:0123:4567:89ab:cdef::]', true],
            ['first.last@[IPv6:0123:4567:89ab:CDEF::]', true],
            ['first.last@[IPv6:::a3:a4:b1:ffff:11.22.33.44]', true],
            ['first.last@[IPv6:::a2:a3:a4:b1:ffff:11.22.33.44]', false], // [251] true),
            ['first.last@[IPv6:a1:a2:a3:a4::11.22.33.44]', true],
            ['first.last@[IPv6:a1:a2:a3:a4:b1::11.22.33.44]', false], // [253] true),
            ['first.last@[IPv6::11.22.33.44]', false],
            ['first.last@[IPv6::::11.22.33.44]', false],
            ['first.last@[IPv6:a1:11.22.33.44]', false],
            ['first.last@[IPv6:a1::11.22.33.44]', true],
            ['first.last@[IPv6:a1:::11.22.33.44]', false],
            ['first.last@[IPv6:a1:a2::11.22.33.44]', true],
            ['first.last@[IPv6:a1:a2:::11.22.33.44]', false],
            ['first.last@[IPv6:0123:4567:89ab:cdef::11.22.33.44]', true],
            ['first.last@[IPv6:0123:4567:89ab:cdef::11.22.33.xx]', false],
            ['first.last@[IPv6:0123:4567:89ab:CDEF::11.22.33.44]', true],
            ['first.last@[IPv6:0123:4567:89ab:CDEFF::11.22.33.44]', false],
            ['first.last@[IPv6:a1::a4:b1::b4:11.22.33.44]', false],
            ['first.last@[IPv6:a1::11.22.33]', false],
            ['first.last@[IPv6:a1::11.22.33.44.55]', false],
            ['first.last@[IPv6:a1::b211.22.33.44]', false],
            ['first.last@[IPv6:a1::b2:11.22.33.44]', true],
            ['first.last@[IPv6:a1::b2::11.22.33.44]', false],
            ['first.last@[IPv6:a1::b3:]', false],
            ['first.last@[IPv6::a2::b4]', false],
            ['first.last@[IPv6:a1:a2:a3:a4:b1:b2:b3:]', false],
            ['first.last@[IPv6::a2:a3:a4:b1:b2:b3:b4]', false],
            ['first.last@[IPv6:a1:a2:a3:a4::b1:b2:b3:b4]', false],
            ['test@test.com', true],
            ['test@example.com&#10;', false],
            ['test@xn--example.com', true],
            ['test@Bücher.ch', false], // [279] true)
        ];

        return $emailTest;
    }

    #[DataProvider('dataProviderIsEmailAll')]
    public function testIsEmailAll(string $payload, bool $expected): void
    {
        $this->assertEquals(
            $expected,
            \Dotclear\Helper\Text::isEmail($payload)
        );
    }

    public function testDeaccent(): void
    {
        $this->assertEquals(
            'AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss ee',
            \Dotclear\Helper\Text::deaccent('ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß éè')
        );
    }

    public function testStr2URL(): void
    {
        $this->assertEquals(
            'https://domaincom/AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss/eehtml',
            \Dotclear\Helper\Text::str2URL('https://domain.com/ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß/éè.html')
        );
        $this->assertEquals(
            'https:-domaincom-AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss-eehtml',
            \Dotclear\Helper\Text::str2URL('https://domain.com/ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß/éè.html', false)
        );
    }

    public function testTidyURL(): void
    {
        // Keep /, no spaces
        $this->assertEquals(
            'Étrange-et-curieux/À-vous-!',
            \Dotclear\Helper\Text::tidyURL('Étrange et curieux/=À vous !')
        );

        // Keep /, keep spaces
        $this->assertEquals(
            'Étrange et curieux/À vous !',
            \Dotclear\Helper\Text::tidyURL('Étrange et curieux/=À vous !', true, true)
        );

        // No /, keep spaces
        $this->assertEquals(
            'Étrange et curieux-À vous !',
            \Dotclear\Helper\Text::tidyURL('Étrange et curieux/=À vous !', false, true)
        );

        // No /, no spaces
        $this->assertEquals(
            'Étrange-et-curieux-À-vous-!',
            \Dotclear\Helper\Text::tidyURL('Étrange et curieux/=À vous !', false, false)
        );
    }

    public function testcutString(): void
    {
        $faker = Factory::create();
        $text  = $faker->realText(400);

        $this->assertLessThanOrEqual(
            200,
            mb_strlen(\Dotclear\Helper\Text::cutString($text, 200))
        );
        $this->assertEquals(
            'https:-domaincom-AAA',
            \Dotclear\Helper\Text::cutString('https:-domaincom-AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss-eehtml', 20)
        );
        $this->assertEquals(
            'https domaincom',
            \Dotclear\Helper\Text::cutString('https domaincom AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss eehtml', 20)
        );
    }

    public function testSplitWords(): void
    {
        $test = \Dotclear\Helper\Text::splitWords('Étrange et curieux/=À vous !');
        $this->assertCount(
            3,
            $test
        );
        $this->assertEquals(
            'étrange',
            $test[0]
        );
        $this->assertEquals(
            'curieux',
            $test[1]
        );
        $this->assertEquals(
            'vous',
            $test[2]
        );

        $this->assertEmpty(
            \Dotclear\Helper\Text::splitWords(' ')
        );
    }

    public function testDetectEncoding(): void
    {
        $this->assertEquals(
            'utf-8',
            \Dotclear\Helper\Text::detectEncoding('Étrange et curieux/=À vous !')
        );

        $test = mb_convert_encoding('Étrange et curieux/=À vous !', 'ISO-8859-1');
        $this->assertEquals(
            'iso-8859-1',
            \Dotclear\Helper\Text::detectEncoding($test)
        );
    }

    public function testToUTF8(): void
    {
        $this->assertEquals(
            'Étrange et curieux/=À vous !',
            \Dotclear\Helper\Text::toUTF8('Étrange et curieux/=À vous !')
        );

        $test = mb_convert_encoding('Étrange et curieux/=À vous !', 'ISO-8859-1');
        $this->assertEquals(
            'Étrange et curieux/=À vous !',
            \Dotclear\Helper\Text::toUTF8($test)
        );
    }

    /**
     * @return array<array<string, bool|int>>
     */
    public static function dataProviderUtf8badFind(): array
    {
        return [
            // false
            ['', false],
            ['hello world', false],
            ['€', false],
            ["\u{1F600}", false],
            ['こんにちは', false],
            // error position
            [hex2bin('FF'), 0],
            [hex2bin('C080'), 0],
            ['A' . hex2bin('C080') . 'B', 1],
            ['€' . hex2bin('FF'), 3],
            ['ASCII' . '你' . hex2bin('FF') . 'tail', 8],
            ['ASCII' . mb_convert_encoding('你', 'UTF-8', 'UTF-8') . hex2bin('FF') . 'tail', 8],
            [hex2bin('E228A1'), 0],
            [str_repeat('x', 100) . '漢字', false],
        ];
    }

    #[DataProvider('dataProviderUtf8badFind')]
    public function testUtf8badFind(string $input, mixed $expected): void
    {
        $result = \Dotclear\Helper\Text::utf8badFind($input);

        if ($expected === false) {
            $this->assertFalse($result);
        } else {
            // s'assurer du type int pour comparaison stricte
            $this->assertSame($expected, $result);
        }
    }

    public function testCleanUTF8(): void
    {
        $this->assertEquals(
            'Étrange et curieux/=À vous !',
            \Dotclear\Helper\Text::cleanUTF8('Étrange et curieux/=À vous !')
        );
        $this->assertEquals(
            'Étrange et ? curieux/=À vous !',
            \Dotclear\Helper\Text::cleanUTF8('Étrange et ' . hex2bin('FF') . ' curieux/=À vous !')
        );
    }

    public function testCleanStr(): void
    {
        $this->assertEquals(
            'Étrange et curieux/=À vous !',
            \Dotclear\Helper\Text::cleanStr('Étrange et curieux/=À vous !')
        );

        $test = mb_convert_encoding('Étrange et curieux/=À vous !', 'ISO-8859-1');
        $this->assertEquals(
            'Étrange et curieux/=À vous !',
            \Dotclear\Helper\Text::cleanStr($test)
        );
    }

    public function testRemoveDiacritics(): void
    {
        $this->assertEquals(
            'AAAAEAOAUAVAYBCDDZDzEFGHIJKLLJLjMNNJNjOOEOIOOe',
            \Dotclear\Helper\Text::removeDiacritics('&#9398;&#42802;&#508;&#42804;&#42806;&#42810;&#42812;&#7682;&#262;&#393;&#497;&#453;&#518;&#65318;&#500;&#11381;&#407;&#65322;&#7728;&#319;&#455;&#456;&#7742;&#9411;&#458;&#459;&#492;&#338;&#418;&#42830;&#65349;')
        );
    }
}
