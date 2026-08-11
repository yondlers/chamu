<?php

namespace App\Support;

/**
 * Public TVET college directory from the Department of Higher Education and Training.
 *
 * @see https://www.dhet.gov.za/RegionalOffices/educational-institutions/technical-vocational-education-and-training-colleges-tvet-colleges.html
 */
final class TvetColleges
{
    public const SOURCE_URL = 'https://www.dhet.gov.za/RegionalOffices/educational-institutions/technical-vocational-education-and-training-colleges-tvet-colleges.html';

    /**
     * Abbreviations that may not include "TVET" in the stored university name.
     *
     * @var list<string>
     */
    public const SPECIAL_NAME_ABBREVIATIONS = [
        'BOLAND',
        'CCT',
        'WESTCOL',
    ];

    public static function isTvet(?string $abbreviation, ?string $name): bool
    {
        $abbreviation = strtoupper(trim((string) $abbreviation));

        if ($abbreviation !== '' && in_array($abbreviation, self::abbreviations(), true)) {
            return true;
        }

        $name = strtolower((string) $name);

        return str_contains($name, 'tvet')
            || str_contains($name, 'fet college')
            || str_contains($name, 'college of cape town');
    }

    /**
     * @return list<string>
     */
    public static function abbreviations(): array
    {
        return array_keys(self::directory());
    }

