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

class lcDeviceDetective
{
    private static $device_strings =
        [
            'symbian' => 'symbian',
            'windows ce' => 'winmobile',
            'windows' => 'winmobile',
            'iemobile' => 'winmobile',
            'wm5 pie' => 'winmobile', //old winmobile device :?
            'blackberry' => 'blackberry',
            'iphone' => 'iphone',
            'ipad' => 'ipad',
            'android' => 'android',
        ];
    private static $mobile_agents =
        [
            'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
            'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
            'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
            'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
            'newt', 'noki', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
            'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
            'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
            'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
            'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-',
        ];
    private $useragent;
    //private $httpaccept;

    //Device Versions//
    /*private static $versions = array
    (
        'symbian' => array('series60', 'series70', 'series80', 'series90')
    );*/


    /*private static $browsers = array
    (
        'opera',
        'netfront', //common os browsers
        'teleca q',
        'safari',
    );*/

    /*private static $device_breads = array
    (
        'pda', 'mini', 'mobile', 'mobi'
    );*/

    //webkits engines//
    /*private static $web_kits = array
    (
        'webkit', 'android', 'iphone', 'ipod'
    );*/


    //rare custom stuff//
    /*private static $operators = array
    (
        'docomo', 'kddi', 'vodafone'
    );*/

    /*private static $manufacturer = array
    (
        'sonyericsson', 'ericsson', 'sec-sgh', 'sony', 'apple', 'htc'
    );*/

    //mobile agents//
    private $request;

    public function __construct(lcRequest $request)
    {
        $this->request = $request;

        $this->useragent = strtolower($this->request->env('HTTP_USER_AGENT'));
        //$this->httpaccept = strtolower($this->request->env('HTTP_ACCEPT'));

        //$this->useragent = strtolower('Mozilla/5.0 (SymbianOS/9.4; Series60/3.1 NokiaN97-1/12.0.024');
        //$this->useragent = strtolower('th_touch_prot722 opera mini/9.50 (windows nt 5.1; u; en)');
    }

    public function isWapRequest()
    {
        return ((strpos(strtolower($this->request->env('HTTP_ACCEPT')), 'application/vnd.wap.xhtml+xml') > 0) ||
            ((($this->request->env('HTTP_X_WAP_PROFILE')) || ($this->request->env('HTTP_PROFILE'))))
        );
    }

    public function detectMobileDevice()
    {
        if (!$this->isMobileBrowser()) {
            return [];
        }

        $data = [];

        foreach (self::$device_strings as $haystack => $device_key) {
            //find device key and proccess it//
            if (stripos($this->useragent, $haystack) > -1) {
                $data = $this->processDevice($device_key);

                break;
            }
        }

        return $data;
    }

