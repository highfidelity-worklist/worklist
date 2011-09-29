<?php
  $timezoneTable = array(
	  "-1200" => "(GMT -12:00) Eniwetok, Kwajalein",
	  "-1100" => "(GMT -11:00) Midway Island, Samoa",
	  "-1000" => "(GMT -10:00) Hawaii",
	  "-0900" => "(GMT -9:00) Alaska",
	  "-0800" => "(GMT -8:00) Pacific Time (US & Canada)",
	  "-0700" => "(GMT -7:00) Mountain Time (US & Canada)",
	  "-0600" => "(GMT -6:00) Central Time (US & Canada), Mexico City",
	  "-0500" => "(GMT -5:00) Eastern Time (US & Canada), Bogota, Lima",
	  "-0400" => "(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz",
	  "-0330" => "(GMT -3:30) Newfoundland",
	  "-0300" => "(GMT -3:00) Brazil, Buenos Aires, Georgetown",
	  "-0200" => "(GMT -2:00) Mid-Atlantic",
	  "-0100" => "(GMT -1:00 hour) Azores, Cape Verde Islands",
	  "+0000" => "(GMT) Western Europe Time, London, Lisbon, Casablanca",
	  "+0100" => "(GMT +1:00 hour) Brussels, Copenhagen, Madrid, Paris",
	  "+0200" => "(GMT +2:00) Kaliningrad, South Africa",
	  "+0300" => "(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg",
	  "+0330" => "(GMT +3:30) Tehran",
	  "+0400" => "(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi",
	  "+0430" => "(GMT +4:30) Kabul",
	  "+0500" => "(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent",
	  "+0530" => "(GMT +5:30) Bombay, Calcutta, Madras, New Delhi",
	  "+0600" => "(GMT +6:00) Almaty, Dhaka, Colombo",
	  "+0700" => "(GMT +7:00) Bangkok, Hanoi, Jakarta",
	  "+0800" => "(GMT +8:00) Beijing, Perth, Singapore, Hong Kong",
	  "+0900" => "(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk",
	  "+0930" => "(GMT +9:30) Adelaide, Darwin",
	  "+1000" => "(GMT +10:00) Eastern Australia, Guam, Vladivostok",
	  "+1100" => "(GMT +11:00) Magadan, Solomon Islands, New Caledonia",
	  "+1200" => "(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka"
  );
function getTimeZoneDateTime($GMT) {
    $timezones = array(
        '-1200'=>'Pacific/Kwajalein',
        '-1100'=>'Pacific/Samoa',
        '-1000'=>'Pacific/Honolulu',
        '-0900'=>'America/Juneau',
        '-0800'=>'America/Los_Angeles',
        '-0700'=>'America/Denver',
        '-0600'=>'America/Mexico_City',
        '-0500'=>'America/New_York',
        '-0400'=>'America/Caracas',
        '-0330'=>'America/St_Johns',
        '-0300'=>'America/Argentina/Buenos_Aires',
        '-0200'=>'Atlantic/Azores',// no cities here so just picking an hour ahead
        '-0100'=>'Atlantic/Azores',
        '+0000'=>'Europe/London',
        '+0100'=>'Europe/Paris',
        '+0200'=>'Europe/Helsinki',
        '+0300'=>'Europe/Moscow',
        '+0330'=>'Asia/Tehran',
        '+0400'=>'Asia/Baku',
        '+0430'=>'Asia/Kabul',
        '+0500'=>'Asia/Karachi',
        '+0530'=>'Asia/Calcutta',
        '+0600'=>'Asia/Colombo',
        '+0700'=>'Asia/Bangkok',
        '+0800'=>'Asia/Singapore',
        '+0900'=>'Asia/Tokyo',
        '+0930'=>'Australia/Darwin',
        '+1000'=>'Pacific/Guam',
        '+1100'=>'Asia/Magadan',
        '+1200'=>'Asia/Kamchatka'
    );
    if(isset($timezones[$GMT])){
        return $timezones[$GMT];
    } else {
        return date_default_timezone_get();
    }
}

function convertTimeZoneToLocalTime($timeoffset) {
    $DefZone = getTimeZoneDateTime($timeoffset);
    date_default_timezone_set($DefZone);
    if (strlen($timeoffset) == 5) {
        $formatedTime = str_split($timeoffset);
        $Symbol = $formatedTime[0];
        $First = $formatedTime[1];
        $Second = $formatedTime[2];
        $Third = $formatedTime[3];
        $Fourth = $formatedTime[4];
        if ($Third=="3") {
            $Third =5;
        }
        $timezone_local = $Symbol.$First.$Second.".".$Third.$Fourth;
    } else {
        $timezone_local = 0;
    }

    $time = time();
    $timezone_offset = date("Z");
    $timezone_add = round($timezone_local*60*60);
    $ar = localtime($time,true);
    if ($ar['tm_isdst']) { $time += 3600; }
    $time = round($time-$timezone_offset+$timezone_add);
    $LocalTime = date("h:i:s A", $time);
    return $LocalTime;		
}
?>