    /**
     * @return array<string, array{
     *     name: string,
     *     province: string,
     *     website: ?string,
     *     phone: ?string,
     *     latitude: float,
     *     longitude: float
     * }>
     */
    public static function directory(): array
    {
        return [
            // Eastern Cape
            'BCC' => [
                'name' => 'Buffalo City TVET College',
                'province' => 'Eastern Cape',
                'website' => 'https://www.bccollege.co.za',
                'phone' => '043 704 9201',
                'latitude' => -32.9962200,
                'longitude' => 27.8992500,
            ],
            'EMC' => [
                'name' => 'Eastcape Midlands TVET College',
                'province' => 'Eastern Cape',
                'website' => 'https://www.emcol.co.za',
                'phone' => '086 038 8879',
                'latitude' => -33.7898000,
                'longitude' => 25.4140100,
            ],
            'IKHALA' => [
                'name' => 'Ikhala TVET College',
                'province' => 'Eastern Cape',
                'website' => 'https://www.ikhalacollege.co.za',
                'phone' => '047 873 8843',
                'latitude' => -31.9097850,
                'longitude' => 26.9670090,
            ],
            'INGWE' => [
                'name' => 'Ingwe TVET College',
                'province' => 'Eastern Cape',
                'website' => 'https://www.ingwecollege.co.za',
                'phone' => '039 255 0346',
                'latitude' => -30.8997580,
                'longitude' => 28.9937660,
            ],
            'KHC' => [
                'name' => 'King Hintsa TVET College',
                'province' => 'Eastern Cape',
                'website' => 'https://www.kinghintsacollege.edu.za',
                'phone' => '047 401 6400',
                'latitude' => -32.3272060,
                'longitude' => 28.1739810,
            ],
            'KSD' => [
                'name' => 'King Sabata Dalindyebo TVET College',
                'province' => 'Eastern Cape',
                'website' => null,
                'phone' => '047 505 1001',
                'latitude' => -31.5950800,
                'longitude' => 28.7972600,
            ],
            'LOVEDALE' => [
                'name' => 'Lovedale TVET College',
                'province' => 'Eastern Cape',
                'website' => 'https://www.lovedalecollege.co.za',
                'phone' => '043 642 1331',
                'latitude' => -32.8720000,
                'longitude' => 27.3889530,
            ],
            'PEC' => [
                'name' => 'Port Elizabeth TVET College',
                'province' => 'Eastern Cape',
                'website' => 'https://www.pecollege.edu.za',
                'phone' => '041 585 7771',
                'latitude' => -33.9609800,
                'longitude' => 25.6109800,
            ],

            // Free State
            'FLAVIUS' => [
                'name' => 'Flavius Mareka TVET College',
                'province' => 'Free State',
                'website' => 'https://www.flaviusmareka.net',
                'phone' => '016 976 0815',
                'latitude' => -26.8245600,
                'longitude' => 27.8417800,
            ],
            'GOLDFIELDS' => [
                'name' => 'Goldfields TVET College',
                'province' => 'Free State',
                'website' => 'https://www.goldfieldsfet.edu.za',
                'phone' => '057 392 1027',
                'latitude' => -27.9835660,
                'longitude' => 26.7750730,
            ],
            'MALUTI' => [
                'name' => 'Maluti TVET College',
                'province' => 'Free State',
                'website' => 'https://www.malutifet.org.za',
                'phone' => '058 713 3048',
                'latitude' => -28.5270200,
                'longitude' => 28.8002440,
            ],
            'MOTHEO' => [
                'name' => 'Motheo TVET College',
                'province' => 'Free State',
                'website' => 'https://www.motheofet.co.za',
                'phone' => '051 406 9300',
                'latitude' => -29.1233840,
                'longitude' => 26.2212430,
            ],

            // Gauteng
            'CJC' => [
                'name' => 'Central Johannesburg TVET College',
                'province' => 'Gauteng',
                'website' => 'https://www.cjc.co.za',
                'phone' => '011 484 1388',
                'latitude' => -26.1749000,
                'longitude' => 28.0490200,
            ],
            'EEC' => [
                'name' => 'Ekurhuleni East TVET College',
                'province' => 'Gauteng',
                'website' => 'https://www.eec.edu.za',
                'phone' => '011 736 4400',
                'latitude' => -26.2889400,
                'longitude' => 28.4090800,
            ],
            'EWC' => [
                'name' => 'Ekurhuleni West TVET College',
                'province' => 'Gauteng',
                'website' => 'https://www.ewc.edu.za',
                'phone' => '011 323 1600',
                'latitude' => -26.2886417,
                'longitude' => 28.4055683,
            ],
            'SEDCOL' => [
                'name' => 'Sedibeng TVET College',
                'province' => 'Gauteng',
                'website' => 'https://www.sedcol.co.za',
                'phone' => '016 422 6645',
                'latitude' => -26.1869350,
                'longitude' => 27.6774300,
            ],
            'SWGC' => [
                'name' => 'South West Gauteng TVET College',
                'province' => 'Gauteng',
                'website' => 'https://www.swgc.co.za',
                'phone' => '011 984 1260',
                'latitude' => -26.6782533,
                'longitude' => 27.9309300,
            ],
            'TNC' => [
                'name' => 'Tshwane North TVET College',
                'province' => 'Gauteng',
                'website' => 'https://www.tnc4fet.co.za',
                'phone' => '012 401 1950',
                'latitude' => -26.2141067,
                'longitude' => 27.8745633,
            ],
            'TSC' => [
                'name' => 'Tshwane South TVET College',
                'province' => 'Gauteng',
                'website' => 'https://www.tsc.edu.za',
                'phone' => '012 401 5000',
                'latitude' => -25.7503067,
                'longitude' => 28.1822567,
            ],
            'WESTCOL' => [
                'name' => 'Western TVET College',
                'province' => 'Gauteng',
                'website' => 'https://www.westcol.co.za',
                'phone' => '011 693 3608',
                'latitude' => -27.4236220,
                'longitude' => 26.1014550,
            ],

            // KwaZulu-Natal
            'COASTAL' => [
                'name' => 'Coastal TVET College',
                'province' => 'KwaZulu-Natal',
                'website' => 'https://www.coastalkzn.co.za',
                'phone' => '031 905 7000',
                'latitude' => -30.5124090,
                'longitude' => 30.0133210,
            ],
            'ELANGENI' => [
                'name' => 'Elangeni TVET College',
                'province' => 'KwaZulu-Natal',
                'website' => 'https://www.efet.co.za',
                'phone' => '031 716 6700',
                'latitude' => -29.8232950,
                'longitude' => 30.8696510,
            ],
            'ESAYIDI' => [
                'name' => 'Esayidi TVET College',
                'province' => 'KwaZulu-Natal',
                'website' => 'https://www.esayidifet.co.za',
                'phone' => '039 684 0110',
                'latitude' => -30.2710510,
                'longitude' => 30.4412080,
            ],
            'MAJUBA' => [
                'name' => 'Majuba TVET College',
                'province' => 'KwaZulu-Natal',
                'website' => 'https://www.majuba.edu.za',
                'phone' => '034 326 4888',
                'latitude' => -29.5638500,
                'longitude' => 27.4553700,
            ],
            'MNAMBITHI' => [
                'name' => 'Mnambithi TVET College',
                'province' => 'KwaZulu-Natal',
                'website' => null,
                'phone' => '036 368 3800',
                'latitude' => -29.4640800,
                'longitude' => 28.3345900,
            ],
            'MTHASHANA' => [
                'name' => 'Mthashana TVET College',
                'province' => 'KwaZulu-Natal',
                'website' => 'https://www.mthashanafet.co.za',
                'phone' => '034 980 1010',
                'latitude' => -30.4837600,
                'longitude' => 27.4626500,
            ],
            'THEKWINI' => [
                'name' => 'Thekwini TVET College',
                'province' => 'KwaZulu-Natal',
                'website' => 'https://www.thekwinicollege.co.za',
                'phone' => '031 250 8400',
                // DHET lists longitude as 20.49…; corrected to the KZN coastal longitude range.
                'latitude' => -30.5911592,
                'longitude' => 30.4932641,
            ],
            'UMFOLOZI' => [
                'name' => 'Umfolozi TVET College',
                'province' => 'KwaZulu-Natal',
                'website' => 'https://www.umfolozicollege.co.za',
                'phone' => '035 902 9503',
                'latitude' => -32.0760870,
                'longitude' => 28.7644610,
            ],
            'UMGUNGUNDLOVU' => [
                'name' => 'Umgungundlovu TVET College',
                'province' => 'KwaZulu-Natal',
                'website' => 'https://www.ufetc.edu.za',
                'phone' => '033 341 2102',
                'latitude' => -30.2254600,
                'longitude' => 29.3676500,
            ],

            // Limpopo
            'CAPRICORN' => [
                'name' => 'Capricorn TVET College',
                'province' => 'Limpopo',
                'website' => 'https://www.capricorncollege.edu.za',
                'phone' => '015 291 3118',
                'latitude' => -23.5330240,
                'longitude' => 29.2729430,
            ],
            'LEPHALALE' => [
                'name' => 'Lephalale TVET College',
                'province' => 'Limpopo',
                'website' => 'https://www.lephalalefetcollege.co.za',
                'phone' => '014 763 2252',
                'latitude' => -23.6840700,
                'longitude' => 27.6928890,
            ],
            'LETABA' => [
                'name' => 'Letaba TVET College',
                'province' => 'Limpopo',
                'website' => 'https://www.letabafet.co.za',
                'phone' => '015 307 5440',
                'latitude' => -23.8356980,
                'longitude' => 30.1626970,
            ],
            'MOPANI' => [
                'name' => 'Mopani South East TVET College',
                'province' => 'Limpopo',
                'website' => 'https://www.mopanicollege.edu.za',
                'phone' => '015 781 5721',
                'latitude' => -23.9467320,
                'longitude' => 31.1389690,
            ],
            'SEKHUKHUNE' => [
                'name' => 'Sekhukhune TVET College',
                'province' => 'Limpopo',
                'website' => 'https://www.sekfetcol.co.za',
                'phone' => '013 269 0278',
                'latitude' => -25.0941910,
                'longitude' => 29.2424400,
            ],
            'VHEMBE' => [
                'name' => 'Vhembe TVET College',
                'province' => 'Limpopo',
                'website' => 'https://www.vhembefet.co.za',
                'phone' => '015 963 3156',
                'latitude' => -23.0377900,
                'longitude' => 29.9108600,
            ],
            'WATERBERG' => [
                'name' => 'Waterberg TVET College',
                'province' => 'Limpopo',
                'website' => 'https://www.waterbergcollege.co.za',
                'phone' => '015 491 8581',
                'latitude' => -24.1799730,
                'longitude' => 29.0164090,
            ],

            // Mpumalanga
            'EHLANZENI' => [
                'name' => 'Ehlanzeni TVET College',
                'province' => 'Mpumalanga',
                'website' => 'https://www.ehlanzenicollege.co.za',
                'phone' => '013 752 7105',
                'latitude' => -25.4731300,
                'longitude' => 30.9792600,
            ],
            'GSC' => [
                'name' => 'Gert Sibande TVET College',
                'province' => 'Mpumalanga',
                'website' => 'https://www.gscollege.co.za',
                'phone' => '017 712 9040',
                'latitude' => -26.5659540,
                'longitude' => 29.1433040,
            ],
            'NKANGALA' => [
                'name' => 'Nkangala TVET College',
                'province' => 'Mpumalanga',
                'website' => 'https://www.nkangalafet.edu.za',
                'phone' => '013 690 1430',
                'latitude' => -25.8776630,
                'longitude' => 29.2161640,
            ],

            // Northern Cape
            'NCR' => [
                'name' => 'Northern Cape Rural TVET College',
                'province' => 'Northern Cape',
                'website' => 'https://www.ncrfet.edu.za',
                'phone' => '054 331 3836',
                'latitude' => -28.4361600,
                'longitude' => 21.2133700,
            ],
            'NCU' => [
                'name' => 'Northern Cape Urban TVET College',
                'province' => 'Northern Cape',
                'website' => 'https://www.ncufetcollege.edu.za',
                'phone' => '053 839 2000',
                'latitude' => -28.7450300,
                'longitude' => 24.7662200,
            ],

            // North West
            'ORBIT' => [
                'name' => 'Orbit TVET College',
                'province' => 'North West',
                'website' => 'https://www.orbitcollege.co.za',
                'phone' => '014 592 7014',
                'latitude' => -25.6378700,
                'longitude' => 27.7763200,
            ],
            'TALETSO' => [
                'name' => 'Taletso TVET College',
                'province' => 'North West',
                'website' => 'https://www.taletsofetcollege.co.za',
                'phone' => '018 384 2346',
                'latitude' => -25.8283510,
                'longitude' => 25.6155480,
            ],
            'VUSELELA' => [
                'name' => 'Vuselela TVET College',
                'province' => 'North West',
                'website' => 'https://www.vuselelacollege.co.za',
                'phone' => '018 406 7800',
                'latitude' => -26.8634700,
                'longitude' => 26.6654270,
            ],

            // Western Cape
            'BOLAND' => [
                'name' => 'Boland TVET College',
                'province' => 'Western Cape',
                'website' => 'https://www.bolandcollege.com',
                'phone' => '021 886 7111',
                'latitude' => -33.9266667,
                'longitude' => 18.8566667,
            ],
            'CCT' => [
                'name' => 'College of Cape Town',
                'province' => 'Western Cape',
                'website' => 'https://www.cct.edu.za',
                'phone' => '021 404 6700',
                'latitude' => -33.5542100,
                'longitude' => 18.2725870,
            ],
            'FALSEBAY' => [
                'name' => 'False Bay TVET College',
                'province' => 'Western Cape',
                'website' => 'https://www.falsebaycollege.co.za',
                'phone' => '021 003 0600',
                'latitude' => -34.6267400,
                'longitude' => 18.2624600,
            ],
            'NORTHLINK' => [
                'name' => 'Northlink TVET College',
                'province' => 'Western Cape',
                'website' => 'https://www.northlink.co.za',
                'phone' => '021 970 9000',
                'latitude' => -33.5536270,
                'longitude' => 18.5122280,
            ],
            'SCC' => [
                'name' => 'South Cape TVET College',
                'province' => 'Western Cape',
                'website' => 'https://www.sccollege.co.za',
                'phone' => '044 884 0359',
                'latitude' => -33.5756200,
                'longitude' => 22.2794400,
            ],
            'WESTCOAST' => [
                'name' => 'West Coast TVET College',
                'province' => 'Western Cape',
                'website' => 'https://www.westcoastcollege.co.za',
                'phone' => '022 482 1143',
                'latitude' => -33.4621000,
                'longitude' => 18.7296000,
            ],
        ];
    }
}
