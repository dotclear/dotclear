<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper;

use Dotclear\Helper\Html\Html;

/**
 * @class Text
 */
class Text
{
    /**
     * Check email address
     *
     * Returns true if $email is a valid email address.
     *
     * @param string    $email    Email string
     */
    public static function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Accents replacement
     *
     * Replaces some occidental accentuated characters by their ASCII
     * representation.
     *
     * @param    string    $str        String to deaccent
     */
    public static function deaccent(string $str): string
    {
        $pattern       = [];
        $pattern['A']  = '\x{00C0}-\x{00C5}';
        $pattern['AE'] = '\x{00C6}';
        $pattern['C']  = '\x{00C7}';
        $pattern['D']  = '\x{00D0}';
        $pattern['E']  = '\x{00C8}-\x{00CB}';
        $pattern['I']  = '\x{00CC}-\x{00CF}';
        $pattern['N']  = '\x{00D1}';
        $pattern['O']  = '\x{00D2}-\x{00D6}\x{00D8}';
        $pattern['OE'] = '\x{0152}';
        $pattern['S']  = '\x{0160}';
        $pattern['U']  = '\x{00D9}-\x{00DC}';
        $pattern['Y']  = '\x{00DD}';
        $pattern['Z']  = '\x{017D}';

        $pattern['a']  = '\x{00E0}-\x{00E5}';
        $pattern['ae'] = '\x{00E6}';
        $pattern['c']  = '\x{00E7}';
        $pattern['d']  = '\x{00F0}';
        $pattern['e']  = '\x{00E8}-\x{00EB}';
        $pattern['i']  = '\x{00EC}-\x{00EF}';
        $pattern['n']  = '\x{00F1}';
        $pattern['o']  = '\x{00F2}-\x{00F6}\x{00F8}';
        $pattern['oe'] = '\x{0153}';
        $pattern['s']  = '\x{0161}';
        $pattern['u']  = '\x{00F9}-\x{00FC}';
        $pattern['y']  = '\x{00FD}\x{00FF}';
        $pattern['z']  = '\x{017E}';

        $pattern['ss'] = '\x{00DF}';

        foreach ($pattern as $r => $p) {
            $str = (string) preg_replace('/[' . $p . ']/u', $r, $str);
        }

        return $str;
    }

    /**
     * String to URL
     *
     * Transforms a string to a proper URL.
     *
     * @param string    $str            String to transform
     * @param bool      $with_slashes   Keep slashes in URL
     */
    public static function str2URL(string $str, bool $with_slashes = true): string
    {
        $str = self::deaccent($str);
        $str = (string) preg_replace('/[^A-Za-z0-9_\s\'\:\/[\]-]/', '', $str);

        return self::tidyURL($str, $with_slashes);
    }

    /**
     * URL cleanup
     *
     * @param string    $str            URL to tidy
     * @param bool      $keep_slashes   Keep slashes in URL
     * @param bool      $keep_spaces    Keep spaces in URL
     */
    public static function tidyURL(string $str, bool $keep_slashes = true, bool $keep_spaces = false): string
    {
        $str = strip_tags($str);
        $str = str_replace(['?', '&', '#', '=', '+', '<', '>', '"', '%'], '', $str);
        $str = str_replace("'", ' ', $str);
        $str = (string) preg_replace('/[\s]+/u', ' ', trim($str));

        if (!$keep_slashes) {
            $str = str_replace('/', '-', $str);
        }

        if (!$keep_spaces) {
            $str = str_replace(' ', '-', $str);
        }

        $str = (string) preg_replace('/\-+/', '-', $str);

        // Remove path changes in URL
        return (string) preg_replace(['%^/%', '%\.+/%'], '', $str);
    }

    /**
     * Cut string
     *
     * Returns a cuted string on spaced at given length $l.
     *
     * @param    string    $str           String to cut
     * @param    integer   $length        Length to keep
     */
    public static function cutString(string $str, int $length): string
    {
        $res             = '';
        $composed_length = 0;

        $parts = preg_split('/([\s]+)/u', $str, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts !== false) {
            if (mb_strlen($parts[0]) >= $length) {
                return mb_substr($parts[0], 0, $length);
            }

            foreach ($parts as $part) {
                $composed_length += mb_strlen($part);
                if ($composed_length > $length) {
                    break;
                }
                $res .= $part;
            }
        }

        return trim($res);
    }

    /**
     * Split words
     *
     * Returns an array of words from a given string.
     *
     * @param string    $str        Words to split
     *
     * @return array<string>
     */
    public static function splitWords(string $str): array
    {
        $non_word = '\x{0000}-\x{002F}\x{003A}-\x{0040}\x{005b}-\x{0060}\x{007B}-\x{007E}\x{00A0}-\x{00BF}\s';
        if (preg_match_all('/([^' . $non_word . ']{3,})/msu', Html::clean($str), $match)) {
            foreach ($match[1] as $i => $v) {
                $match[1][$i] = mb_strtolower($v);
            }

            return $match[1];
        }

        return [];
    }

    /**
     * Encoding detection
     *
     * Returns the encoding (in lowercase) of given $str.
     *
     * @param string    $str        String
     */
    public static function detectEncoding(string $str): string
    {
        return strtolower((string) mb_detect_encoding(
            $str,
            [
                'UTF-8',
                'ISO-8859-1',
                'ISO-8859-2',
                'ISO-8859-3',
                'ISO-8859-4',
                'ISO-8859-5',
                'ISO-8859-6',
                'ISO-8859-7',
                'ISO-8859-8',
                'ISO-8859-9',
                'ISO-8859-10',
                'ISO-8859-13',
                'ISO-8859-14',
                'ISO-8859-15',
            ]
        ));
    }

