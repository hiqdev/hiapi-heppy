<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\heppy\tests\unit\helpers;

use hiapi\heppy\helpers\idn;
use PHPUnit\Framework\TestCase;

/**
 * IDN Utility Class Test
 */
class IdnTest extends TestCase
{
    /**
     * @param string $name
     * @param string $expected
     * @dataProvider detectLangProvider
     */
    public function testDetectLang(string $name, string $expected): void
    {
        $this->assertEquals($expected, idn::detectLang($name));
    }

    public static function detectLangProvider(): array
    {
        return [
            // Cyrillic
            'ukr' => ['тест.укр', 'UKR'],
            'ukr_email' => ['admin@тест.укр', 'UKR'],
            'rus' => ['рыба.рф', 'RUS'],

            // CJK
            'chi' => ['谷歌.中国', 'CHI'],
            'jpn' => ['グーグル.jp', 'JPN'],
            'kor' => ['구글.kr', 'KOR'],

            // Arabic / Persian
            'ara' => ['دبي.امارات', 'ARA'],
            'per' => ['xn--mgbb7fyab.ir', 'PER'], // تهران.ir
            'per_gaf' => ['گاج.com', 'PER'],

            // Cyrillic email local part
            'ukr_cyrillic_email' => ['адмін@домен.укр', 'UKR'],

            // Serbian Cyrillic
            'srp_char' => ['ђоме.com', 'SRP'],
            'srp_tld'  => ['домен.rs',  'SRP'],

            // Mongolian Cyrillic
            'mng' => ['нутаг.mn', 'MNG'],

            // Cyrillic TLD-only
            'rus_tld' => ['домен.ru', 'RUS'],
            'bel_tld' => ['домен.by', 'BEL'],

            // Unique scripts
            'div' => ['ތ.mv',  'DIV'],
            'tib' => ['ཀ.com', 'TIB'],
            'mon' => ['ᠮ.com', 'MON'],
            'yii' => ['ꀀ.com', 'YII'],

            // Nordic (TLD-based)
            'nor' => ['øl.no',      'NOR'],
            'swe' => ['åäö.se',     'SWE'],
            'fin' => ['äiti.fi',    'FIN'],
            'cat' => ['domini.cat', 'CAT'],

            // Plain
            'eng' => ['google.com', 'ENG'],
        ];
    }

}
