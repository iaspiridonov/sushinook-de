<?php

namespace Src\Helpers;

class UTM
{
    const UTM_SOURCE   = 'utm_source';
    const UTM_MEDIUM   = 'utm_medium';
    const UTM_CAMPAIGN = 'utm_campaign';
    const UTM_TERM     = 'utm_term';
    const UTM_CONTENT  = 'utm_content';

    public static function setUTM(): void
    {
        foreach (self::asArray() as $UTMItem) {
            if (array_key_exists($UTMItem, $_GET)) {
                self::setCookie($UTMItem, $_GET[$UTMItem]);
            }
        }
    }

    public static function getSavedUTM(): array
    {
        $UTM = [];
        foreach (self::asArray() as $UTMItem) {
            if (array_key_exists($UTMItem, $_COOKIE)) {
                $UTM[$UTMItem] = $_COOKIE[$UTMItem];
            }
        }

        return $UTM;
    }

    public static function getSavedUTMWithoutKeys(): array
    {
        $UTM = [];
        foreach (self::asArray() as $UTMItem) {
            if (array_key_exists($UTMItem, $_COOKIE)) {
                $UTM[] = $_COOKIE[$UTMItem] ?? 'SEO';
            } else {
                $UTM[] = 'SEO';
            }

        }

        return $UTM;
    }

    private static function asArray(): array
    {
        return [
            self::UTM_SOURCE,
            self::UTM_MEDIUM,
            self::UTM_CAMPAIGN,
            self::UTM_TERM,
            self::UTM_CONTENT,
        ];
    }

    private static function setCookie($key, $value): void
    {
        setcookie($key, $value, time() + 3600 * 24 * 30, '/'); // храним 30 дней
    }
}