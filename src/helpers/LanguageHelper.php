<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

declare(strict_types=1);

namespace hiapi\heppy\helpers;

use LanguageDetection\Language;

/**
 * LanguageHelper
 * Find right language code for domain name
 */
final class LanguageHelper
{
    /**
     * @var self
     */
    private static $instance = null;
    /**
     * @var Language
     */
    private $_detector;

    /**
     * @var array $_replace: key - detected language, value - afilias tag
     */
    private $_replace = [
        'af' => 'AFR', // Afrikaans
        'sq' => 'ALB', // Albanian
        'ar' => 'ARA', // Arabic
        /** ARG - Aragonese */
        'hy' => 'ARM', // Armenian
        /**
          ASM - Assamese
          AST - Asturian
          AVE - Avestan
          AWA - Awadhi
        **/
        'az-Cyrl' => 'AZE', // Azerbaijani Cyr
        'az-Latn' => 'AZE', // Azerbaijani Lat
        /**
          BAN - Balinese
          BAL - Baluchi
          BAS - Basa
          BAK - Bashkir
        **/
        'eu' => 'CAR', //Basque
        'be' => 'BEL', // Belarusian
        'bn' => 'BEN', // Bengali
        /** BHO - Bhojpuri **/
        'bs-Cyrl' => 'BOS', // Bosnian Cyr
        'bs-Latn' => 'BOS', // Bosnian Lat
        'bg' => 'BUL', // Bulgarian
        /**
            BUR - Burmese
            CAR - Carib
        **/
        'ca' => 'CAT', // Catalan
        /** CHE - Chechen **/
        'zh-Hans' => 'CHI', // Chinese
        'zh-Hant' => 'CHI', // Chinese
        /**
            CHV - Chuvash
            COP - Coptic
        **/
        'co' => 'COS', // Corsican
        'hr' => 'SCR', // Croatian
        'cs' => 'CZE', // Czech
        'da' => 'DAN', // Danish
        /**
            DIV - Divehi
            DOI - Dogri
        **/
        'ca' => 'DUT', // Dutch
        'en' => 'ENG', // English
        'et' => 'EST', // Estonian
        'fo' => 'FAO', // Faroese
        'fj' => 'FIJ', // Fijian
        'fi' => 'FIN', // Finnish
        'fr' => 'FRE', // French
        'fy' => 'FRY', // Frisian
        'ga' => 'GLA', // Gaelic
        'gd' => 'GLA', // Gaelic
        /** GEOGeorgian **/
        'de' => 'GER', // German
        /** GON - Gondi **/
        'el-monoton' => 'GRE', // Greek
        'el-polyton' => 'GRE', // Greek
        'gu' => 'GUJ', // Gujarati
        'he' => 'HEB', // Hebrew
        'hi' => 'HIN', // Hindi
        'hu' => 'HUN', // Hungarian
        'is' => 'ICE', // Icelandic
        /** INC - Indic **/
        'id' => 'IND', // Indonesian
        /**
            INH - Ingush
            GLE - Irish
        **/
        'it' => 'ITA', // Italian
        'ja' => 'JPN', // Japanese
        'jv' => 'JAV', // Javanese
        /**
            KAS - Kashmiri
            KAZ - Kazakh
        **/
        'km' => 'KHM', // Khmer
        /** KIR - Kirghiz */
        'ko' => 'KOR', // Korean
        'ku' => 'KUR', // Kurdish
        'lo' => 'LAO', // Lao
        'lv' => 'LAV', // Latvian
        'lt' => 'LIT', // Lithuanian
        /**
            LTZ Luxembourgish
            MAC Macedonian
        **/
        'ms-Arabic' => 'MAY', // Malay
        'ms-Latn' => 'MAY', // Malay
        /** MAL Malayalam **/
        'mt' => 'MLT', // Maltese
        /**
            MAO - Maori
            MOL - Moldavian
        **/
        'mn-Cyrl' => 'MON', // Mongolian
        /** NEP - Nepali **/
        'nb' => 'NOR', // Norwegian
        'nn' => 'NOR', // Norwegian
        /**
            ORI - Oriya
            OSS - Ossetian
            PAN - Panjabi
        **/
        'fa' => 'PER', // Persian
        'pl' => 'POL', // Polish
        'pt-BR' => 'POR', // Portuguese
        'pt-PT' => 'POR', // Portuguese
        /**
            PUS - Pushto
            RAJ - Rajasthani
        **/
        'ro' => 'RUM', // Romanian
        'ru' => 'RUS', // Russian
        /** SMO - Samoan **/
        'sa' => 'SAN', // Sanskrit
        /**
            SRD - Sardinian
            SCC - Serbian
            SND - Sindhi
            SIN - Sinhalese
        **/
        'sk' => 'SLO', // Slovak
        'sl' => 'SLV', // Slovenian
        'so' => 'SOM', // Somali
        'es' => 'SPA', // Spanish
        /** SWA - Swahili **/
        'sv' => 'SWE', // Swedish
        /**
            SYRSyriac
            TGKTajik
            TAMTamil
            TELTelugu
            THAThai
        **/
        'bo' => 'TIB', // Tibetan
        'tr' => 'TUR', // Turkish
        'uk' => 'UKR', // Ukrainian
        'ur' => 'URD', // Urdu
        'uz' => 'UZB', // Uzbek
        'vi' => 'VIE', // Vietnamese
        'cy' => 'WEL', // Welsh
        /** YID - Yiddish **/
    ];

    /**
     * Gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): Singleton
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    private function __construct()
    {
        $this->_detector = new Language;
    }

    /**
     * Detect Verisign tag language
     *
     * @param string
     * @return ?string
     * @throw \Exception
     */
    public function detect(string $str): string
    {
         $detected = (string) $this->_detector->detect($str);

         $tag = $this->getVerisignTag($detected);
         if ($tag !== null) {
             return $tag;
         }

         throw new \Exception('could not detect language');
    }

    /**
     * Get verisign tag by detected lang
     *
     * @param string
     * @return ?string
     */
    public static getVerisignTag(string $tag = ''): ?string
    {
        return $this->_replace[$tag] ?? null;
    }

}
