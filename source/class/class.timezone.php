<?php
namespace System;

use DateTimeZone;

class Timezone
{
    private $main_config;

    public function __construct(array $config_array)
    {
        $this->main_config = $config_array;
    }

    public function getWebTimezone()
    {
        return $this->main_config['web_timezone'];
    }

    public function getCustomTimezone($user_tz)
    {
        return (!empty($user_tz)) ? $user_tz : false;
    }

    public function getTimezoneList($area = '')
    {
        $get_zone = (!empty($area)) ? $area : 2047;
        $zone_array = array();
        $timestamp = time();
        $default_timezone = (!empty($this->getWebTimezone())) ? $this->getWebTimezone() : date_default_timezone_get();
        foreach (DateTimeZone::listIdentifiers($get_zone) as $key => $zone) {
            date_default_timezone_set($zone);
            $zone_array[$key]['zone'] = $zone;
            $zone_array[$key]['diff_from_GMT'] = $zone.' (GMT'.date('P', $timestamp).')';
        }
        date_default_timezone_set($default_timezone);

        return $zone_array;
    }

    public function getTimezoneArea()
    {
        $current_timezone = $this->getWebTimezone();
        date_default_timezone_set($current_timezone);
        $tz_result = explode('/', $current_timezone);

        return $tz_result[0];
    }
}
