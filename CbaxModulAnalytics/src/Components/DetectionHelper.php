<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components;

class DetectionHelper
{
    const DEFAULT_DEVICES = [
        'desktop',
        'tablet',
        'mobile'
    ];

    const OS = [
        '/windows nt 11/i'      =>  'Windows 11',
        '/windows nt 10/i'      =>  'Windows 10',
        '/windows phone 10/i'   =>  'Windows Phone 10',
        '/windows phone 8.1/i'  =>  'Windows Phone 8.1',
        '/windows phone 8/i'    =>  'Windows Phone 8',
        '/windows nt 6.3/i'     =>  'Windows 8.1',
        '/windows nt 6.2/i'     =>  'Windows 8',
        '/windows nt 6.1/i'     =>  'Windows 7',
        '/windows nt 6.0/i'     =>  'Windows Vista',
        '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     =>  'Windows XP',
        '/windows xp/i'         =>  'Windows XP',
        '/windows nt 5.0/i'     =>  'Windows 2000',
        '/windows me/i'         =>  'Windows ME',
        '/win98/i'              =>  'Windows 98',
        '/win95/i'              =>  'Windows 95',
        '/win16/i'              =>  'Windows 3.11',
        '/macintosh|mac os x/i' =>  'Mac OS X',
        '/mac_powerpc/i'        =>  'Mac OS 9',
        '/iphone/i'             =>  'iOS',
        '/ipod/i'               =>  'iOS',
        '/ipad/i'               =>  'iOS',
        '/android/i'            =>  'Android',
        '/linux/i'              =>  'Linux',
        '/ubuntu/i'             =>  'Ubuntu',
        '/blackberry/i'         =>  'BlackBerry',
        '/webos/i'              =>  'Mobile'
    ];

    const BROWSER = [
        '/firefox/i'    =>  'Firefox',
        '/msie/i'       =>  'Internet Explorer',
        '/edge/i'       =>  'Edge',
        '/edg/i'        =>  'Edge',
        '/opera/i'      =>  'Opera',
        '/chrome/i'     =>  'Chrome',
        '/safari/i'     =>  'Safari',
        '/mobile/i'     =>  'Handheld Browser',
        '/netscape/i'   =>  'Netscape',
        '/maxthon/i'    =>  'Maxthon',
        '/konqueror/i'  =>  'Konqueror'
    ];

    public static function getDeviceType(string $httpUserAgent): string
    {
        if (!empty($_COOKIE) && !empty($_COOKIE['x-ua-device'])) {
            $deviceType = strtolower($_COOKIE['x-ua-device']);
            if (in_array($deviceType, self::DEFAULT_DEVICES)) {
                return $deviceType;
            }
        }

        $os = DetectionHelper::getOS($httpUserAgent);
        $mobileOS = ['Windows Phone 10','Windows Phone 8.1','Windows Phone 8','BlackBerry','Mobile'];
        $tabletOS = ['Android','iOS'];

        if (preg_match('/mobile|phone|ipod/i', $httpUserAgent) || in_array($os, $mobileOS)) {
            return 'mobile';
        }

        if (preg_match('/tablet|ipad/i', $httpUserAgent) || in_array($os, $tabletOS)) {
            return 'tablet';
        }
        return 'desktop';
    }

    public static function getOS(string $httpUserAgent): string
    {
        foreach (self::OS as $key => $value) {
            if (preg_match($key, $httpUserAgent)) {
                return $value;
            }
        }

        return 'Not Detected';
    }

    public static function getBrowser(string $httpUserAgent): string
    {
        foreach (self::BROWSER as $key => $value) {
            if (preg_match($key, $httpUserAgent)) {
                return $value;
            }
        }

        return 'Not Detected';
    }

    public static function botDetected(string $userAgent): bool
    {
        return is_string($userAgent) && preg_match('/bot|googlebot|robot|baidu|crawl|crawler|bingbot|yahoo|yandexbot|msnbot|slurp|spider|mediapartners/i', $userAgent);
    }

    public static function ipIsBlacklisted(mixed $ip, ?array $config): bool
    {
        if (!empty($ip) && !empty($config['trackingIpBlacklist']) && is_array($config['trackingIpBlacklist'])) {
            return in_array($ip, $config['trackingIpBlacklist']);
        }

        return false;
    }


}
