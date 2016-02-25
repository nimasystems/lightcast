<?php

/*
 * Lightcast - A PHP MVC Framework
* Copyright (C) 2005 Nimasystems Ltd
*
* This program is NOT free software; you cannot redistribute and/or modify
* it's sources under any circumstances without the explicit knowledge and
* agreement of the rightful owner of the software - Nimasystems Ltd.
*
* This program is distributed WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
* PURPOSE.  See the LICENSE.txt file for more information.
*
* You should have received a copy of LICENSE.txt file along with this
* program; if not, write to:
* NIMASYSTEMS LTD
* Plovdiv, Bulgaria
* ZIP Code: 4000
* Address: 95 "Kapitan Raycho" Str.
* E-Mail: info@nimasystems.com


*/

class i18nHelper
{
    public static function getSupportedLangCodesString()
    {
        return
            'af|sq|gsw|am|ar|hy|as|az|ba|eu|be|bn|bs|br|bg|ca|zh|co|hr|cs|da|prs|div|' .
            'nl|en|et|fo|fil|fi|fr|fy|gl|ka|de|el|kl|gu|ha|he|hi|hu|is|ig|id|iu|ga|xh|' .
            'zu|it|ja|kn|kk|km|qut|rw|sw|kok|ko|ky|lo|lv|lt|wee|lb|mk|ms|ml|mt|mi|arn|mr|' .
            'moh|mn|ne|nb|nn|oc|or|ps|fa|pl|pt|pa|quz|ro|rm|ru|smn|smj|se|sms|sma|sa|sr|nso|' .
            'tn|si|sk|sl|es|sv|syr|tg|tmz|ta|tt|te|th|bo|tr|tk|ug|uk|wen|ur|uz|vi|cy|wo|sah|ii|yo';
    }

    public static function getDefaultCountryCodeForLangCode($lang_code)
    {
        $l = self::getAll();

        return @$l[1][$lang_code];
    }