    /**
     * UTF8 conversions
     *
     * Returns an UTF-8 converted string. If $encoding is not specified, the
     * function will try to detect encoding.
     *
     * @param string    $str         String to convert
     * @param string    $encoding    Optionnal "from" encoding
     */
    public static function toUTF8(string $str, ?string $encoding = null): string
    {
        if (!$encoding) {
            $encoding = self::detectEncoding($str);
        }

        if ($encoding !== 'utf-8') {
            $str = (string) iconv($encoding, 'UTF-8', $str);
        }

        return $str;
    }

    /**
     * Find bad UTF8 tokens
     *
     * Locates the first bad byte in a UTF-8 string returning it's
     * byte index in the string
     * PCRE Pattern to locate bad bytes in a UTF-8 string
     * Comes from W3 FAQ: Multilingual Forms
     * Note: modified to include full ASCII range including control chars
     *
     * @copyright Harry Fuecks (http://phputf8.sourceforge.net <a href="http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html">GNU LGPL 2.1</a>)
     *
     * @param string    $str        String to search
     */
    public static function utf8badFind(string $str): false|int
    {
        $UTF8_BAD = '([\x00-\x7F]' .            // ASCII (including control chars)
        '|[\xC2-\xDF][\x80-\xBF]' .             // non-overlong 2-byte
        '|\xE0[\xA0-\xBF][\x80-\xBF]' .         // excluding overlongs
        '|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}' .  // straight 3-byte
        '|\xED[\x80-\x9F][\x80-\xBF]' .         // excluding surrogates
        '|\xF0[\x90-\xBF][\x80-\xBF]{2}' .      // planes 1-3
        '|[\xF1-\xF3][\x80-\xBF]{3}' .          // planes 4-15
        '|\xF4[\x80-\x8F][\x80-\xBF]{2}' .      // plane 16
        '|(.{1}))';                             // invalid byte
        $pos = 0;

        while (preg_match('/' . $UTF8_BAD . '/S', $str, $matches)) {
            $bytes = strlen($matches[0]);
            if (isset($matches[2])) {
                return $pos;
            }
            $pos += $bytes;
            $str = substr($str, $bytes);
        }

        return false;
    }

    /**
     * UTF-8 cleanup
     *
     * Replaces non UTF-8 bytes in $str by $repl.
     *
     * @copyright Harry Fuecks (http://phputf8.sourceforge.net <a href="http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html">GNU LGPL 2.1</a>)
     *
     * @param string    $str        String to clean
     * @param string    $repl       Replacement string
     */
    public static function cleanUTF8(string $str, string $repl = '?'): string
    {
        while (($bad_index = self::utf8badFind($str)) !== false) {
            $str = substr_replace($str, $repl, $bad_index, 1);
        }

        return $str;
    }

    /**
     * Clean a string
     *
     * @param      string  $str    The string
     */
    public static function cleanStr(string $str): string
    {
        return self::cleanUTF8(self::toUTF8($str));
    }