    public function isMobileBrowser()
    {
        if (preg_match('/(up.browser|up.link|mmp|mobile|symbian|smartphone|midp|wap|android|htc|phone|windows ce|iemobile|wm5 pie)/i', $this->useragent)) {
            return true;
        }

        $mobile_ua = strtolower(substr($this->useragent, 0, 4));

        if (in_array($mobile_ua, self::$mobile_agents)) {
            return true;
        }

        if (preg_match('/windows/i', $this->useragent)) {
            if (stripos($this->useragent, 'opera') > -1) {
                if ((stripos($this->useragent, 'mini') > -1) || (stripos($this->useragent, 'mobi') > -1)) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        if ((strpos(strtolower($this->request->env('HTTP_ACCEPT')), 'application/vnd.wap.xhtml+xml') > 0) ||
            ((($this->request->env('HTTP_X_WAP_PROFILE')) || ($this->request->env('HTTP_PROFILE'))))
        ) {
            return true;
        }

        return false;
    }

    public function processDevice($device_key)
    {
        switch ($device_key) {
            case 'iphone':
                {
                    // match iphone devices
                    preg_match_all('/\sos\s(.*?)\s/i', $this->useragent, $matches);
                    $matches = array_filter($matches);

                    if ($matches && count($matches) == 2) {
                        return
                            [
                                'device' => 'iphone',
                                'sdk' => $matches[1][0],
                            ];
                    }

                    //default case unknow symbian device//
                    return
                        [
                            'device' => 'iphone',
                            'sdk' => null,
                        ];

                    break;
                }
            case 'ipad':
                {
                    // match iphone devices
                    preg_match_all('/\sos\s(.*?)\s/i', $this->useragent, $matches);
                    $matches = array_filter($matches);

                    if ($matches && count($matches) == 2) {
                        return
                            [
                                'device' => 'ipad',
                                'sdk' => $matches[1][0],
                            ];
                    }

                    //default case unknow symbian device//
                    return
                        [
                            'device' => 'ipad',
                            'sdk' => null,
                        ];

                    break;
                }
            case 'android':
                {
                    // match android devices
                    preg_match_all('/Android\s(.*?);\s/i', $this->useragent, $matches);
                    $matches = array_filter($matches);

                    if ($matches && count($matches) == 2) {
                        return
                            [
                                'device' => 'android',
                                'sdk' => $matches[1][0],
                            ];
                    }

                    //default case unknow android device//
                    return
                        [
                            'device' => 'android',
                            'sdk' => null,
                        ];

                    break;
                }
            case 'symbian':
                {
                    //new headers
                    preg_match_all('/series(.*?)\/(.*?) /i', $this->useragent, $matches);
                    $matches = array_filter($matches);

                    if ($matches && count($matches) == 3) {
                        return
                            [
                                'device' => 'symbian',
                                'sdk' => (int)$matches[1][0],
                                'fp' => $this->getFpCodeByBrowser($matches[2][0]),
                            ];
                    }

                    //default case unknow symbian device//
                    return
                        [
                            'device' => 'symbian',
                            'sdk' => 0,
                            'fp' => false,
                        ];

                    break;
                }
            case 'winmobile':
                {
                    //ie//
                    preg_match_all('/msie (.*?);/i', $this->useragent, $matches);
                    $matches = array_filter($matches);

                    if ($matches && count($matches) == 2) {
                        return
                            [
                                'device' => 'winmobile',
                                'os_version' => (float)$matches[1][0],
                            ];
                    }

                    //opera
                    preg_match_all('/msie (.*?);/i', $this->useragent, $matches);
                    $matches = array_filter($matches);

                    if ($matches && count($matches) == 2) {
                        return
                            [
                                'device' => 'winmobile',
                                'os_version' => (float)$matches[1][0],
                            ];
                    }

                    //opera sucks//
                    if (stripos('opera', $this->useragent) > -1) {
                        preg_match_all('/windows nt (.*?);/i', $this->useragent, $matches);
                        $matches = array_filter($matches);

                        if ($matches && count($matches) == 2) {
                            return
                                [
                                    'device' => 'winmobile',
                                    'os_version' => (float)$matches[1][0],
                                ];
                        }
                    }

                    //i dont know what are you!
                    return
                        [
                            'device' => 'winmobile',
                            'os_version' => 0,
                        ];
                }
            default:
                {
                    return [];
                }
        }
    }

    private function getFpCodeByBrowser($code)
    {
        $code = (float)$code;

        //the furutre pack 1
        if ($code == 3.1) {
            return 1;
        }

        //some have 7.1//
        if ($code == 3.2) {
            return 2;
        }

        if ($code == 7.1 || $code == 7.0) {
            return 5;
        }

        //unsuported symbian
        if ($code < 3.1) {
            return false;
        }

        //greater then future pack 1
        if ($code > 3.2) {
            return 2;
        }

        return false;
    }

    public function debug()
    {
        e($this->useragent);
    }

    public function __toString()
    {
        return (string)$this->useragent;
    }
}