    public static function getAll()
    {
        $defaultCountry = array();

        $supportedLanguages = array();

        /* Afrikaans (South Africa) */
        $supportedLanguages['af']['ZA'] = array(
            'description' => 'Afrikaans (South Africa)',
            'title_en' => 'Afrikaans',
            'title' => 'Afrikaans',
            'country' => 'ZAF',
            'langcode' => 'AFK',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0436,
        );
        $defaultCountry['af'] = 'ZA';

        /* Albanian (Albania) */
        $supportedLanguages['sq']['AL'] = array(
            'description' => 'Albanian (Albania)',
            'title_en' => 'Albanian',
            'title' => 'shqipe',
            'country' => 'ALB',
            'langcode' => 'SQI',
            'ansi' => 1250,
            'oem' => 852,
            'lcid' => 0x041C,
        );
        $defaultCountry['sq'] = 'AL';

        /* Alsatian (France) */
        $supportedLanguages['gsw']['FR'] = array(
            'description' => 'Alsatian (France)',
            'title_en' => 'Alsatian',
            'title' => 'Elsässisch',
            'country' => 'FRA',
            'langcode' => 'GSW',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0484,
        );
        $defaultCountry['gsw'] = 'FR';

        /* Amharic (Ethiopia) */
        $supportedLanguages['am']['ET'] = array(
            'description' => 'Amharic (Ethiopia)',
            'title_en' => 'Amharic',
            'title' => 'አማርኛ',
            'country' => 'eth',
            'langcode' => 'AMH',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x045E,
        );
        $defaultCountry['am'] = 'ET';

        /* Arabic (Algeria)‎ */
        $supportedLanguages['ar']['DZ'] = array(
            'description' => 'Arabic (Algeria)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'DZA',
            'langcode' => 'ARG',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x1401,
        );
        $defaultCountry['ar'] = 'SA';

        /* Arabic (Bahrain)‎ */
        $supportedLanguages['ar']['BH'] = array(
            'description' => 'Arabic (Bahrain)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'BHR',
            'langcode' => 'ARH',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x3C01,
        );

        /* Arabic (Egypt)‎ */
        $supportedLanguages['ar']['EG'] = array(
            'description' => 'Arabic (Egypt)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'EGY',
            'langcode' => 'ARE',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x0C01,
        );

        /* Arabic (Iraq)‎ */
        $supportedLanguages['ar']['IQ'] = array(
            'description' => 'Arabic (Iraq)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'IRQ',
            'langcode' => 'ARI',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x0801,
        );

        /* Arabic (Jordan)‎ */
        $supportedLanguages['ar']['JO'] = array(
            'description' => 'Arabic (Jordan)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'JOR',
            'langcode' => 'ARJ',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x2C01,
        );

        /* Arabic (Kuwait)‎ */
        $supportedLanguages['ar']['KW'] = array(
            'description' => 'Arabic (Kuwait)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'KWT',
            'langcode' => 'ARK',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x3401,
        );

        /* Arabic (Lebanon)‎ */
        $supportedLanguages['ar']['LB'] = array(
            'description' => 'Arabic (Lebanon)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'LBN',
            'langcode' => 'ARB',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x3001,
        );

        /* Arabic (Libya)‎ */
        $supportedLanguages['ar']['LY'] = array(
            'description' => 'Arabic (Libya)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'LBY',
            'langcode' => 'ARL',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x1001,
        );

        /* Arabic (Morocco)‎ */
        $supportedLanguages['ar']['MA'] = array(
            'description' => 'Arabic (Morocco)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'MAR',
            'langcode' => 'ARM',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x1801,
        );

        /* Arabic (Oman)‎ */
        $supportedLanguages['ar']['OM'] = array(
            'description' => 'Arabic (Oman)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'OMN',
            'langcode' => 'ARO',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x2001,
        );

        /* Arabic (Qatar)‎ */
        $supportedLanguages['ar']['QA'] = array(
            'description' => 'Arabic (Qatar)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'QAT',
            'langcode' => 'ARQ',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x4001,
        );

        /* Arabic (Saudi Arabia)‎ */
        $supportedLanguages['ar']['SA'] = array(
            'description' => 'Arabic (Saudi Arabia)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'SAU',
            'langcode' => 'ARA',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x0401,
        );

        /* Arabic (Syria)‎ */
        $supportedLanguages['ar']['SY'] = array(
            'description' => 'Arabic (Syria)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'SYR',
            'langcode' => 'ARS',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x2801,
        );

        /* Arabic (Tunisia)‎ */
        $supportedLanguages['ar']['TN'] = array(
            'description' => 'Arabic (Tunisia)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'TUN',
            'langcode' => 'ART',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x1C01,
        );

        /* Arabic (U.A.E.)‎ */
        $supportedLanguages['ar']['AE'] = array(
            'description' => 'Arabic (U.A.E.)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'ARE',
            'langcode' => 'ARU',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x3801,
        );

        /* Arabic (Yemen)‎ */
        $supportedLanguages['ar']['YE'] = array(
            'description' => 'Arabic (Yemen)‎',
            'title_en' => 'Arabic',
            'title' => 'العربية',
            'country' => 'YEM',
            'langcode' => 'ARY',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x2401,
        );

        /* Armenian (Armenia) */
        $supportedLanguages['hy']['AM'] = array(
            'description' => 'Armenian (Armenia)',
            'title_en' => 'Armenian',
            'title' => 'Հայերեն',
            'country' => 'ARM',
            'langcode' => 'HYE',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x042B,
        );
        $defaultCountry['hy'] = 'AM';

        /* Assamese (India) */
        $supportedLanguages['as']['IN'] = array(
            'description' => 'Assamese (India)',
            'title_en' => 'Assamese',
            'title' => 'অসমীয়া',
            'country' => 'IND',
            'langcode' => 'ASM',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x044D,
        );
        $defaultCountry['as'] = 'IN';

        /* Azeri (Cyrillic, Azerbaijan) */
        $supportedLanguages['az']['Cyrl'] = array(
            'description' => 'Azeri (Cyrillic, Azerbaijan)',
            'title_en' => 'Azeri',
            'title' => 'Азәрбајҹан',
            'country' => 'AZE',
            'langcode' => 'AZE',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x082C,
        );
        $defaultCountry['az'] = 'Cyrl';

        /* Azeri (Latin, Azerbaijan) */
        $supportedLanguages['az']['Latn'] = array(
            'description' => 'Azeri (Latin, Azerbaijan)',
            'title_en' => 'Azeri',
            'title' => 'Azərbaycan ılı',
            'country' => 'AZE',
            'langcode' => 'AZE',
            'ansi' => 1254,
            'oem' => 857,
            'lcid' => 0x042C,
        );

        /* Bashkir (Russia) */
        $supportedLanguages['ba']['RU'] = array(
            'description' => 'Bashkir (Russia)',
            'title_en' => 'Bashkir',
            'title' => 'Башҡорт',
            'country' => 'RUS',
            'langcode' => 'BAS',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x046D,
        );
        $defaultCountry['ba'] = 'RU';

        /* Basque (Basque) */
        $supportedLanguages['eu']['ES'] = array(
            'description' => 'Basque (Basque)',
            'title_en' => 'Basque',
            'title' => 'euskara',
            'country' => 'ESP',
            'langcode' => 'EUQ',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x042D,
        );
        $defaultCountry['eu'] = 'ES';

        /* Belarusian (Belarus) */
        $supportedLanguages['be']['BY'] = array(
            'description' => 'Belarusian (Belarus)',
            'title_en' => 'Belarusian',
            'title' => 'Беларускі',
            'country' => 'BLR',
            'langcode' => 'BEL',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x0423,
        );
        $defaultCountry['be'] = 'BY';

        /* Bengali (Bangladesh) */
        $supportedLanguages['bn']['BD'] = array(
            'description' => 'Bengali (Bangladesh)',
            'title_en' => 'Bengali',
            'title' => 'বাংলা',
            'country' => 'BDG',
            'langcode' => 'BNG',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0845,
        );
        $defaultCountry['bn'] = 'BD';

        /* Bengali (India) */
        $supportedLanguages['bn']['IN'] = array(
            'description' => 'Bengali (India)',
            'title_en' => 'Bengali',
            'title' => 'বাংলা',
            'country' => 'IND',
            'langcode' => 'BNG',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0445,
        );

        /* Bosnian (Cyrillic, Bosnia and Herzegovina) */
        $supportedLanguages['bs']['Cyrl'] = array(
            'description' => 'Bosnian (Cyrillic, Bosnia and Herzegovina)',
            'title_en' => 'Bosnian',
            'title' => 'босански',
            'country' => 'BIH',
            'langcode' => 'BSC',
            'ansi' => 1251,
            'oem' => 855,
            'lcid' => 0x201A,
        );
        $defaultCountry['bs'] = 'Cyrl';

        /* Bosnian (Latin, Bosnia and Herzegovina) */
        $supportedLanguages['bs']['Latn'] = array(
            'description' => 'Bosnian (Latin, Bosnia and Herzegovina)',
            'title_en' => 'Bosnian',
            'title' => 'bosanski',
            'country' => 'BIH',
            'langcode' => 'BSB',
            'ansi' => 1250,
            'oem' => 852,
            'lcid' => 0x141A,
        );

        /* Breton (France) */
        $supportedLanguages['br']['FR'] = array(
            'description' => 'Breton (France)',
            'title_en' => 'Breton',
            'title' => 'brezhoneg',
            'country' => 'FRA',
            'langcode' => 'BRE',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x047E,
        );
        $defaultCountry['br'] = 'FR';

        /* Bulgarian (Bulgaria) */
        $supportedLanguages['bg']['BG'] = array(
            'description' => 'Bulgarian (Bulgaria)',
            'title_en' => 'Bulgarian',
            'title' => 'български',
            'country' => 'BGR',
            'langcode' => 'BGR',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x0402,
        );
        $defaultCountry['bg'] = 'BG';

        /* Catalan (Catalan) */
        $supportedLanguages['ca']['ES'] = array(
            'description' => 'Catalan (Catalan)',
            'title_en' => 'Catalan',
            'title' => 'català',
            'country' => 'ESP',
            'langcode' => 'CAT',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0403,
        );
        $defaultCountry['ca'] = 'ES';

        /* Chinese (Hong Kong S.A.R.) */
        $supportedLanguages['zh']['HK'] = array(
            'description' => 'Chinese (Hong Kong S.A.R.)',
            'title_en' => 'Chinese',
            'title' => '中文',
            'country' => 'HKG',
            'langcode' => 'ZHH',
            'ansi' => 950,
            'oem' => 950,
            'lcid' => 0x0C04,
        );
        $defaultCountry['zh'] = 'CN';

        /* Chinese (Macao S.A.R.) */
        $supportedLanguages['zh']['MO'] = array(
            'description' => 'Chinese (Macao S.A.R.)',
            'title_en' => 'Chinese',
            'title' => '中文',
            'country' => 'MCO',
            'langcode' => 'ZHM',
            'ansi' => 950,
            'oem' => 950,
            'lcid' => 0x1404,
        );

        /* Chinese (People's Republic of China) */
        $supportedLanguages['zh']['CN'] = array(
            'description' => 'Chinese (People\'s Republic of China)',
            'title_en' => 'Chinese',
            'title' => '中文',
            'country' => 'CHN',
            'langcode' => 'CHS',
            'ansi' => 936,
            'oem' => 936,
            'lcid' => 0x0804,
        );

        /* Chinese (Simplified) */
        $supportedLanguages['zh']['Hans'] = array(
            'description' => 'Chinese (Simplified)',
            'title_en' => 'Chinese',
            'title' => '中文',
            'country' => 'CHN',
            'langcode' => 'CHS',
            'ansi' => 936,
            'oem' => 936,
            'lcid' => 0x0004,
        );

        /* Chinese (Singapore) */
        $supportedLanguages['zh']['SG'] = array(
            'description' => 'Chinese (Singapore)',
            'title_en' => 'Chinese',
            'title' => '中文',
            'country' => 'SGP',
            'langcode' => 'ZHI',
            'ansi' => 936,
            'oem' => 936,
            'lcid' => 0x1004,
        );

        /* Chinese (Taiwan) */
        $supportedLanguages['zh']['TW'] = array(
            'description' => 'Chinese (Taiwan)',
            'title_en' => 'Chinese',
            'title' => '中文',
            'country' => 'TWN',
            'langcode' => 'CHT',
            'ansi' => 950,
            'oem' => 950,
            'lcid' => 0x0404,
        );

        /* Chinese (Traditional) */
        $supportedLanguages['zh']['Hant'] = array(
            'description' => 'Chinese (Traditional)',
            'title_en' => 'Chinese',
            'title' => '中文',
            'country' => 'TWN',
            'langcode' => 'CHT',
            'ansi' => 950,
            'oem' => 950,
            'lcid' => 0x7C04,
        );

        /* Corsican (France) */
        $supportedLanguages['co']['FR'] = array(
            'description' => 'Corsican (France)',
            'title_en' => 'Corsican',
            'title' => 'Corsu',
            'country' => 'FRA',
            'langcode' => 'COS',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0483,
        );
        $defaultCountry['co'] = 'FR';

        /* Croatian (Croatia) */
        $supportedLanguages['hr']['HR'] = array(
            'description' => 'Croatian (Croatia)',
            'title_en' => 'Croatian',
            'title' => 'hrvatski',
            'country' => 'HRV',
            'langcode' => 'HRV',
            'ansi' => 1250,
            'oem' => 852,
            'lcid' => 0x041A,
        );
        $defaultCountry['hr'] = 'HR';

        /* Croatian (Latin, Bosnia and Herzegovina) */
        $supportedLanguages['hr']['BA'] = array(
            'description' => 'Croatian (Latin, Bosnia and Herzegovina)',
            'title_en' => 'Croatian',
            'title' => 'hrvatski',
            'country' => 'BIH',
            'langcode' => 'HRB',
            'ansi' => 1250,
            'oem' => 852,
            'lcid' => 0x101A,
        );

        /* Czech (Czech Republic) */
        $supportedLanguages['cs']['CZ'] = array(
            'description' => 'Czech (Czech Republic)',
            'title_en' => 'Czech',
            'title' => 'čeština',
            'country' => 'CZE',
            'langcode' => 'CSY',
            'ansi' => 1250,
            'oem' => 852,
            'lcid' => 0x0405,
        );
        $defaultCountry['cs'] = 'CZ';

        /* Danish (Denmark) */
        $supportedLanguages['da']['DK'] = array(
            'description' => 'Danish (Denmark)',
            'title_en' => 'Danish',
            'title' => 'dansk',
            'country' => 'DNK',
            'langcode' => 'DAN',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0406,
        );
        $defaultCountry['da'] = 'DK';

        /* Dari (Afghanistan) */
        $supportedLanguages['prs']['AF'] = array(
            'description' => 'Dari (Afghanistan)',
            'title_en' => 'Dari',
            'title' => 'درى',
            'country' => 'AFG',
            'langcode' => 'PRS',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x048C,
        );
        $defaultCountry['prs'] = 'AF';

        /* Divehi (Maldives)‎ */
        $supportedLanguages['div']['MV'] = array(
            'description' => 'Divehi (Maldives)‎',
            'title_en' => 'Divehi',
            'title' => 'ދިވެހިބަސް',
            'country' => 'MDV',
            'langcode' => 'DIV',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0465,
        );
        $defaultCountry['div'] = 'MV';

        /* Dutch (Belgium) */
        $supportedLanguages['nl']['BE'] = array(
            'description' => 'Dutch (Belgium)',
            'title_en' => 'Dutch',
            'title' => 'Nederlands',
            'country' => 'BEL',
            'langcode' => 'NLB',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0813,
        );
        $defaultCountry['nl'] = 'NL';

        /* Dutch (Netherlands) */
        $supportedLanguages['nl']['NL'] = array(
            'description' => 'Dutch (Netherlands)',
            'title_en' => 'Dutch',
            'title' => 'Nederlands',
            'country' => 'NLD',
            'langcode' => 'NLD',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0413,
        );

        /* English (Australia) */
        $supportedLanguages['en']['AU'] = array(
            'description' => 'English (Australia)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'AUS',
            'langcode' => 'ENA',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0C09,
        );
        $defaultCountry['en'] = 'US';

        /* English (Belize) */
        $supportedLanguages['en']['BZ'] = array(
            'description' => 'English (Belize)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'BLZ',
            'langcode' => 'ENL',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x2809,
        );

        /* English (Canada) */
        $supportedLanguages['en']['CA'] = array(
            'description' => 'English (Canada)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'CAN',
            'langcode' => 'ENC',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x1009,
        );

        /* English (Caribbean) */
        $supportedLanguages['en']['029'] = array(
            'description' => 'English (Caribbean)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'CAR',
            'langcode' => 'ENB',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x2409,
        );

        /* English (India) */
        $supportedLanguages['en']['IN'] = array(
            'description' => 'English (India)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'IND',
            'langcode' => 'ENN',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x4009,
        );

        /* English (Ireland) */
        $supportedLanguages['en']['IE'] = array(
            'description' => 'English (Ireland)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'IRL',
            'langcode' => 'ENI',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x1809,
        );

        /* English (Jamaica) */
        $supportedLanguages['en']['JM'] = array(
            'description' => 'English (Jamaica)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'JAM',
            'langcode' => 'ENJ',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x2009,
        );

        /* English (Malaysia) */
        $supportedLanguages['en']['MY'] = array(
            'description' => 'English (Malaysia)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'MYS',
            'langcode' => 'ENM',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x4409,
        );

        /* English (New Zealand) */
        $supportedLanguages['en']['NZ'] = array(
            'description' => 'English (New Zealand)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'NZL',
            'langcode' => 'ENZ',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x1409,
        );

        /* English (Republic of the Philippines) */
        $supportedLanguages['en']['PH'] = array(
            'description' => 'English (Republic of the Philippines)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'PHL',
            'langcode' => 'ENP',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x3409,
        );

        /* English (Singapore) */
        $supportedLanguages['en']['SG'] = array(
            'description' => 'English (Singapore)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'SGP',
            'langcode' => 'ENE',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x4809,
        );

        /* English (South Africa) */
        $supportedLanguages['en']['ZA'] = array(
            'description' => 'English (South Africa)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'ZAF',
            'langcode' => 'ENS',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x1C09,
        );

        /* English (Trinidad and Tobago) */
        $supportedLanguages['en']['TT'] = array(
            'description' => 'English (Trinidad and Tobago)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'TTO',
            'langcode' => 'ENT',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x2C09,
        );

        /* English (United Kingdom) */
        $supportedLanguages['en']['GB'] = array(
            'description' => 'English (United Kingdom)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'GBR',
            'langcode' => 'ENG',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0809,
        );

        /* English (United States) */
        $supportedLanguages['en']['US'] = array(
            'description' => 'English (United States)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'USA',
            'langcode' => 'ENU',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x0409,
        );

        /* English (Zimbabwe) */
        $supportedLanguages['en']['ZW'] = array(
            'description' => 'English (Zimbabwe)',
            'title_en' => 'English',
            'title' => 'English',
            'country' => 'ZWE',
            'langcode' => 'ENW',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x3009,
        );

        /* Estonian (Estonia) */
        $supportedLanguages['et']['EE'] = array(
            'description' => 'Estonian (Estonia)',
            'title_en' => 'Estonian',
            'title' => 'eesti',
            'country' => 'EST',
            'langcode' => 'ETI',
            'ansi' => 1257,
            'oem' => 775,
            'lcid' => 0x0425,
        );
        $defaultCountry['et'] = 'EE';

        /* Faroese (Faroe Islands) */
        $supportedLanguages['fo']['FO'] = array(
            'description' => 'Faroese (Faroe Islands)',
            'title_en' => 'Faroese',
            'title' => 'føroyskt',
            'country' => 'FRO',
            'langcode' => 'FOS',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0438,
        );
        $defaultCountry['fo'] = 'FO';

        /* Filipino (Philippines) */
        $supportedLanguages['fil']['PH'] = array(
            'description' => 'Filipino (Philippines)',
            'title_en' => 'Filipino',
            'title' => 'Filipino',
            'country' => 'PHL',
            'langcode' => 'FPO',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x0464,
        );
        $defaultCountry['fil'] = 'PH';

        /* Finnish (Finland) */
        $supportedLanguages['fi']['FI'] = array(
            'description' => 'Finnish (Finland)',
            'title_en' => 'Finnish',
            'title' => 'suomi',
            'country' => 'FIN',
            'langcode' => 'FIN',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x040B,
        );
        $defaultCountry['fi'] = 'FI';

        /* French (Belgium) */
        $supportedLanguages['fr']['BE'] = array(
            'description' => 'French (Belgium)',
            'title_en' => 'French',
            'title' => 'français',
            'country' => 'BEL',
            'langcode' => 'FRB',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x080C,
        );
        $defaultCountry['fr'] = 'FR';

        /* French (Canada) */
        $supportedLanguages['fr']['CA'] = array(
            'description' => 'French (Canada)',
            'title_en' => 'French',
            'title' => 'français',
            'country' => 'CAN',
            'langcode' => 'FRC',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0C0C,
        );

        /* French (France) */
        $supportedLanguages['fr']['FR'] = array(
            'description' => 'French (France)',
            'title_en' => 'French',
            'title' => 'français',
            'country' => 'FRA',
            'langcode' => 'FRA',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x040C,
        );

        /* French (Luxembourg) */
        $supportedLanguages['fr']['LU'] = array(
            'description' => 'French (Luxembourg)',
            'title_en' => 'French',
            'title' => 'français',
            'country' => 'LUX',
            'langcode' => 'FRL',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x140C,
        );

        /* French (Principality of Monaco) */
        $supportedLanguages['fr']['MC'] = array(
            'description' => 'French (Principality of Monaco)',
            'title_en' => 'French',
            'title' => 'français',
            'country' => 'MCO',
            'langcode' => 'FRM',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x180C,
        );

        /* French (Switzerland) */
        $supportedLanguages['fr']['CH'] = array(
            'description' => 'French (Switzerland)',
            'title_en' => 'French',
            'title' => 'français',
            'country' => 'CHE',
            'langcode' => 'FRS',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x100C,
        );

        /* Frisian (Netherlands) */
        $supportedLanguages['fy']['NL'] = array(
            'description' => 'Frisian (Netherlands)',
            'title_en' => 'Frisian',
            'title' => 'Frysk',
            'country' => 'NLD',
            'langcode' => 'FYN',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0462,
        );
        $defaultCountry['fy'] = 'NL';

        /* Galician (Galician) */
        $supportedLanguages['gl']['ES'] = array(
            'description' => 'Galician (Galician)',
            'title_en' => 'Galician',
            'title' => 'galego',
            'country' => 'ESP',
            'langcode' => 'GLC',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0456,
        );
        $defaultCountry['gl'] = 'ES';

        /* Georgian (Georgia) */
        $supportedLanguages['ka']['GE'] = array(
            'description' => 'Georgian (Georgia)',
            'title_en' => 'Georgian',
            'title' => 'ქართული',
            'country' => 'GEO',
            'langcode' => 'KAT',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0437,
        );
        $defaultCountry['ka'] = 'GE';

        /* German (Austria) */
        $supportedLanguages['de']['AT'] = array(
            'description' => 'German (Austria)',
            'title_en' => 'German',
            'title' => 'Deutsch',
            'country' => 'AUT',
            'langcode' => 'DEA',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0C07,
        );
        $defaultCountry['de'] = 'DE';

        /* German (Germany) */
        $supportedLanguages['de']['DE'] = array(
            'description' => 'German (Germany)',
            'title_en' => 'German',
            'title' => 'Deutsch',
            'country' => 'DEU',
            'langcode' => 'DEU',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0407,
        );

        /* German (Liechtenstein) */
        $supportedLanguages['de']['LI'] = array(
            'description' => 'German (Liechtenstein)',
            'title_en' => 'German',
            'title' => 'Deutsch',
            'country' => 'LIE',
            'langcode' => 'DEC',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x1407,
        );

        /* German (Luxembourg) */
        $supportedLanguages['de']['LU'] = array(
            'description' => 'German (Luxembourg)',
            'title_en' => 'German',
            'title' => 'Deutsch',
            'country' => 'LUX',
            'langcode' => 'DEL',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x1007,
        );

        /* German (Switzerland) */
        $supportedLanguages['de']['CH'] = array(
            'description' => 'German (Switzerland)',
            'title_en' => 'German',
            'title' => 'Deutsch',
            'country' => 'CHE',
            'langcode' => 'DES',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0807,
        );

        /* Greek (Greece) */
        $supportedLanguages['el']['GR'] = array(
            'description' => 'Greek (Greece)',
            'title_en' => 'Greek',
            'title' => 'ελληνικά',
            'country' => 'GRC',
            'langcode' => 'ELL',
            'ansi' => 1253,
            'oem' => 737,
            'lcid' => 0x0408,
        );
        $defaultCountry['el'] = 'GR';

        /* Greenlandic (Greenland) */
        $supportedLanguages['kl']['GL'] = array(
            'description' => 'Greenlandic (Greenland)',
            'title_en' => 'Greenlandic',
            'title' => 'kalaallisut',
            'country' => 'GRL',
            'langcode' => 'KAL',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x046F,
        );
        $defaultCountry['kl'] = 'GL';

        /* Gujarati (India) */
        $supportedLanguages['gu']['IN'] = array(
            'description' => 'Gujarati (India)',
            'title_en' => 'Gujarati',
            'title' => 'ગુજરાતી',
            'country' => 'IND',
            'langcode' => 'GUJ',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0447,
        );
        $defaultCountry['gu'] = 'IN';

        /* Hausa (Latin, Nigeria) */
        $supportedLanguages['ha']['Latn'] = array(
            'description' => 'Hausa (Latin, Nigeria)',
            'title_en' => 'Hausa',
            'title' => 'Hausa',
            'country' => 'NGA',
            'langcode' => 'HAU',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x0468,
        );
        $defaultCountry['ha'] = 'Latn';

        /* Hebrew (Israel)‎ */
        $supportedLanguages['he']['IL'] = array(
            'description' => 'Hebrew (Israel)‎',
            'title_en' => 'Hebrew',
            'title' => 'עברית',
            'country' => 'ISR',
            'langcode' => 'HEB',
            'ansi' => 1255,
            'oem' => 862,
            'lcid' => 0x040D,
        );
        $defaultCountry['he'] = 'IL';

        /* Hindi (India) */
        $supportedLanguages['hi']['IN'] = array(
            'description' => 'Hindi (India)',
            'title_en' => 'Hindi',
            'title' => 'हिंदी',
            'country' => 'IND',
            'langcode' => 'HIN',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0439,
        );
        $defaultCountry['hi'] = 'IN';

        /* Hungarian (Hungary) */
        $supportedLanguages['hu']['HU'] = array(
            'description' => 'Hungarian (Hungary)',
            'title_en' => 'Hungarian',
            'title' => 'magyar',
            'country' => 'HUN',
            'langcode' => 'HUN',
            'ansi' => 1250,
            'oem' => 852,
            'lcid' => 0x040E,
        );
        $defaultCountry['hu'] = 'HU';

        /* Icelandic (Iceland) */
        $supportedLanguages['is']['IS'] = array(
            'description' => 'Icelandic (Iceland)',
            'title_en' => 'Icelandic',
            'title' => 'íslenska',
            'country' => 'ISL',
            'langcode' => 'ISL',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x040F,
        );
        $defaultCountry['is'] = 'IS';

        /* Igbo (Nigeria) */
        $supportedLanguages['ig']['NG'] = array(
            'description' => 'Igbo (Nigeria)',
            'title_en' => 'Igbo',
            'title' => 'Igbo',
            'country' => 'NGA',
            'langcode' => 'IBO',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x0470,
        );
        $defaultCountry['ig'] = 'NG';

        /* Indonesian (Indonesia) */
        $supportedLanguages['id']['ID'] = array(
            'description' => 'Indonesian (Indonesia)',
            'title_en' => 'Indonesian',
            'title' => 'Bahasa Indonesia',
            'country' => 'IDN',
            'langcode' => 'IND',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0421,
        );
        $defaultCountry['id'] = 'ID';

        /* Inuktitut (Latin, Canada) */
        $supportedLanguages['iu']['Latn'] = array(
            'description' => 'Inuktitut (Latin, Canada)',
            'title_en' => 'Inuktitut',
            'title' => 'Inuktitut',
            'country' => 'CAN',
            'langcode' => 'IUK',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x085D,
        );
        $defaultCountry['iu'] = 'Latn';

        /* Inuktitut (Syllabics, Canada) */
        $supportedLanguages['iu']['Cans'] = array(
            'description' => 'Inuktitut (Syllabics, Canada)',
            'title_en' => 'Inuktitut',
            'title' => 'ᐃᓄᒃᑎᑐᑦ',
            'country' => 'CAN',
            'langcode' => 'IUS',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x045D,
        );

        /* Irish (Ireland) */
        $supportedLanguages['ga']['IE'] = array(
            'description' => 'Irish (Ireland)',
            'title_en' => 'Irish',
            'title' => 'Gaeilge',
            'country' => 'IRL',
            'langcode' => 'IRE',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x083C,
        );
        $defaultCountry['ga'] = 'IE';

        /* isiXhosa (South Africa) */
        $supportedLanguages['xh']['ZA'] = array(
            'description' => 'isiXhosa (South Africa)',
            'title_en' => 'isiXhosa',
            'title' => 'isiXhosa',
            'country' => 'ZAF',
            'langcode' => 'XHO',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0434,
        );
        $defaultCountry['xh'] = 'ZA';

        /* isiZulu (South Africa) */
        $supportedLanguages['zu']['ZA'] = array(
            'description' => 'isiZulu (South Africa)',
            'title_en' => 'isiZulu',
            'title' => 'isiZulu',
            'country' => 'ZAF',
            'langcode' => 'ZUL',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0435,
        );
        $defaultCountry['zu'] = 'ZA';

        /* Italian (Italy) */
        $supportedLanguages['it']['IT'] = array(
            'description' => 'Italian (Italy)',
            'title_en' => 'Italian',
            'title' => 'italiano',
            'country' => 'ITA',
            'langcode' => 'ITA',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0410,
        );
        $defaultCountry['it'] = 'IT';

        /* Italian (Switzerland) */
        $supportedLanguages['it']['CH'] = array(
            'description' => 'Italian (Switzerland)',
            'title_en' => 'Italian',
            'title' => 'italiano',
            'country' => 'CHE',
            'langcode' => 'ITS',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0810,
        );

        /* Japanese (Japan) */
        $supportedLanguages['ja']['JP'] = array(
            'description' => 'Japanese (Japan)',
            'title_en' => 'Japanese',
            'title' => '日本語',
            'country' => 'JPN',
            'langcode' => 'JPN',
            'ansi' => 932,
            'oem' => 932,
            'lcid' => 0x0411,
        );
        $defaultCountry['ja'] = 'JP';

        /* Kannada (India) */
        $supportedLanguages['kn']['IN'] = array(
            'description' => 'Kannada (India)',
            'title_en' => 'Kannada',
            'title' => 'ಕನ್ನಡ',
            'country' => 'IND',
            'langcode' => 'KDI',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x044B,
        );
        $defaultCountry['kn'] = 'IN';

        /* Kazakh (Kazakhstan) */
        $supportedLanguages['kk']['KZ'] = array(
            'description' => 'Kazakh (Kazakhstan)',
            'title_en' => 'Kazakh',
            'title' => 'Қазақ',
            'country' => 'KAZ',
            'langcode' => 'KKZ',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x043F,
        );
        $defaultCountry['kk'] = 'KZ';

        /* Khmer (Cambodia) */
        $supportedLanguages['km']['KH'] = array(
            'description' => 'Khmer (Cambodia)',
            'title_en' => 'Khmer',
            'title' => 'ខ្មែរ',
            'country' => 'KHM',
            'langcode' => 'KHM',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0453,
        );
        $defaultCountry['km'] = 'KH';

        /* K'iche (Guatemala) */
        $supportedLanguages['qut']['GT'] = array(
            'description' => 'K\'iche (Guatemala)',
            'title_en' => 'K\'iche',
            'title' => 'K\'iche',
            'country' => 'GTM',
            'langcode' => 'QUT',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0486,
        );
        $defaultCountry['qut'] = 'GT';

        /* Kinyarwanda (Rwanda) */
        $supportedLanguages['rw']['RW'] = array(
            'description' => 'Kinyarwanda (Rwanda)',
            'title_en' => 'Kinyarwanda',
            'title' => 'Kinyarwanda',
            'country' => 'RWA',
            'langcode' => 'KIN',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x0487,
        );
        $defaultCountry['rw'] = 'RW';

        /* Kiswahili (Kenya) */
        $supportedLanguages['sw']['KE'] = array(
            'description' => 'Kiswahili (Kenya)',
            'title_en' => 'Kiswahili',
            'title' => 'Kiswahili',
            'country' => 'KEN',
            'langcode' => 'SWK',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x0441,
        );
        $defaultCountry['sw'] = 'KE';

        /* Konkani (India) */
        $supportedLanguages['kok']['IN'] = array(
            'description' => 'Konkani (India)',
            'title_en' => 'Konkani',
            'title' => 'कोंकणी',
            'country' => 'IND',
            'langcode' => 'KNK',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0457,
        );
        $defaultCountry['kok'] = 'IN';

        /* Korean (Korea) */
        $supportedLanguages['ko']['KR'] = array(
            'description' => 'Korean (Korea)',
            'title_en' => 'Korean',
            'title' => '한국어',
            'country' => 'KOR',
            'langcode' => 'KOR',
            'ansi' => 949,
            'oem' => 949,
            'lcid' => 0x0412,
        );
        $defaultCountry['ko'] = 'KR';

        /* Kyrgyz (Kyrgyzstan) */
        $supportedLanguages['ky']['KG'] = array(
            'description' => 'Kyrgyz (Kyrgyzstan)',
            'title_en' => 'Kyrgyz',
            'title' => 'Кыргыз',
            'country' => 'KGZ',
            'langcode' => 'KYR',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x0440,
        );
        $defaultCountry['ky'] = 'KG';

        /* Lao (Lao P.D.R.) */
        $supportedLanguages['lo']['LA'] = array(
            'description' => 'Lao (Lao P.D.R.)',
            'title_en' => 'Lao',
            'title' => 'ລາວ',
            'country' => 'LAO',
            'langcode' => 'LAO',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0454,
        );
        $defaultCountry['lo'] = 'LA';

        /* Latvian (Latvia) */
        $supportedLanguages['lv']['LV'] = array(
            'description' => 'Latvian (Latvia)',
            'title_en' => 'Latvian',
            'title' => 'latviešu',
            'country' => 'LVA',
            'langcode' => 'LVI',
            'ansi' => 1257,
            'oem' => 775,
            'lcid' => 0x0426,
        );
        $defaultCountry['lv'] = 'LV';

        /* Lithuanian (Lithuania) */
        $supportedLanguages['lt']['LT'] = array(
            'description' => 'Lithuanian (Lithuania)',
            'title_en' => 'Lithuanian',
            'title' => 'lietuvių',
            'country' => 'LTU',
            'langcode' => 'LTH',
            'ansi' => 1257,
            'oem' => 775,
            'lcid' => 0x0427,
        );
        $defaultCountry['lt'] = 'LT';

        /* Lower Sorbian (Germany) */
        $supportedLanguages['wee']['DE'] = array(
            'description' => 'Lower Sorbian (Germany)',
            'title_en' => 'Lower Sorbian',
            'title' => 'dolnoserbšćina',
            'country' => 'GER',
            'langcode' => 'DSB',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x082E,
        );
        $defaultCountry['wee'] = 'DE';

        /* Luxembourgish (Luxembourg) */
        $supportedLanguages['lb']['LU'] = array(
            'description' => 'Luxembourgish (Luxembourg)',
            'title_en' => 'Luxembourgish',
            'title' => 'Lëtzebuergesch',
            'country' => 'LUX',
            'langcode' => 'LBX',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x046E,
        );
        $defaultCountry['lb'] = 'LU';

        /* Macedonian (Former Yugoslav Republic of Macedonia) */
        $supportedLanguages['mk']['MK'] = array(
            'description' => 'Macedonian (Former Yugoslav Republic of Macedonia)',
            'title_en' => 'Macedonian',
            'title' => 'македонски јазик',
            'country' => 'MKD',
            'langcode' => 'MKI',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x042F,
        );
        $defaultCountry['mk'] = 'MK';

        /* Malay (Brunei Darussalam) */
        $supportedLanguages['ms']['BN'] = array(
            'description' => 'Malay (Brunei Darussalam)',
            'title_en' => 'Malay',
            'title' => 'Bahasa Malaysia',
            'country' => 'BRN',
            'langcode' => 'MSB',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x083E,
        );
        $defaultCountry['ms'] = 'BN';

        /* Malay (Malaysia) */
        $supportedLanguages['ms']['MY'] = array(
            'description' => 'Malay (Malaysia)',
            'title_en' => 'Malay',
            'title' => 'Bahasa Malaysia',
            'country' => 'MYS',
            'langcode' => 'MSL',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x043E,
        );

        /* Malayalam (India) */
        $supportedLanguages['ml']['IN'] = array(
            'description' => 'Malayalam (India)',
            'title_en' => 'Malayalam',
            'title' => 'മലയാളം',
            'country' => 'IND',
            'langcode' => 'MYM',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x044C,
        );
        $defaultCountry['ml'] = 'IN';

        /* Maltese (Malta) */
        $supportedLanguages['mt']['MT'] = array(
            'description' => 'Maltese (Malta)',
            'title_en' => 'Maltese',
            'title' => 'Malti',
            'country' => 'MLT',
            'langcode' => 'MLT',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x043A,
        );
        $defaultCountry['mt'] = 'MT';

        /* Maori (New Zealand) */
        $supportedLanguages['mi']['NZ'] = array(
            'description' => 'Maori (New Zealand)',
            'title_en' => 'Maori',
            'title' => 'Reo Māori',
            'country' => 'NZL',
            'langcode' => 'MRI',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0481,
        );
        $defaultCountry['mi'] = 'NZ';

        /* Mapudungun (Chile) */
        $supportedLanguages['arn']['CL'] = array(
            'description' => 'Mapudungun (Chile)',
            'title_en' => 'Mapudungun',
            'title' => 'Mapudungun',
            'country' => 'CHL',
            'langcode' => 'MPD',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x047A,
        );
        $defaultCountry['arn'] = 'CL';

        /* Marathi (India) */
        $supportedLanguages['mr']['IN'] = array(
            'description' => 'Marathi (India)',
            'title_en' => 'Marathi',
            'title' => 'मराठी',
            'country' => 'IND',
            'langcode' => 'MAR',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x044E,
        );
        $defaultCountry['mr'] = 'IN';

        /* Mohawk (Mohawk) */
        $supportedLanguages['moh']['CA'] = array(
            'description' => 'Mohawk (Mohawk)',
            'title_en' => 'Mohawk',
            'title' => 'Kanien\'kéha',
            'country' => 'CAN',
            'langcode' => 'MWK',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x047C,
        );
        $defaultCountry['moh'] = 'CA';

        /* Mongolian (Cyrillic, Mongolia) */
        $supportedLanguages['mn']['MN'] = array(
            'description' => 'Mongolian (Cyrillic, Mongolia)',
            'title_en' => 'Mongolian',
            'title' => 'Монгол хэл',
            'country' => 'MNG',
            'langcode' => 'MON',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x0450,
        );
        $defaultCountry['mn'] = 'MN';

        /* Mongolian (Traditional Mongolian, PRC) */
        $supportedLanguages['mn']['Mong'] = array(
            'description' => 'Mongolian (Traditional Mongolian, PRC)',
            'title_en' => 'Mongolian',
            'title' => 'ᠮᠣᠩᠭᠤᠯ ᠬᠡᠯᠡ',
            'country' => 'CHN',
            'langcode' => 'MNG',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0850,
        );

        /* Nepali (Nepal) */
        $supportedLanguages['ne']['NP'] = array(
            'description' => 'Nepali (Nepal)',
            'title_en' => 'Nepali',
            'title' => 'नेपाली',
            'country' => 'NEP',
            'langcode' => 'NEP',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0461,
        );
        $defaultCountry['ne'] = 'NP';

        /* Norwegian, Bokmål (Norway) */
        $supportedLanguages['nb']['NO'] = array(
            'description' => 'Norwegian, Bokmål (Norway)',
            'title_en' => 'Norwegian',
            'title' => 'norsk, bokmål',
            'country' => 'NOR',
            'langcode' => 'NOR',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0414,
        );
        $defaultCountry['nb'] = 'NO';

        /* Norwegian, Nynorsk (Norway) */
        $supportedLanguages['nn']['NO'] = array(
            'description' => 'Norwegian, Nynorsk (Norway)',
            'title_en' => 'Norwegian',
            'title' => 'norsk, nynorsk',
            'country' => 'NOR',
            'langcode' => 'NON',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0814,
        );
        $defaultCountry['nn'] = 'NO';

        /* Occitan (France) */
        $supportedLanguages['oc']['FR'] = array(
            'description' => 'Occitan (France)',
            'title_en' => 'Occitan',
            'title' => 'Occitan',
            'country' => 'FRA',
            'langcode' => 'OCI',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0482,
        );
        $defaultCountry['oc'] = 'FR';

        /* Oriya (India) */
        $supportedLanguages['or']['IN'] = array(
            'description' => 'Oriya (India)',
            'title_en' => 'Oriya',
            'title' => 'ଓଡ଼ିଆ',
            'country' => 'IND',
            'langcode' => 'ORI',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0448,
        );
        $defaultCountry['or'] = 'IN';

        /* Pashto (Afghanistan) */
        $supportedLanguages['ps']['AF'] = array(
            'description' => 'Pashto (Afghanistan)',
            'title_en' => 'Pashto',
            'title' => 'پښتو',
            'country' => 'AFG',
            'langcode' => 'PAS',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0463,
        );
        $defaultCountry['ps'] = 'AF';

        /* Persian‎ */
        $supportedLanguages['fa']['IR'] = array(
            'description' => 'Persian‎',
            'title_en' => 'Persian',
            'title' => 'فارسى',
            'country' => 'IRN',
            'langcode' => 'FAR',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x0429,
        );
        $defaultCountry['fa'] = 'IR';

        /* Polish (Poland) */
        $supportedLanguages['pl']['PL'] = array(
            'description' => 'Polish (Poland)',
            'title_en' => 'Polish',
            'title' => 'polski',
            'country' => 'POL',
            'langcode' => 'PLK',
            'ansi' => 1250,
            'oem' => 852,
            'lcid' => 0x0415,
        );
        $defaultCountry['pl'] = 'PL';

        /* Portuguese (Brazil) */
        $supportedLanguages['pt']['BR'] = array(
            'description' => 'Portuguese (Brazil)',
            'title_en' => 'Portuguese',
            'title' => 'Português',
            'country' => 'BRA',
            'langcode' => 'PTB',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0416,
        );
        $defaultCountry['pt'] = 'BR';

        /* Portuguese (Portugal) */
        $supportedLanguages['pt']['PT'] = array(
            'description' => 'Portuguese (Portugal)',
            'title_en' => 'Portuguese',
            'title' => 'português',
            'country' => 'PRT',
            'langcode' => 'PTG',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0816,
        );

        /* Punjabi (India) */
        $supportedLanguages['pa']['IN'] = array(
            'description' => 'Punjabi (India)',
            'title_en' => 'Punjabi',
            'title' => 'ਪੰਜਾਬੀ',
            'country' => 'IND',
            'langcode' => 'PAN',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0446,
        );
        $defaultCountry['pa'] = 'IN';

        /* Quechua (Bolivia) */
        $supportedLanguages['quz']['BO'] = array(
            'description' => 'Quechua (Bolivia)',
            'title_en' => 'Quechua',
            'title' => 'runasimi',
            'country' => 'BOL',
            'langcode' => 'QUB',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x046B,
        );
        $defaultCountry['quz'] = 'BO';

        /* Quechua (Ecuador) */
        $supportedLanguages['quz']['EC'] = array(
            'description' => 'Quechua (Ecuador)',
            'title_en' => 'Quechua',
            'title' => 'runasimi',
            'country' => 'ECU',
            'langcode' => 'QUE',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x086B,
        );

        /* Quechua (Peru) */
        $supportedLanguages['quz']['PE'] = array(
            'description' => 'Quechua (Peru)',
            'title_en' => 'Quechua',
            'title' => 'runasimi',
            'country' => 'PER',
            'langcode' => 'QUP',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0C6B,
        );

        /* Romanian (Romania) */
        $supportedLanguages['ro']['RO'] = array(
            'description' => 'Romanian (Romania)',
            'title_en' => 'Romanian',
            'title' => 'română',
            'country' => 'ROM',
            'langcode' => 'ROM',
            'ansi' => 1250,
            'oem' => 852,
            'lcid' => 0x0418,
        );
        $defaultCountry['ro'] = 'RO';

        /* Romansh (Switzerland) */
        $supportedLanguages['rm']['CH'] = array(
            'description' => 'Romansh (Switzerland)',
            'title_en' => 'Romansh',
            'title' => 'Rumantsch',
            'country' => 'CHE',
            'langcode' => 'RMC',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0417,
        );
        $defaultCountry['rm'] = 'CH';

        /* Russian (Russia) */
        $supportedLanguages['ru']['RU'] = array(
            'description' => 'Russian (Russia)',
            'title_en' => 'Russian',
            'title' => 'русский',
            'country' => 'RUS',
            'langcode' => 'RUS',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x0419,
        );
        $defaultCountry['ru'] = 'RU';

        /* Sami, Inari (Finland) */
        $supportedLanguages['smn']['FI'] = array(
            'description' => 'Sami, Inari (Finland)',
            'title_en' => 'Sami',
            'title' => 'sämikielâ',
            'country' => 'FIN',
            'langcode' => 'SMN',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x243B,
        );
        $defaultCountry['smn'] = 'FI';

        /* Sami, Lule (Norway) */
        $supportedLanguages['smj']['NO'] = array(
            'description' => 'Sami, Lule (Norway)',
            'title_en' => 'Sami',
            'title' => 'julevusámegiella',
            'country' => 'NOR',
            'langcode' => 'SMJ',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x103B,
        );
        $defaultCountry['smj'] = 'NO';

        /* Sami, Lule (Sweden) */
        $supportedLanguages['smj']['SE'] = array(
            'description' => 'Sami, Lule (Sweden)',
            'title_en' => 'Sami',
            'title' => 'julevusámegiella',
            'country' => 'SWE',
            'langcode' => 'SMK',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x143B,
        );

        /* Sami, Northern (Finland) */
        $supportedLanguages['se']['FI'] = array(
            'description' => 'Sami, Northern (Finland)',
            'title_en' => 'Sami',
            'title' => 'davvisámegiella',
            'country' => 'FIN',
            'langcode' => 'SMG',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0C3B,
        );
        $defaultCountry['se'] = 'SE';

        /* Sami, Northern (Norway) */
        $supportedLanguages['se']['NO'] = array(
            'description' => 'Sami, Northern (Norway)',
            'title_en' => 'Sami',
            'title' => 'davvisámegiella',
            'country' => 'NOR',
            'langcode' => 'SME',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x043B,
        );

        /* Sami, Northern (Sweden) */
        $supportedLanguages['se']['SE'] = array(
            'description' => 'Sami, Northern (Sweden)',
            'title_en' => 'Sami',
            'title' => 'davvisámegiella',
            'country' => 'SWE',
            'langcode' => 'SMF',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x083B,
        );

        /* Sami, Skolt (Finland) */
        $supportedLanguages['sms']['FI'] = array(
            'description' => 'Sami, Skolt (Finland)',
            'title_en' => 'Sami',
            'title' => 'sääm´ǩiõll',
            'country' => 'FIN',
            'langcode' => 'SMS',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x203B,
        );
        $defaultCountry['sms'] = 'FI';

        /* Sami, Southern (Norway) */
        $supportedLanguages['sma']['NO'] = array(
            'description' => 'Sami, Southern (Norway)',
            'title_en' => 'Sami',
            'title' => 'åarjelsaemiengiele',
            'country' => 'NOR',
            'langcode' => 'SMA',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x183B,
        );
        $defaultCountry['sma'] = 'NO';

        /* Sami, Southern (Sweden) */
        $supportedLanguages['sma']['SE'] = array(
            'description' => 'Sami, Southern (Sweden)',
            'title_en' => 'Sami',
            'title' => 'åarjelsaemiengiele',
            'country' => 'SWE',
            'langcode' => 'SMB',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x1C3B,
        );

        /* Sanskrit (India) */
        $supportedLanguages['sa']['IN'] = array(
            'description' => 'Sanskrit (India)',
            'title_en' => 'Sanskrit',
            'title' => 'संस्कृत',
            'country' => 'IND',
            'langcode' => 'SAN',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x044F,
        );
        $defaultCountry['sa'] = 'IN';

        /* Serbian (Cyrillic, Bosnia and Herzegovina) */
        $supportedLanguages['sr']['Cyrl'] = array(
            'description' => 'Serbian (Cyrillic, Bosnia and Herzegovina)',
            'title_en' => 'Serbian',
            'title' => 'српски',
            'country' => 'BIH',
            'langcode' => 'SRN',
            'ansi' => 1251,
            'oem' => 855,
            'lcid' => 0x1C1A,
        );
        $defaultCountry['sr'] = 'Cyrl';

        /* Serbian (Cyrillic, Serbia) */
        $supportedLanguages['sr']['Cyrl'] = array(
            'description' => 'Serbian (Cyrillic, Serbia)',
            'title_en' => 'Serbian',
            'title' => 'српски',
            'country' => 'SCG',
            'langcode' => 'SRB',
            'ansi' => 1251,
            'oem' => 855,
            'lcid' => 0x0C1A,
        );

        /* Serbian (Latin, Bosnia and Herzegovina) */
        $supportedLanguages['sr']['Latn'] = array(
            'description' => 'Serbian (Latin, Bosnia and Herzegovina)',
            'title_en' => 'Serbian',
            'title' => 'srpski',
            'country' => 'BIH',
            'langcode' => 'SRS',
            'ansi' => 1250,
            'oem' => 852,
            'lcid' => 0x181A,
        );

        /* Serbian (Latin, Serbia) */
        $supportedLanguages['sr']['Latn'] = array(
            'description' => 'Serbian (Latin, Serbia)',
            'title_en' => 'Serbian',
            'title' => 'srpski',
            'country' => 'SCG',
            'langcode' => 'SRL',
            'ansi' => 1250,
            'oem' => 852,
            'lcid' => 0x081A,
        );

        /* Sesotho sa Leboa (South Africa) */
        $supportedLanguages['nso']['ZA'] = array(
            'description' => 'Sesotho sa Leboa (South Africa)',
            'title_en' => 'Sesotho sa Leboa',
            'title' => 'Sesotho sa Leboa',
            'country' => 'ZAF',
            'langcode' => 'NSO',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x046C,
        );
        $defaultCountry['nso'] = 'ZA';

        /* Setswana (South Africa) */
        $supportedLanguages['tn']['ZA'] = array(
            'description' => 'Setswana (South Africa)',
            'title_en' => 'Setswana',
            'title' => 'Setswana',
            'country' => 'ZAF',
            'langcode' => 'TSN',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0432,
        );
        $defaultCountry['tn'] = 'ZA';

        /* Sinhala (Sri Lanka) */
        $supportedLanguages['si']['LK'] = array(
            'description' => 'Sinhala (Sri Lanka)',
            'title_en' => 'Sinhala',
            'title' => 'සිංහ',
            'country' => 'LKA',
            'langcode' => 'SIN',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x045B,
        );
        $defaultCountry['si'] = 'LK';

        /* Slovak (Slovakia) */
        $supportedLanguages['sk']['SK'] = array(
            'description' => 'Slovak (Slovakia)',
            'title_en' => 'Slovak',
            'title' => 'slovenčina',
            'country' => 'SVK',
            'langcode' => 'SKY',
            'ansi' => 1250,
            'oem' => 852,
            'lcid' => 0x041B,
        );
        $defaultCountry['sk'] = 'SK';

        /* Slovenian (Slovenia) */
        $supportedLanguages['sl']['SI'] = array(
            'description' => 'Slovenian (Slovenia)',
            'title_en' => 'Slovenian',
            'title' => 'slovenski',
            'country' => 'SVN',
            'langcode' => 'SLV',
            'ansi' => 1250,
            'oem' => 852,
            'lcid' => 0x0424,
        );
        $defaultCountry['sl'] = 'SI';

        /* Spanish (Argentina) */
        $supportedLanguages['es']['AR'] = array(
            'description' => 'Spanish (Argentina)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'ARG',
            'langcode' => 'ESS',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x2C0A,
        );
        $defaultCountry['es'] = 'ES';

        /* Spanish (Bolivia) */
        $supportedLanguages['es']['BO'] = array(
            'description' => 'Spanish (Bolivia)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'BOL',
            'langcode' => 'ESB',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x400A,
        );

        /* Spanish (Chile) */
        $supportedLanguages['es']['CL'] = array(
            'description' => 'Spanish (Chile)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'CHL',
            'langcode' => 'ESL',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x340A,
        );

        /* Spanish (Colombia) */
        $supportedLanguages['es']['CO'] = array(
            'description' => 'Spanish (Colombia)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'COL',
            'langcode' => 'ESO',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x240A,
        );

        /* Spanish (Costa Rica) */
        $supportedLanguages['es']['CR'] = array(
            'description' => 'Spanish (Costa Rica)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'CRI',
            'langcode' => 'ESC',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x140A,
        );

        /* Spanish (Dominican Republic) */
        $supportedLanguages['es']['DO'] = array(
            'description' => 'Spanish (Dominican Republic)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'DOM',
            'langcode' => 'ESD',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x1C0A,
        );

        /* Spanish (Ecuador) */
        $supportedLanguages['es']['EC'] = array(
            'description' => 'Spanish (Ecuador)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'ECU',
            'langcode' => 'ESF',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x300A,
        );

        /* Spanish (El Salvador) */
        $supportedLanguages['es']['SV'] = array(
            'description' => 'Spanish (El Salvador)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'SLV',
            'langcode' => 'ESE',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x440A,
        );

        /* Spanish (Guatemala) */
        $supportedLanguages['es']['GT'] = array(
            'description' => 'Spanish (Guatemala)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'GTM',
            'langcode' => 'ESG',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x100A,
        );

        /* Spanish (Honduras) */
        $supportedLanguages['es']['HN'] = array(
            'description' => 'Spanish (Honduras)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'HND',
            'langcode' => 'ESH',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x480A,
        );

        /* Spanish (Mexico) */
        $supportedLanguages['es']['MX'] = array(
            'description' => 'Spanish (Mexico)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'MEX',
            'langcode' => 'ESM',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x080A,
        );

        /* Spanish (Nicaragua) */
        $supportedLanguages['es']['NI'] = array(
            'description' => 'Spanish (Nicaragua)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'NIC',
            'langcode' => 'ESI',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x4C0A,
        );

        /* Spanish (Panama) */
        $supportedLanguages['es']['PA'] = array(
            'description' => 'Spanish (Panama)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'PAN',
            'langcode' => 'ESA',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x180A,
        );

        /* Spanish (Paraguay) */
        $supportedLanguages['es']['PY'] = array(
            'description' => 'Spanish (Paraguay)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'PRY',
            'langcode' => 'ESZ',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x3C0A,
        );

        /* Spanish (Peru) */
        $supportedLanguages['es']['PE'] = array(
            'description' => 'Spanish (Peru)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'PER',
            'langcode' => 'ESR',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x280A,
        );

        /* Spanish (Puerto Rico) */
        $supportedLanguages['es']['PR'] = array(
            'description' => 'Spanish (Puerto Rico)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'PRI',
            'langcode' => 'ESU',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x500A,
        );

        /* Spanish (Spain) */
        $supportedLanguages['es']['ES'] = array(
            'description' => 'Spanish (Spain)',
            'title_en' => 'Spanish',
            'title' => 'español',
            'country' => 'ESP',
            'langcode' => 'ESN',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0C0A,
        );

        /* Spanish (United States) */
        $supportedLanguages['es']['US'] = array(
            'description' => 'Spanish (United States)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'USA',
            'langcode' => 'EST',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x540A,
        );

        /* Spanish (Uruguay) */
        $supportedLanguages['es']['UY'] = array(
            'description' => 'Spanish (Uruguay)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'URY',
            'langcode' => 'ESY',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x380A,
        );

        /* Spanish (Venezuela) */
        $supportedLanguages['es']['VE'] = array(
            'description' => 'Spanish (Venezuela)',
            'title_en' => 'Spanish',
            'title' => 'Español',
            'country' => 'VEN',
            'langcode' => 'ESV',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x200A,
        );

        /* Swedish (Finland) */
        $supportedLanguages['sv']['FI'] = array(
            'description' => 'Swedish (Finland)',
            'title_en' => 'Swedish',
            'title' => 'svenska',
            'country' => 'FIN',
            'langcode' => 'SVF',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x081D,
        );
        $defaultCountry['sv'] = 'FI';

        /* Swedish (Sweden) */
        $supportedLanguages['sv']['SE'] = array(
            'description' => 'Swedish (Sweden)',
            'title_en' => 'Swedish',
            'title' => 'svenska',
            'country' => 'SWE',
            'langcode' => 'SVE',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x041D,
        );

        /* Syriac (Syria)‎ */
        $supportedLanguages['syr']['SY'] = array(
            'description' => 'Syriac (Syria)‎',
            'title_en' => 'Syriac',
            'title' => 'ܣܘܪܝܝܐ',
            'country' => 'SYR',
            'langcode' => 'SYR',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x045A,
        );
        $defaultCountry['syr'] = 'SY';

        /* Tajik (Cyrillic, Tajikistan) */
        $supportedLanguages['tg']['Cyrl'] = array(
            'description' => 'Tajik (Cyrillic, Tajikistan)',
            'title_en' => 'Tajik',
            'title' => 'Тоҷикӣ',
            'country' => 'TAJ',
            'langcode' => 'TAJ',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x0428,
        );
        $defaultCountry['tg'] = 'Cyrl';

        /* Tamazight (Latin, Algeria) */
        $supportedLanguages['tmz']['Latn'] = array(
            'description' => 'Tamazight (Latin, Algeria)',
            'title_en' => 'Tamazight',
            'title' => 'Tamazight',
            'country' => 'DZA',
            'langcode' => 'TMZ',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x085F,
        );
        $defaultCountry['tmz'] = 'Latn';

        /* Tamil (India) */
        $supportedLanguages['ta']['IN'] = array(
            'description' => 'Tamil (India)',
            'title_en' => 'Tamil',
            'title' => 'தமிழ்',
            'country' => 'IND',
            'langcode' => 'TAM',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0449,
        );
        $defaultCountry['ta'] = 'IN';

        /* Tatar (Russia) */
        $supportedLanguages['tt']['RU'] = array(
            'description' => 'Tatar (Russia)',
            'title_en' => 'Tatar',
            'title' => 'Татар',
            'country' => 'RUS',
            'langcode' => 'TTT',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x0444,
        );
        $defaultCountry['tt'] = 'RU';

        /* Telugu (India) */
        $supportedLanguages['te']['IN'] = array(
            'description' => 'Telugu (India)',
            'title_en' => 'Telugu',
            'title' => 'తెలుగు',
            'country' => 'IND',
            'langcode' => 'TEL',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x044A,
        );
        $defaultCountry['te'] = 'IN';

        /* Thai (Thailand) */
        $supportedLanguages['th']['TH'] = array(
            'description' => 'Thai (Thailand)',
            'title_en' => 'Thai',
            'title' => 'ไทย',
            'country' => 'THA',
            'langcode' => 'THA',
            'ansi' => 874,
            'oem' => 874,
            'lcid' => 0x041E,
        );
        $defaultCountry['th'] = 'TH';

        /* Tibetan (PRC) */
        $supportedLanguages['bo']['CN'] = array(
            'description' => 'Tibetan (PRC)',
            'title_en' => 'Tibetan',
            'title' => 'བོད་ཡིག',
            'country' => 'CHN',
            'langcode' => 'BOB',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0451,
        );
        $defaultCountry['bo'] = 'CN';

        /* Turkish (Turkey) */
        $supportedLanguages['tr']['TR'] = array(
            'description' => 'Turkish (Turkey)',
            'title_en' => 'Turkish',
            'title' => 'Türkçe',
            'country' => 'TUR',
            'langcode' => 'TRK',
            'ansi' => 1254,
            'oem' => 857,
            'lcid' => 0x041F,
        );
        $defaultCountry['tr'] = 'TR';

        /* Turkmen (Turkmenistan) */
        $supportedLanguages['tk']['TM'] = array(
            'description' => 'Turkmen (Turkmenistan)',
            'title_en' => 'Turkmen',
            'title' => 'türkmençe',
            'country' => 'TKM',
            'langcode' => 'TUK',
            'ansi' => 1250,
            'oem' => 852,
            'lcid' => 0x0442,
        );
        $defaultCountry['tk'] = 'TM';

        /* Uighur (PRC) */
        $supportedLanguages['ug']['CN'] = array(
            'description' => 'Uighur (PRC)',
            'title_en' => 'Uighur',
            'title' => 'ئۇيغۇر يېزىقى',
            'country' => 'CHN',
            'langcode' => 'UIG',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x0480,
        );
        $defaultCountry['ug'] = 'CN';

        /* Ukrainian (Ukraine) */
        $supportedLanguages['uk']['UA'] = array(
            'description' => 'Ukrainian (Ukraine)',
            'title_en' => 'Ukrainian',
            'title' => 'україньска',
            'country' => 'UKR',
            'langcode' => 'UKR',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x0422,
        );
        $defaultCountry['uk'] = 'UA';

        /* Upper Sorbian (Germany) */
        $supportedLanguages['wen']['DE'] = array(
            'description' => 'Upper Sorbian (Germany)',
            'title_en' => 'Upper Sorbian',
            'title' => 'hornjoserbšćina',
            'country' => 'GER',
            'langcode' => 'HSB',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x042E,
        );
        $defaultCountry['wen'] = 'DE';

        /* Urdu (Islamic Republic of Pakistan)‎ */
        $supportedLanguages['ur']['PK'] = array(
            'description' => 'Urdu (Islamic Republic of Pakistan)‎',
            'title_en' => 'Urdu',
            'title' => 'اُردو',
            'country' => 'PAK',
            'langcode' => 'URD',
            'ansi' => 1256,
            'oem' => 720,
            'lcid' => 0x0420,
        );
        $defaultCountry['ur'] = 'PK';

        /* Uzbek (Cyrillic, Uzbekistan) */
        $supportedLanguages['uz']['Cyrl'] = array(
            'description' => 'Uzbek (Cyrillic, Uzbekistan)',
            'title_en' => 'Uzbek',
            'title' => 'Ўзбек',
            'country' => 'UZB',
            'langcode' => 'UZB',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x0843,
        );
        $defaultCountry['uz'] = 'Cyrl';

        /* Uzbek (Latin, Uzbekistan) */
        $supportedLanguages['uz']['Latn'] = array(
            'description' => 'Uzbek (Latin, Uzbekistan)',
            'title_en' => 'Uzbek',
            'title' => 'U\'zbek',
            'country' => 'UZB',
            'langcode' => 'UZB',
            'ansi' => 1254,
            'oem' => 857,
            'lcid' => 0x0443,
        );

        /* Vietnamese (Vietnam) */
        $supportedLanguages['vi']['VN'] = array(
            'description' => 'Vietnamese (Vietnam)',
            'title_en' => 'Vietnamese',
            'title' => 'Tiếng Việt',
            'country' => 'VNM',
            'langcode' => 'VIT',
            'ansi' => 1258,
            'oem' => 1258,
            'lcid' => 0x042A,
        );
        $defaultCountry['vi'] = 'VN';

        /* Welsh (United Kingdom) */
        $supportedLanguages['cy']['GB'] = array(
            'description' => 'Welsh (United Kingdom)',
            'title_en' => 'Welsh',
            'title' => 'Cymraeg',
            'country' => 'GBR',
            'langcode' => 'CYM',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0452,
        );
        $defaultCountry['cy'] = 'GB';

        /* Wolof (Senegal) */
        $supportedLanguages['wo']['SN'] = array(
            'description' => 'Wolof (Senegal)',
            'title_en' => 'Wolof',
            'title' => 'Wolof',
            'country' => 'SEN',
            'langcode' => 'WOL',
            'ansi' => 1252,
            'oem' => 850,
            'lcid' => 0x0488,
        );
        $defaultCountry['wo'] = 'SN';

        /* Yakut (Russia) */
        $supportedLanguages['sah']['RU'] = array(
            'description' => 'Yakut (Russia)',
            'title_en' => 'Yakut',
            'title' => 'саха',
            'country' => 'RUS',
            'langcode' => 'SAH',
            'ansi' => 1251,
            'oem' => 866,
            'lcid' => 0x0485,
        );
        $defaultCountry['sah'] = 'RU';

        /* Yi (PRC) */
        $supportedLanguages['ii']['CN'] = array(
            'description' => 'Yi (PRC)',
            'title_en' => 'Yi',
            'title' => 'ꆈꌠꁱꂷ',
            'country' => 'CHN',
            'langcode' => 'III',
            'ansi' => 0,
            'oem' => 1,
            'lcid' => 0x0478,
        );
        $defaultCountry['ii'] = 'CN';

        /* Yoruba (Nigeria) */
        $supportedLanguages['yo']['NG'] = array(
            'description' => 'Yoruba (Nigeria)',
            'title_en' => 'Yoruba',
            'title' => 'Yoruba',
            'country' => 'NGA',
            'langcode' => 'YOR',
            'ansi' => 1252,
            'oem' => 437,
            'lcid' => 0x046A,
        );
        $defaultCountry['yo'] = 'NG';

        return array($supportedLanguages, $defaultCountry);
    }

    public static function LangCountryLocalesToThreeLetterCode($locale)
    {
        if (!$spl = array_filter(explode('_', $locale))) {
            return false;
        }

        $all = self::getAll();

        if (!isset($spl[1])) {
            $spl[1] = isset($all[1][$spl[0]]) ? $all[1][$spl[0]] : null;
        }

        if (!isset($all[0][$spl[0]])) {
            return false;
        }

        if (!isset($all[0][$spl[0]][$spl[1]])) {
            return false;
        }

        return array(
            $all[0][$spl[0]][$spl[1]]['langcode'],
            $all[0][$spl[0]][$spl[1]]['title_en']
        );
    }
}