    /**
     * @var        array<array<string, string>>
     */
    protected static $defaultDiacriticsRemovalMap = [
        [
            'base'    => 'A',
            'letters' => '/(&#65;|&#9398;|&#65313;|&#192;|&#193;|&#194;|&#7846;|&#7844;|&#7850;|&#7848;|&#195;|&#256;|&#258;|&#7856;|&#7854;|&#7860;|&#7858;|&#550;|&#480;|&#196;|&#478;|&#7842;|&#197;|&#506;|&#461;|&#512;|&#514;|&#7840;|&#7852;|&#7862;|&#7680;|&#260;|&#570;|&#11375;|[\x{0041}\x{24B6}\x{FF21}\x{00C0}\x{00C1}\x{00C2}\x{1EA6}\x{1EA4}\x{1EAA}\x{1EA8}\x{00C3}\x{0100}\x{0102}\x{1EB0}\x{1EAE}\x{1EB4}\x{1EB2}\x{0226}\x{01E0}\x{00C4}\x{01DE}\x{1EA2}\x{00C5}\x{01FA}\x{01CD}\x{0200}\x{0202}\x{1EA0}\x{1EAC}\x{1EB6}\x{1E00}\x{0104}\x{023A}\x{2C6F}])/',
        ],
        [
            'base'    => 'AA',
            'letters' => '/(&#42802;|[\x{A732}])/',
        ],
        [
            'base'    => 'AE',
            'letters' => '/(&#198;|&#508;|&#482;|[\x{00C6}\x{01FC}\x{01E2}])/',
        ],
        [
            'base'    => 'AO',
            'letters' => '/(&#42804;|[\x{A734}])/',
        ],
        [
            'base'    => 'AU',
            'letters' => '/(&#42806;|[\x{A736}])/',
        ],
        [
            'base'    => 'AV',
            'letters' => '/(&#42808;|&#42810;|[\x{A738}\x{A73A}])/',
        ],
        [
            'base'    => 'AY',
            'letters' => '/(&#42812;|[\x{A73C}])/',
        ],
        [
            'base'    => 'B',
            'letters' => '/(&#66;|&#9399;|&#65314;|&#7682;|&#7684;|&#7686;|&#579;|&#386;|&#385;|[\x{0042}\x{24B7}\x{FF22}\x{1E02}\x{1E04}\x{1E06}\x{0243}\x{0182}\x{0181}])/',
        ],
        [
            'base'    => 'C',
            'letters' => '/(&#67;|&#9400;|&#65315;|&#262;|&#264;|&#266;|&#268;|&#199;|&#7688;|&#391;|&#571;|&#42814;|[\x{0043}\x{24B8}\x{FF23}\x{0106}\x{0108}\x{010A}\x{010C}\x{00C7}\x{1E08}\x{0187}\x{023B}\x{A73E}])/',
        ],
        [
            'base'    => 'D',
            'letters' => '/(&#68;|&#9401;|&#65316;|&#7690;|&#270;|&#7692;|&#7696;|&#7698;|&#7694;|&#272;|&#395;|&#394;|&#393;|&#42873;|&#208;|[\x{0044}\x{24B9}\x{FF24}\x{1E0A}\x{010E}\x{1E0C}\x{1E10}\x{1E12}\x{1E0E}\x{0110}\x{018B}\x{018A}\x{0189}\x{A779}\x{00D0}])/',
        ],
        [
            'base'    => 'DZ',
            'letters' => '/(&#497;|&#452;|[\x{01F1}\x{01C4}])/',
        ],
        [
            'base'    => 'Dz',
            'letters' => '/(&#498;|&#453;|[\x{01F2}\x{01C5}])/',
        ],
        [
            'base'    => 'E',
            'letters' => '/(&#69;|&#9402;|&#65317;|&#200;|&#201;|&#202;|&#7872;|&#7870;|&#7876;|&#7874;|&#7868;|&#274;|&#7700;|&#7702;|&#276;|&#278;|&#203;|&#7866;|&#282;|&#516;|&#518;|&#7864;|&#7878;|&#552;|&#7708;|&#280;|&#7704;|&#7706;|&#400;|&#398;|[\x{0045}\x{24BA}\x{FF25}\x{00C8}\x{00C9}\x{00CA}\x{1EC0}\x{1EBE}\x{1EC4}\x{1EC2}\x{1EBC}\x{0112}\x{1E14}\x{1E16}\x{0114}\x{0116}\x{00CB}\x{1EBA}\x{011A}\x{0204}\x{0206}\x{1EB8}\x{1EC6}\x{0228}\x{1E1C}\x{0118}\x{1E18}\x{1E1A}\x{0190}\x{018E}])/',
        ],
        [
            'base'    => 'F',
            'letters' => '/(&#70;|&#9403;|&#65318;|&#7710;|&#401;|&#42875;|[\x{0046}\x{24BB}\x{FF26}\x{1E1E}\x{0191}\x{A77B}])/',
        ],
        [
            'base'    => 'G',
            'letters' => '/(&#71;|&#9404;|&#65319;|&#500;|&#284;|&#7712;|&#286;|&#288;|&#486;|&#290;|&#484;|&#403;|&#42912;|&#42877;|&#42878;|[\x{0047}\x{24BC}\x{FF27}\x{01F4}\x{011C}\x{1E20}\x{011E}\x{0120}\x{01E6}\x{0122}\x{01E4}\x{0193}\x{A7A0}\x{A77D}\x{A77E}])/',
        ],
        [
            'base'    => 'H',
            'letters' => '/(&#72;|&#9405;|&#65320;|&#292;|&#7714;|&#7718;|&#542;|&#7716;|&#7720;|&#7722;|&#294;|&#11367;|&#11381;|&#42893;|[\x{0048}\x{24BD}\x{FF28}\x{0124}\x{1E22}\x{1E26}\x{021E}\x{1E24}\x{1E28}\x{1E2A}\x{0126}\x{2C67}\x{2C75}\x{A78D}])/',
        ],
        [
            'base'    => 'I',
            'letters' => '/(&#73;|&#9406;|&#65321;|&#204;|&#205;|&#206;|&#296;|&#298;|&#300;|&#304;|&#207;|&#7726;|&#7880;|&#463;|&#520;|&#522;|&#7882;|&#302;|&#7724;|&#407;|[\x{0049}\x{24BE}\x{FF29}\x{00CC}\x{00CD}\x{00CE}\x{0128}\x{012A}\x{012C}\x{0130}\x{00CF}\x{1E2E}\x{1EC8}\x{01CF}\x{0208}\x{020A}\x{1ECA}\x{012E}\x{1E2C}\x{0197}])/',
        ],
        [
            'base'    => 'J',
            'letters' => '/(&#74;|&#9407;|&#65322;|&#308;|&#584;|[\x{004A}\x{24BF}\x{FF2A}\x{0134}\x{0248}])/',
        ],
        [
            'base'    => 'K',
            'letters' => '/(&#75;|&#9408;|&#65323;|&#7728;|&#488;|&#7730;|&#310;|&#7732;|&#408;|&#11369;|&#42816;|&#42818;|&#42820;|&#42914;|[\x{004B}\x{24C0}\x{FF2B}\x{1E30}\x{01E8}\x{1E32}\x{0136}\x{1E34}\x{0198}\x{2C69}\x{A740}\x{A742}\x{A744}\x{A7A2}])/',
        ],
        [
            'base'    => 'L',
            'letters' => '/(&#76;|&#9409;|&#65324;|&#319;|&#313;|&#317;|&#7734;|&#7736;|&#315;|&#7740;|&#7738;|&#321;|&#573;|&#11362;|&#11360;|&#42824;|&#42822;|&#42880;|[\x{004C}\x{24C1}\x{FF2C}\x{013F}\x{0139}\x{013D}\x{1E36}\x{1E38}\x{013B}\x{1E3C}\x{1E3A}\x{0141}\x{023D}\x{2C62}\x{2C60}\x{A748}\x{A746}\x{A780}])/',
        ],
        [
            'base'    => 'LJ',
            'letters' => '/(&#455;|[\x{01C7}])/',
        ],
        [
            'base'    => 'Lj',
            'letters' => '/(&#456;|[\x{01C8}])/',
        ],
        [
            'base'    => 'M',
            'letters' => '/(&#77;|&#9410;|&#65325;|&#7742;|&#7744;|&#7746;|&#11374;|&#412;|[\x{004D}\x{24C2}\x{FF2D}\x{1E3E}\x{1E40}\x{1E42}\x{2C6E}\x{019C}])/',
        ],
        [
            'base'    => 'N',
            'letters' => '/(&#78;|&#9411;|&#65326;|&#504;|&#323;|&#209;|&#7748;|&#327;|&#7750;|&#325;|&#7754;|&#7752;|&#544;|&#413;|&#42896;|&#42916;|&#330;|[\x{004E}\x{24C3}\x{FF2E}\x{01F8}\x{0143}\x{00D1}\x{1E44}\x{0147}\x{1E46}\x{0145}\x{1E4A}\x{1E48}\x{0220}\x{019D}\x{A790}\x{A7A4}\x{014A}])/',
        ],
        [
            'base'    => 'NJ',
            'letters' => '/(&#458;|[\x{01CA}])/',
        ],
        [
            'base'    => 'Nj',
            'letters' => '/(&#459;|[\x{01CB}])/',
        ],
        [
            'base'    => 'O',
            'letters' => '/(&#79;|&#9412;|&#65327;|&#210;|&#211;|&#212;|&#7890;|&#7888;|&#7894;|&#7892;|&#213;|&#7756;|&#556;|&#7758;|&#332;|&#7760;|&#7762;|&#334;|&#558;|&#560;|&#214;|&#554;|&#7886;|&#336;|&#465;|&#524;|&#526;|&#416;|&#7900;|&#7898;|&#7904;|&#7902;|&#7906;|&#7884;|&#7896;|&#490;|&#492;|&#216;|&#510;|&#390;|&#415;|&#42826;|&#42828;|[\x{004F}\x{24C4}\x{FF2F}\x{00D2}\x{00D3}\x{00D4}\x{1ED2}\x{1ED0}\x{1ED6}\x{1ED4}\x{00D5}\x{1E4C}\x{022C}\x{1E4E}\x{014C}\x{1E50}\x{1E52}\x{014E}\x{022E}\x{0230}\x{00D6}\x{022A}\x{1ECE}\x{0150}\x{01D1}\x{020C}\x{020E}\x{01A0}\x{1EDC}\x{1EDA}\x{1EE0}\x{1EDE}\x{1EE2}\x{1ECC}\x{1ED8}\x{01EA}\x{01EC}\x{00D8}\x{01FE}\x{0186}\x{019F}\x{A74A}\x{A74C}])/',
        ],
        [
            'base'    => 'OE',
            'letters' => '/(&#338;|[\x{0152}])/',
        ],
        [
            'base'    => 'OI',
            'letters' => '/(&#418;|[\x{01A2}])/',
        ],
        [
            'base'    => 'OO',
            'letters' => '/(&#42830;|[\x{A74E}])/',
        ],
        [
            'base'    => 'OU',
            'letters' => '/(&#546;|[\x{0222}])/',
        ],
        [
            'base'    => 'P',
            'letters' => '/(&#80;|&#9413;|&#65328;|&#7764;|&#7766;|&#420;|&#11363;|&#42832;|&#42834;|&#42836;|[\x{0050}\x{24C5}\x{FF30}\x{1E54}\x{1E56}\x{01A4}\x{2C63}\x{A750}\x{A752}\x{A754}])/',
        ],
        [
            'base'    => 'Q',
            'letters' => '/(&#81;|&#9414;|&#65329;|&#42838;|&#42840;|&#586;|[\x{0051}\x{24C6}\x{FF31}\x{A756}\x{A758}\x{024A}])/',
        ],
        [
            'base'    => 'R',
            'letters' => '/(&#82;|&#9415;|&#65330;|&#340;|&#7768;|&#344;|&#528;|&#530;|&#7770;|&#7772;|&#342;|&#7774;|&#588;|&#11364;|&#42842;|&#42918;|&#42882;|[\x{0052}\x{24C7}\x{FF32}\x{0154}\x{1E58}\x{0158}\x{0210}\x{0212}\x{1E5A}\x{1E5C}\x{0156}\x{1E5E}\x{024C}\x{2C64}\x{A75A}\x{A7A6}\x{A782}])/',
        ],
        [
            'base'    => 'S',
            'letters' => '/(&#83;|&#9416;|&#65331;|&#7838;|&#346;|&#7780;|&#348;|&#7776;|&#352;|&#7782;|&#7778;|&#7784;|&#536;|&#350;|&#11390;|&#42920;|&#42884;|[\x{0053}\x{24C8}\x{FF33}\x{1E9E}\x{015A}\x{1E64}\x{015C}\x{1E60}\x{0160}\x{1E66}\x{1E62}\x{1E68}\x{0218}\x{015E}\x{2C7E}\x{A7A8}\x{A784}])/',
        ],
        [
            'base'    => 'T',
            'letters' => '/(&#84;|&#9417;|&#65332;|&#7786;|&#356;|&#7788;|&#538;|&#354;|&#7792;|&#7790;|&#358;|&#428;|&#430;|&#574;|&#42886;|[\x{0054}\x{24C9}\x{FF34}\x{1E6A}\x{0164}\x{1E6C}\x{021A}\x{0162}\x{1E70}\x{1E6E}\x{0166}\x{01AC}\x{01AE}\x{023E}\x{A786}])/',
        ],
        [
            'base'    => 'TH',
            'letters' => '/(&#222;|[\x{00DE}])/',
        ],
        [
            'base'    => 'TZ',
            'letters' => '/(&#42792;|[\x{A728}])/',
        ],
        [
            'base'    => 'U',
            'letters' => '/(&#85;|&#9418;|&#65333;|&#217;|&#218;|&#219;|&#360;|&#7800;|&#362;|&#7802;|&#364;|&#220;|&#475;|&#471;|&#469;|&#473;|&#7910;|&#366;|&#368;|&#467;|&#532;|&#534;|&#431;|&#7914;|&#7912;|&#7918;|&#7916;|&#7920;|&#7908;|&#7794;|&#370;|&#7798;|&#7796;|&#580;|[\x{0055}\x{24CA}\x{FF35}\x{00D9}\x{00DA}\x{00DB}\x{0168}\x{1E78}\x{016A}\x{1E7A}\x{016C}\x{00DC}\x{01DB}\x{01D7}\x{01D5}\x{01D9}\x{1EE6}\x{016E}\x{0170}\x{01D3}\x{0214}\x{0216}\x{01AF}\x{1EEA}\x{1EE8}\x{1EEE}\x{1EEC}\x{1EF0}\x{1EE4}\x{1E72}\x{0172}\x{1E76}\x{1E74}\x{0244}])/',
        ],
        [
            'base'    => 'V',
            'letters' => '/(&#86;|&#9419;|&#65334;|&#7804;|&#7806;|&#434;|&#42846;|&#581;|[\x{0056}\x{24CB}\x{FF36}\x{1E7C}\x{1E7E}\x{01B2}\x{A75E}\x{0245}])/',
        ],
        [
            'base'    => 'VY',
            'letters' => '/(&#42848;|[\x{A760}])/',
        ],
        [
            'base'    => 'W',
            'letters' => '/(&#87;|&#9420;|&#65335;|&#7808;|&#7810;|&#372;|&#7814;|&#7812;|&#7816;|&#11378;|[\x{0057}\x{24CC}\x{FF37}\x{1E80}\x{1E82}\x{0174}\x{1E86}\x{1E84}\x{1E88}\x{2C72}])/',
        ],
        [
            'base'    => 'X',
            'letters' => '/(&#88;|&#9421;|&#65336;|&#7818;|&#7820;|[\x{0058}\x{24CD}\x{FF38}\x{1E8A}\x{1E8C}])/',
        ],
        [
            'base'    => 'Y',
            'letters' => '/(&#89;|&#9422;|&#65337;|&#7922;|&#221;|&#374;|&#7928;|&#562;|&#7822;|&#376;|&#7926;|&#7924;|&#435;|&#590;|&#7934;|[\x{0059}\x{24CE}\x{FF39}\x{1EF2}\x{00DD}\x{0176}\x{1EF8}\x{0232}\x{1E8E}\x{0178}\x{1EF6}\x{1EF4}\x{01B3}\x{024E}\x{1EFE}])/',
        ],
        [
            'base'    => 'Z',
            'letters' => '/(&#90;|&#9423;|&#65338;|&#377;|&#7824;|&#379;|&#381;|&#7826;|&#7828;|&#437;|&#548;|&#11391;|&#11371;|&#42850;|[\x{005A}\x{24CF}\x{FF3A}\x{0179}\x{1E90}\x{017B}\x{017D}\x{1E92}\x{1E94}\x{01B5}\x{0224}\x{2C7F}\x{2C6B}\x{A762}])/',
        ],
        [
            'base'    => 'a',
            'letters' => '/(&#97;|&#9424;|&#65345;|&#7834;|&#224;|&#225;|&#226;|&#7847;|&#7845;|&#7851;|&#7849;|&#227;|&#257;|&#259;|&#7857;|&#7855;|&#7861;|&#7859;|&#551;|&#481;|&#228;|&#479;|&#7843;|&#229;|&#507;|&#462;|&#513;|&#515;|&#7841;|&#7853;|&#7863;|&#7681;|&#261;|&#11365;|&#592;|[\x{0061}\x{24D0}\x{FF41}\x{1E9A}\x{00E0}\x{00E1}\x{00E2}\x{1EA7}\x{1EA5}\x{1EAB}\x{1EA9}\x{00E3}\x{0101}\x{0103}\x{1EB1}\x{1EAF}\x{1EB5}\x{1EB3}\x{0227}\x{01E1}\x{00E4}\x{01DF}\x{1EA3}\x{00E5}\x{01FB}\x{01CE}\x{0201}\x{0203}\x{1EA1}\x{1EAD}\x{1EB7}\x{1E01}\x{0105}\x{2C65}\x{0250}])/',
        ],
        [
            'base'    => 'aa',
            'letters' => '/(&#42803;|[\x{A733}])/',
        ],
        [
            'base'    => 'ae',
            'letters' => '/(&#230;|&#509;|&#483;|[\x{00E6}\x{01FD}\x{01E3}])/',
        ],
        [
            'base'    => 'ao',
            'letters' => '/(&#42805;|[\x{A735}])/',
        ],
        [
            'base'    => 'au',
            'letters' => '/(&#42807;|[\x{A737}])/',
        ],
        [
            'base'    => 'av',
            'letters' => '/(&#42809;|&#42811;|[\x{A739}\x{A73B}])/',
        ],
        [
            'base'    => 'ay',
            'letters' => '/(&#42813;|[\x{A73D}])/',
        ],
        [
            'base'    => 'b',
            'letters' => '/(&#98;|&#9425;|&#65346;|&#7683;|&#7685;|&#7687;|&#384;|&#387;|&#595;|[\x{0062}\x{24D1}\x{FF42}\x{1E03}\x{1E05}\x{1E07}\x{0180}\x{0183}\x{0253}])/',
        ],
        [
            'base'    => 'c',
            'letters' => '/(&#99;|&#9426;|&#65347;|&#263;|&#265;|&#267;|&#269;|&#231;|&#7689;|&#392;|&#572;|&#42815;|&#8580;|[\x{0063}\x{24D2}\x{FF43}\x{0107}\x{0109}\x{010B}\x{010D}\x{00E7}\x{1E09}\x{0188}\x{023C}\x{A73F}\x{2184}])/',
        ],
        [
            'base'    => 'd',
            'letters' => '/(&#100;|&#9427;|&#65348;|&#7691;|&#271;|&#7693;|&#7697;|&#7699;|&#7695;|&#273;|&#396;|&#598;|&#599;|&#42874;|&#240;|[\x{0064}\x{24D3}\x{FF44}\x{1E0B}\x{010F}\x{1E0D}\x{1E11}\x{1E13}\x{1E0F}\x{0111}\x{018C}\x{0256}\x{0257}\x{A77A}\x{00F0}])/',
        ],
        [
            'base'    => 'dz',
            'letters' => '/(&#499;|&#454;|[\x{01F3}\x{01C6}])/',
        ],
        [
            'base'    => 'e',
            'letters' => '/(&#101;|&#9428;|&#65349;|&#232;|&#233;|&#234;|&#7873;|&#7871;|&#7877;|&#7875;|&#7869;|&#275;|&#7701;|&#7703;|&#277;|&#279;|&#235;|&#7867;|&#283;|&#517;|&#519;|&#7865;|&#7879;|&#553;|&#7709;|&#281;|&#7705;|&#7707;|&#583;|&#603;|&#477;|[\x{0065}\x{24D4}\x{FF45}\x{00E8}\x{00E9}\x{00EA}\x{1EC1}\x{1EBF}\x{1EC5}\x{1EC3}\x{1EBD}\x{0113}\x{1E15}\x{1E17}\x{0115}\x{0117}\x{00EB}\x{1EBB}\x{011B}\x{0205}\x{0207}\x{1EB9}\x{1EC7}\x{0229}\x{1E1D}\x{0119}\x{1E19}\x{1E1B}\x{0247}\x{025B}\x{01DD}])/',
        ],
        [
            'base'    => 'f',
            'letters' => '/(&#102;|&#9429;|&#65350;|&#7711;|&#402;|&#42876;|[\x{0066}\x{24D5}\x{FF46}\x{1E1F}\x{0192}\x{A77C}])/',
        ],
        [
            'base'    => 'g',
            'letters' => '/(&#103;|&#9430;|&#65351;|&#501;|&#285;|&#7713;|&#287;|&#289;|&#487;|&#291;|&#485;|&#608;|&#42913;|&#7545;|&#42879;|[\x{0067}\x{24D6}\x{FF47}\x{01F5}\x{011D}\x{1E21}\x{011F}\x{0121}\x{01E7}\x{0123}\x{01E5}\x{0260}\x{A7A1}\x{1D79}\x{A77F}])/',
        ],
        [
            'base'    => 'h',
            'letters' => '/(&#104;|&#9431;|&#65352;|&#293;|&#7715;|&#7719;|&#543;|&#7717;|&#7721;|&#7723;|&#7830;|&#295;|&#11368;|&#11382;|&#613;|[\x{0068}\x{24D7}\x{FF48}\x{0125}\x{1E23}\x{1E27}\x{021F}\x{1E25}\x{1E29}\x{1E2B}\x{1E96}\x{0127}\x{2C68}\x{2C76}\x{0265}])/',
        ],
        [
            'base'    => 'hv',
            'letters' => '/(&#405;|[\x{0195}])/',
        ],
        [
            'base'    => 'i',
            'letters' => '/(&#105;|&#9432;|&#65353;|&#236;|&#237;|&#238;|&#297;|&#299;|&#301;|&#239;|&#7727;|&#7881;|&#464;|&#521;|&#523;|&#7883;|&#303;|&#7725;|&#616;|&#305;|[\x{0069}\x{24D8}\x{FF49}\x{00EC}\x{00ED}\x{00EE}\x{0129}\x{012B}\x{012D}\x{00EF}\x{1E2F}\x{1EC9}\x{01D0}\x{0209}\x{020B}\x{1ECB}\x{012F}\x{1E2D}\x{0268}\x{0131}])/',
        ],
        [
            'base'    => 'ij',
            'letters' => '/(&#307;|[\x{0133}])/',
        ],
        [
            'base'    => 'j',
            'letters' => '/(&#106;|&#9433;|&#65354;|&#309;|&#496;|&#585;|[\x{006A}\x{24D9}\x{FF4A}\x{0135}\x{01F0}\x{0249}])/',
        ],
        [
            'base'    => 'k',
            'letters' => '/(&#107;|&#9434;|&#65355;|&#7729;|&#489;|&#7731;|&#311;|&#7733;|&#409;|&#11370;|&#42817;|&#42819;|&#42821;|&#42915;|[\x{006B}\x{24DA}\x{FF4B}\x{1E31}\x{01E9}\x{1E33}\x{0137}\x{1E35}\x{0199}\x{2C6A}\x{A741}\x{A743}\x{A745}\x{A7A3}])/',
        ],
        [
            'base'    => 'l',
            'letters' => '/(&#108;|&#9435;|&#65356;|&#320;|&#314;|&#318;|&#7735;|&#7737;|&#316;|&#7741;|&#7739;|&#322;|&#410;|&#619;|&#11361;|&#42825;|&#42881;|&#42823;|[\x{006C}\x{24DB}\x{FF4C}\x{0140}\x{013A}\x{013E}\x{1E37}\x{1E39}\x{013C}\x{1E3D}\x{1E3B}\x{0142}\x{019A}\x{026B}\x{2C61}\x{A749}\x{A781}\x{A747}])/',
        ],
        [
            'base'    => 'lj',
            'letters' => '/(&#457;|[\x{01C9}])/',
        ],
        [
            'base'    => 'm',
            'letters' => '/(&#109;|&#9436;|&#65357;|&#7743;|&#7745;|&#7747;|&#625;|&#623;|[\x{006D}\x{24DC}\x{FF4D}\x{1E3F}\x{1E41}\x{1E43}\x{0271}\x{026F}])/',
        ],
        [
            'base'    => 'n',
            'letters' => '/(&#110;|&#9437;|&#65358;|&#505;|&#324;|&#241;|&#7749;|&#328;|&#7751;|&#326;|&#7755;|&#7753;|&#414;|&#626;|&#329;|&#42897;|&#42917;|&#331;|[\x{006E}\x{24DD}\x{FF4E}\x{01F9}\x{0144}\x{00F1}\x{1E45}\x{0148}\x{1E47}\x{0146}\x{1E4B}\x{1E49}\x{019E}\x{0272}\x{0149}\x{A791}\x{A7A5}\x{014B}])/',
        ],
        [
            'base'    => 'nj',
            'letters' => '/(&#460;|[\x{01CC}])/',
        ],
        [
            'base'    => 'o',
            'letters' => '/(&#111;|&#9438;|&#65359;|&#242;|&#243;|&#244;|&#7891;|&#7889;|&#7895;|&#7893;|&#245;|&#7757;|&#557;|&#7759;|&#333;|&#7761;|&#7763;|&#335;|&#559;|&#561;|&#246;|&#555;|&#7887;|&#337;|&#466;|&#525;|&#527;|&#417;|&#7901;|&#7899;|&#7905;|&#7903;|&#7907;|&#7885;|&#7897;|&#491;|&#493;|&#248;|&#511;|&#596;|&#42827;|&#42829;|&#629;|[\x{006F}\x{24DE}\x{FF4F}\x{00F2}\x{00F3}\x{00F4}\x{1ED3}\x{1ED1}\x{1ED7}\x{1ED5}\x{00F5}\x{1E4D}\x{022D}\x{1E4F}\x{014D}\x{1E51}\x{1E53}\x{014F}\x{022F}\x{0231}\x{00F6}\x{022B}\x{1ECF}\x{0151}\x{01D2}\x{020D}\x{020F}\x{01A1}\x{1EDD}\x{1EDB}\x{1EE1}\x{1EDF}\x{1EE3}\x{1ECD}\x{1ED9}\x{01EB}\x{01ED}\x{00F8}\x{01FF}\x{0254}\x{A74B}\x{A74D}\x{0275}])/',
        ],
        [
            'base'    => 'oe',
            'letters' => '/(&#339;|[\x{0153}])/',
        ],
        [
            'base'    => 'oi',
            'letters' => '/(&#419;|[\x{01A3}])/',
        ],
        [
            'base'    => 'ou',
            'letters' => '/(&#547;|[\x{0223}])/',
        ],
        [
            'base'    => 'oo',
            'letters' => '/(&#42831;|[\x{A74F}])/',
        ],
        [
            'base'    => 'p',
            'letters' => '/(&#112;|&#9439;|&#65360;|&#7765;|&#7767;|&#421;|&#7549;|&#42833;|&#42835;|&#42837;|[\x{0070}\x{24DF}\x{FF50}\x{1E55}\x{1E57}\x{01A5}\x{1D7D}\x{A751}\x{A753}\x{A755}])/',
        ],
        [
            'base'    => 'q',
            'letters' => '/(&#113;|&#9440;|&#65361;|&#587;|&#42839;|&#42841;|[\x{0071}\x{24E0}\x{FF51}\x{024B}\x{A757}\x{A759}])/',
        ],
        [
            'base'    => 'r',
            'letters' => '/(&#114;|&#9441;|&#65362;|&#341;|&#7769;|&#345;|&#529;|&#531;|&#7771;|&#7773;|&#343;|&#7775;|&#589;|&#637;|&#42843;|&#42919;|&#42883;|[\x{0072}\x{24E1}\x{FF52}\x{0155}\x{1E59}\x{0159}\x{0211}\x{0213}\x{1E5B}\x{1E5D}\x{0157}\x{1E5F}\x{024D}\x{027D}\x{A75B}\x{A7A7}\x{A783}])/',
        ],
        [
            'base'    => 's',
            'letters' => '/(&#115;|&#9442;|&#65363;|&#347;|&#7781;|&#349;|&#7777;|&#353;|&#7783;|&#7779;|&#7785;|&#537;|&#351;|&#575;|&#42921;|&#42885;|&#7835;|&#383;|[\x{0073}\x{24E2}\x{FF53}\x{015B}\x{1E65}\x{015D}\x{1E61}\x{0161}\x{1E67}\x{1E63}\x{1E69}\x{0219}\x{015F}\x{023F}\x{A7A9}\x{A785}\x{1E9B}\x{017F}])/',
        ],
        [
            'base'    => 'ss',
            'letters' => '/(&#223;|[\x{00DF}])/',
        ],
        [
            'base'    => 't',
            'letters' => '/(&#116;|&#9443;|&#65364;|&#7787;|&#7831;|&#357;|&#7789;|&#539;|&#355;|&#7793;|&#7791;|&#359;|&#429;|&#648;|&#11366;|&#42887;|[\x{0074}\x{24E3}\x{FF54}\x{1E6B}\x{1E97}\x{0165}\x{1E6D}\x{021B}\x{0163}\x{1E71}\x{1E6F}\x{0167}\x{01AD}\x{0288}\x{2C66}\x{A787}])/',
        ],
        [
            'base'    => 'th',
            'letters' => '/(&#254;|[\x{00FE}])/',
        ],
        [
            'base'    => 'tz',
            'letters' => '/(&#42793;|[\x{A729}])/',
        ],
        [
            'base'    => 'u',
            'letters' => '/(&#117;|&#9444;|&#65365;|&#249;|&#250;|&#251;|&#361;|&#7801;|&#363;|&#7803;|&#365;|&#252;|&#476;|&#472;|&#470;|&#474;|&#7911;|&#367;|&#369;|&#468;|&#533;|&#535;|&#432;|&#7915;|&#7913;|&#7919;|&#7917;|&#7921;|&#7909;|&#7795;|&#371;|&#7799;|&#7797;|&#649;|[\x{0075}\x{24E4}\x{FF55}\x{00F9}\x{00FA}\x{00FB}\x{0169}\x{1E79}\x{016B}\x{1E7B}\x{016D}\x{00FC}\x{01DC}\x{01D8}\x{01D6}\x{01DA}\x{1EE7}\x{016F}\x{0171}\x{01D4}\x{0215}\x{0217}\x{01B0}\x{1EEB}\x{1EE9}\x{1EEF}\x{1EED}\x{1EF1}\x{1EE5}\x{1E73}\x{0173}\x{1E77}\x{1E75}\x{0289}])/',
        ],
        [
            'base'    => 'v',
            'letters' => '/(&#118;|&#9445;|&#65366;|&#7805;|&#7807;|&#651;|&#42847;|&#652;|[\x{0076}\x{24E5}\x{FF56}\x{1E7D}\x{1E7F}\x{028B}\x{A75F}\x{028C}])/',
        ],
        [
            'base'    => 'vy',
            'letters' => '/(&#42849;|[\x{A761}])/',
        ],
        [
            'base'    => 'w',
            'letters' => '/(&#119;|&#9446;|&#65367;|&#7809;|&#7811;|&#373;|&#7815;|&#7813;|&#7832;|&#7817;|&#11379;|[\x{0077}\x{24E6}\x{FF57}\x{1E81}\x{1E83}\x{0175}\x{1E87}\x{1E85}\x{1E98}\x{1E89}\x{2C73}])/',
        ],
        [
            'base'    => 'x',
            'letters' => '/(&#120;|&#9447;|&#65368;|&#7819;|&#7821;|[\x{0078}\x{24E7}\x{FF58}\x{1E8B}\x{1E8D}])/',
        ],
        [
            'base'    => 'y',
            'letters' => '/(&#121;|&#9448;|&#65369;|&#7923;|&#253;|&#375;|&#7929;|&#563;|&#7823;|&#255;|&#7927;|&#7833;|&#7925;|&#436;|&#591;|&#7935;|[\x{0079}\x{24E8}\x{FF59}\x{1EF3}\x{00FD}\x{0177}\x{1EF9}\x{0233}\x{1E8F}\x{00FF}\x{1EF7}\x{1E99}\x{1EF5}\x{01B4}\x{024F}\x{1EFF}])/',
        ],
        [
            'base'    => 'z',
            'letters' => '/(&#122;|&#9449;|&#65370;|&#378;|&#7825;|&#380;|&#382;|&#7827;|&#7829;|&#438;|&#549;|&#576;|&#11372;|&#42851;|[\x{007A}\x{24E9}\x{FF5A}\x{017A}\x{1E91}\x{017C}\x{017E}\x{1E93}\x{1E95}\x{01B6}\x{0225}\x{0240}\x{2C6C}\x{A763}])/',
        ],
    ];

    /**
     * Removes diacritics from a string.
     *
     * Removes diacritics from strings containing Latin-1 Supplement, Latin Extended-A,
     * Latin Extended-B and Latin Extended Additional special characters.
     *
     * see https://github.com/infralabs/DiacriticsRemovePHP
     *
     * @param      string  $str    The string
     */
    public static function removeDiacritics(string $str): string
    {
        $counter = count(self::$defaultDiacriticsRemovalMap);
        for ($i = 0; $i < $counter; $i++) {
            $str = (string) preg_replace(
                self::$defaultDiacriticsRemovalMap[$i]['letters'] . 'um',
                self::$defaultDiacriticsRemovalMap[$i]['base'],
                $str
            );
        }

        return $str;
    }
}
