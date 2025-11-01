<?php
use libphonenumber\PhoneNumberUtil;
use libphonenumber\geocoding\PhoneNumberOfflineGeocoder;

function getCountryInfoFromPhone($num) {
    $phoneUtil = PhoneNumberUtil::getInstance();
    $geocoder = PhoneNumberOfflineGeocoder::getInstance();

    try {
        $number = $phoneUtil->parse($num, null);
        $regionCode = $phoneUtil->getRegionCodeForNumber($number);
        $countryName = $geocoder->getDescriptionForNumber($number, "en");
        $countryCode = $number->getCountryCode();

        return [
            'input' => $num,
            'country_name' => $countryName,
            'region_code' => $regionCode,
            'country_calling_code' => '+' . $countryCode
        ];
    } catch (\libphonenumber\NumberParseException $e) {
        return null;
    }
}
