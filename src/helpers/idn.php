<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\heppy\helpers;

/**
 * IDN Utility Class
 */
class idn
{
    /**
     * Detect Language for IDN domain name
     */
    public static function detectLang(string $name): string
    {
        $name = static::toUtf8(static::getDomain($name));
        $name = mb_strtolower($name, 'UTF-8');

        $parts = explode('.', $name);
        $label = $parts[0];
        $tld = end($parts);

        // 1. Cyrillic analysis
        if (preg_match('/\p{Cyrillic}/u', $label)) {
            if (preg_match('/[іїєґ]/u', $label)) return 'UKR';
            if (preg_match('/[ыэъ]/u', $label) || in_array($tld, ['ru', 'рф'], true)) return 'RUS';
            if (preg_match('/[ўі]/u', $label) || in_array($tld, ['by', 'бел'], true)) return 'BEL';
            if (preg_match('/[әіңғөүҷҳқұ]/u', $label) || $tld === 'kz') return 'KAZ';
            if (preg_match('/[ѓѕјљњќџ]/u', $label) || $tld === 'mk') return 'MAC';
            if (preg_match('/[ђћ]/u', $label) || in_array($tld, ['rs', 'рс'], true)) return 'SRP';
            if ($tld === 'bg') return 'BUL';
            if ($tld === 'mn') return 'MNG';
            if (in_array($tld, ['укр', 'ua'], true)) return 'UKR';

            return 'UKR';
        }

        // 2. Specific Scripts (Hex ranges)
        if (preg_match('/[\x{0530}-\x{058F}]/u', $label)) return 'ARM';
        if (preg_match('/[\x{10A0}-\x{10FF}]/u', $label)) return 'GEO';
        if (preg_match('/[\x{0370}-\x{03FF}]/u', $label)) return 'GRE';
        if (preg_match('/[\x{0E00}-\x{0E7F}]/u', $label)) return 'THA';
        if (preg_match('/[\x{0E80}-\x{0EFF}]/u', $label)) return 'LAO';
        if (preg_match('/[\x{1780}-\x{17FF}]/u', $label)) return 'KHM';
        if (preg_match('/[\x{1000}-\x{109F}]/u', $label)) return 'BUR';
        if (preg_match('/[\x{0D80}-\x{0DFF}]/u', $label)) return 'SIN';
        if (preg_match('/[\x{1200}-\x{137F}]/u', $label)) return 'AMH';
        if (preg_match('/[\x{0590}-\x{05FF}]/u', $label)) return 'HEB';
        if (preg_match('/[\x{0780}-\x{07BF}]/u', $label)) return 'DIV';
        if (preg_match('/[\x{0F00}-\x{0FFF}]/u', $label)) return 'TIB';
        if (preg_match('/[\x{1800}-\x{18AF}]/u', $label)) return 'MON';
        if (preg_match('/[\x{A000}-\x{A48F}]/u', $label)) return 'YII';

        // 3. Indic Scripts
        if (preg_match('/[\x{0900}-\x{097F}]/u', $label)) return 'HIN';
        if (preg_match('/[\x{0980}-\x{09FF}]/u', $label)) return 'BEN';
        if (preg_match('/[\x{0B80}-\x{0BFF}]/u', $label)) return 'TAM';
        if (preg_match('/[\x{0A80}-\x{0AFF}]/u', $label)) return 'GUJ';
        if (preg_match('/[\x{0A00}-\x{0A7F}]/u', $label)) return 'PAN';
        if (preg_match('/[\x{0C80}-\x{0CFF}]/u', $label)) return 'KAN';
        if (preg_match('/[\x{0D00}-\x{0D7F}]/u', $label)) return 'MAL';
        if (preg_match('/[\x{0C00}-\x{0C7F}]/u', $label)) return 'TEL';

        // 4. Arabic & Persian
        if (preg_match('/\p{Arabic}/u', $label)) {
            if (preg_match('/[پچژگ]/u', $label) || $tld === 'ir') return 'PER';
            if (preg_match('/[ٹڈڑئے]/u', $label) || $tld === 'pk') return 'URD';
            return 'ARA';
        }

        // 5. CJK analysis
        if (preg_match('/\p{Hangul}/u', $label)) return 'KOR';
        if (preg_match('/\p{Hiragana}|\p{Katakana}/u', $label)) return 'JPN';
        if (preg_match('/\p{Han}/u', $label)) return 'CHI';

        // 6. Latin & Accents
        if (preg_match('/\p{Latin}/u', $label)) {
            // TLD-priority: languages with chars shared with other scripts
            if ($tld === 'no') return 'NOR';
            if ($tld === 'se') return 'SWE';
            if ($tld === 'fi') return 'FIN';
            if ($tld === 'cat') return 'CAT';

            if (preg_match('/[ăằắẳẵặâầấẩẫậêềếểễệôồốổỗộơờớởỡợưừứửữựđ]/u', $label) || $tld === 'vn') return 'VIE';
            if (preg_match('/[àáảãạèéẻẽẹìíỉĩịòóỏõọùúủũụỳýỷỹỵ]/u', $label) && $tld === 'vn') return 'VIE';
            if (preg_match('/[őű]/u', $label) || $tld === 'hu') return 'HUN';
            if (preg_match('/[ğışİı]/u', $label) || $tld === 'tr') return 'TUR';
            if (preg_match('/[ąćęłńóśźż]/u', $label) || $tld === 'pl') return 'POL';
            if (preg_match('/[ðþ]/u', $label) || $tld === 'is') return 'ICE';
            if (preg_match('/[řěňťďů]/u', $label) || $tld === 'cz') return 'CZE';
            if (preg_match('/[ĺľŕôä]/u', $label) || $tld === 'sk') return 'SLO';
            if (preg_match('/[čšžáéíóúý]/u', $label)) return $tld === 'sk' ? 'SLO' : 'CZE';
            if (preg_match('/[įė]/u', $label) || $tld === 'lt') return 'LIT';
            if (preg_match('/[āēģīķļņōŗśųūž]/u', $label) || $tld === 'lv') return 'LAV';
            if (preg_match('/[ąčęėįšųūž]/u', $label)) return 'LIT';
            if (preg_match('/[șță]/u', $label) || $tld === 'ro') return 'RUM';
            if (preg_match('/[ċġħż]/u', $label) || $tld === 'mt') return 'MLT';
            if (preg_match('/[ŵŷ]/u', $label) || in_array($tld, ['wales', 'cymru'], true)) return 'WEL';
            if (preg_match('/[ñ]/u', $label) || $tld === 'es') return 'SPA';
            if (preg_match('/[äöüß]/u', $label) || $tld === 'de') return 'GER';
            if (preg_match('/[àâçéèêëîïôûùÿ]/u', $label) || $tld === 'fr') return 'FRE';
            if (preg_match('/[ã]/u', $label) || $tld === 'pt') return 'POR';
            if (preg_match('/[õ]/u', $label)) return $tld === 'ee' ? 'EST' : 'POR';
            if ($tld === 'ee') return 'EST';
            if (preg_match('/[æøå]/u', $label) || $tld === 'dk') return 'DAN';

            return 'ENG';
        }

        return 'ENG';
    }

    private static function toUtf8(string $name): string
    {
        return self::process(mb_strtolower($name, 'UTF-8'), fn($v) => idn_to_utf8($v, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $v);
    }

    private static function getDomain(string $name): string
    {
        if (strpos($name, '@') !== false) {
            $name = explode('@', $name, 2)[1];
        }
        if (strpos($name, '/') !== false) {
            $name = explode('/', $name, 2)[0];
        }
        return $name;
    }

    private static function process(string $name, callable $callback): string
    {
        if (strpos($name, '@') !== false) {
            [$login, $domain] = explode('@', $name, 2);
            return self::process($login, $callback) . '@' . self::process($domain, $callback);
        }
        if (strpos($name, '/') !== false) {
            [$domain, $path] = explode('/', $name, 2);
            return self::process($domain, $callback) . '/' . $path;
        }
        return $callback($name);
    }
}
