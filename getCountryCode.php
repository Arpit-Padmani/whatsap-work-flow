<?php
require __DIR__ . '/vendor/autoload.php';

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

function getCountryInfoFromPhone(string $rawNumber, string $displayLocale = 'en'): ?array {
    $phoneUtil = PhoneNumberUtil::getInstance();

    try {
        $numberProto = $phoneUtil->parse($rawNumber, null);
    } catch (NumberParseException $e) {
        return null;
    }

    if (!$phoneUtil->isValidNumber($numberProto)) {
        return null;
    }

    $countryCallingCode = $numberProto->getCountryCode();
    $regionCode = $phoneUtil->getRegionCodeForNumber($numberProto);

    // Safe fallback if intl extension is missing
    if (class_exists('Locale')) {
        $countryName = \Locale::getDisplayRegion('-' . $regionCode, $displayLocale);
    } else {
        $countryName = $regionCode;
    }

    $e164 = $phoneUtil->format($numberProto, PhoneNumberFormat::E164);

    return [
        'input' => $rawNumber,
        'e164' => $e164,
        'country_calling_code' => '+' . $countryCallingCode,
        'region_code' => $regionCode,
        'country_name' => $countryName,
    ];
}

// 🌍 Array of 15 test phone numbers (international examples)
$numbers = [
    '+14155552671',    // USA
    '+442079460958',   // UK (London)
    '+61293744000',    // Australia (Sydney)
    '+971501234567',   // UAE (Dubai)
    '+919876543210',   // India
    '+33142278100',    // France
    '+81312345678',    // Japan
    '+5511987654321',  // Brazil
    '+4930123456',     // Germany
    '+34912345678',    // Spain
    '+39061234567',    // Italy
    '+27115551234',    // South Africa
    '+2348012345678',  // Nigeria
    '+85221234567',    // Hong Kong
    '+6598765432'      // Singapore
];

// Process all numbers
$results = [];
foreach ($numbers as $num) {
    $info = getCountryInfoFromPhone($num);
    if ($info) {
        $results[] = $info;
    } else {
        $results[] = [
            'input' => $num,
            'e164' => 'Invalid',
            'country_name' => 'Unknown',
            'region_code' => '-',
            'country_calling_code' => '-',
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Phone Number Country Lookup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { max-width: 800px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #007BFF; color: white; }
        h2 { color: #333; text-align: center; }
        tr:hover { background-color: #f1f1f1; }
    </style>
</head>
<body>
<div class="container">
    <h2>🌍 Country Lookup for 15 Phone Numbers</h2>
    <table>
        <tr>
            <th>#</th>
            <th>Entered Number</th>
            <th>E.164 Format</th>
            <th>Country Name</th>
            <th>Region Code</th>
            <th>Country Calling Code</th>
        </tr>
        <?php foreach ($results as $index => $row): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($row['input']) ?></td>
                <td><?= htmlspecialchars($row['e164']) ?></td>
                <td><?= htmlspecialchars($row['country_name']) ?></td>
                <td><?= htmlspecialchars($row['region_code']) ?></td>
                <td><?= htmlspecialchars($row['country_calling_code']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>