<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contact and campus location details from the Department of Basic Education
 * universities directory (official government source).
 *
 * @see https://www.education.gov.za/FurtherStudies/Universities.aspx
 */
class UniversityContactSeeder extends Seeder
{
    private const SOURCE_URL = 'https://www.education.gov.za/FurtherStudies/Universities.aspx';

    /**
     * Seed contact fields for universities that already exist in the catalogue.
     */
    public function run(): void
    {
        if (! Schema::hasColumn('universities', 'contact_email')) {
            return;
        }

        $now = now();

        foreach ($this->contacts() as $abbreviation => $contact) {
            $exists = DB::table('universities')
                ->where('abbreviation', $abbreviation)
                ->exists();

            if (! $exists) {
                continue;
            }

            DB::table('universities')
                ->where('abbreviation', $abbreviation)
                ->update([
                    'postal_address' => $contact['postal_address'],
                    'physical_address' => $contact['physical_address'],
                    'contact_email' => $contact['contact_email'],
                    'contact_phone' => $contact['contact_phone'],
                    'contact_fax' => $contact['contact_fax'],
                    'latitude' => $contact['latitude'],
                    'longitude' => $contact['longitude'],
                    'contact_source_url' => self::SOURCE_URL,
                    'updated_at' => $now,
                ]);
        }
    }

    /**
     * @return array<string, array{
     *     postal_address: ?string,
     *     physical_address: ?string,
     *     contact_email: ?string,
     *     contact_phone: ?string,
     *     contact_fax: ?string,
     *     latitude: ?float,
     *     longitude: ?float
     * }>
     */
    private function contacts(): array
    {
        return [
            'CPUT' => [
                'postal_address' => "P O Box 1906\nBELLVILLE, 7535",
                'physical_address' => "CPUT Bellville Campus\nAdmin Building, 2nd Floor\nSymphony Way, Off Modderdam Road\nBELLVILLE, 7535",
                'contact_email' => 'haupth@cput.ac.za',
                'contact_phone' => '021 959 6201',
                'contact_fax' => '021 951 5422',
                'latitude' => -33.9325000,
                'longitude' => 18.6406000,
            ],
            'UCT' => [
                'postal_address' => "Private Bag\nRONDEBOSCH, 7701",
                'physical_address' => "Bremner Building\nLovers Lane\nRONDEBOSCH",
                'contact_email' => 'vc@uct.ac.za',
                'contact_phone' => '021 650 2105',
                'contact_fax' => '021 650 5100',
                'latitude' => -33.9577000,
                'longitude' => 18.4612000,
            ],
            'CUT' => [
                'postal_address' => "Private Bag X20539\nBLOEMFONTEIN, 9300",
                'physical_address' => "20 President Brand Street\nMahabane Building, 1st Floor\nBloemfontein, 9301",
                'contact_email' => 'vc@cut.ac.za',
                'contact_phone' => '051 507 3001',
                'contact_fax' => '051 507 3310',
                'latitude' => -29.1213000,
                'longitude' => 26.2140000,
            ],
            'DUT' => [
                'postal_address' => "P O Box 1334\nDURBAN, 4000",
                'physical_address' => "Milena Court, Gate 1\n79 Steve Biko Road\nSteve Biko Campus\nDURBAN, 4001",
                'contact_email' => 'vc@dut.ac.za',
                'contact_phone' => '031 373 2474',
                'contact_fax' => '031 373 2011',
                'latitude' => -29.8519000,
                'longitude' => 31.0067000,
            ],
            'UFH' => [
                'postal_address' => "Private Bag X1314\nALICE, 5700",
                'physical_address' => "University of Fort Hare\nAlice Campus\nALICE, 5700",
                'contact_email' => 'kgola@ufh.ac.za',
                'contact_phone' => '040 602 2071',
                'contact_fax' => '040 603 1338',
                'latitude' => -32.7870000,
                'longitude' => 26.8470000,
            ],
            'UFS' => [
                'postal_address' => "PO Box 339\nBLOEMFONTEIN, 9300",
                'physical_address' => "Main Building H11\nNelson Mandela Drive, Brandwag\nBLOEMFONTEIN, 9301",
                'contact_email' => 'rector@ufs.ac.za',
                'contact_phone' => '051 401 7000',
                'contact_fax' => '051 401 3669',
                'latitude' => -29.1089000,
                'longitude' => 26.1867000,
            ],
            'UJ' => [
                'postal_address' => "PO Box 524\nAUCKLAND PARK, 2006",
                'physical_address' => "1st Floor, Madibeng Building\nCnr Kingsway and University Road\nKingsway Campus\nAUCKLAND PARK, 2006",
                'contact_email' => 'Thabom@uj.ac.za',
                'contact_phone' => '011 559 4805',
                'contact_fax' => '011 559 4807',
                'latitude' => -26.1833000,
                'longitude' => 27.9989000,
            ],
            'UKZN' => [
                'postal_address' => "Private Bag X54001\nDURBAN, 4041",
                'physical_address' => "1st Floor Admin Building\nChiltern Hill\nUniversity Road\nWESTVILLE, 3629",
                'contact_email' => 'Reddyj1@ukzn.ac.za',
                'contact_phone' => '031 260 2227',
                'contact_fax' => '031 261 5685',
                'latitude' => -29.8178000,
                'longitude' => 30.9428000,
            ],
            'UL' => [
                'postal_address' => "Private Bag X1106\nSOVENGA, 0727",
                'physical_address' => "A Block, 4th Floor\nMankweng\nSOVENGA, 0727",
                'contact_email' => 'frances.pratt@ul.ac.za',
                'contact_phone' => '015 268 2140',
                'contact_fax' => '015 267 0142',
                'latitude' => -23.8860000,
                'longitude' => 29.7389000,
            ],
            'MUT' => [
                'postal_address' => "P O Box 12363\nJACOBS, 4026",
                'physical_address' => "West Wing\nMangosuthu Highway\nUMLAZI, 4031",
                'contact_email' => 'vc@mut.ac.za',
                'contact_phone' => '031 907 7219',
                'contact_fax' => '031 906 5470',
                'latitude' => -29.9700000,
                'longitude' => 30.9130000,
            ],
            'UMP' => [
                'postal_address' => "Private Bag X11283\nMbombela\n1200",
                'physical_address' => "University of Mpumalanga\nMbombela Campus\nMbombela, 1200",
                'contact_email' => 'Nozuko.ngqukana@ump.ac.za',
                'contact_phone' => '013 002 0011',
                'contact_fax' => '086 516 4284',
                'latitude' => -25.4750000,
                'longitude' => 30.9690000,
            ],
            'NMU' => [
                'postal_address' => "PO Box 77000\nPORT ELIZABETH, 6031",
                'physical_address' => "Main Building, 18th Floor, Room 1801\nNMMU South Campus, University Road\nSUMMERSTRAND, PORT ELIZABETH, 6001",
                'contact_email' => 'vc@mandela.ac.za',
                'contact_phone' => '041 504 3211',
                'contact_fax' => '041 504 9211',
                'latitude' => -34.0089000,
                'longitude' => 25.6706000,
            ],
            'NWU' => [
                'postal_address' => "Private Bag X6001\nPOTCHEFSTROOM, 2520",
                'physical_address' => "Joon van Rooyen Building\n11 Hoffman Street\nPOTCHEFSTROOM, 2521",
                'contact_email' => 'lerato.tsagae@nwu.ac.za',
                'contact_phone' => '018 299 4901',
                'contact_fax' => '018 299 4910',
                'latitude' => -26.6886000,
                'longitude' => 27.0931000,
            ],
            'UP' => [
                'postal_address' => 'Private Bag X20, Hatfield, 0028',
                'physical_address' => "Lynnwood Road\nAdmin Building 433\nUniversity of Pretoria\nPRETORIA, 0002",
                'contact_email' => 'rector@up.ac.za',
                'contact_phone' => '012 420 2900',
                'contact_fax' => '012 420 4530',
                'latitude' => -25.7545000,
                'longitude' => 28.2314000,
            ],
            'RU' => [
                'postal_address' => "PO Box 94\nGRAHAMSTOWN, 6140",
                'physical_address' => "Vice-Chancellor's Office\nRhodes University\nMain Administration Building\nDrostdy Road\nGRAHAMSTOWN, 6139",
                'contact_email' => 'vc@ru.ac.za',
                'contact_phone' => '046 603 8148',
                'contact_fax' => '046 622 8444',
                'latitude' => -33.3135000,
                'longitude' => 26.5200000,
            ],
            'SMU' => [
                'postal_address' => "PO Box 203\nMedunsa\n0204",
                'physical_address' => "Sefako Makgatho Health Sciences University\nMedunsa Campus\nGa-Rankuwa, 0204",
                'contact_email' => 'erica.ehlers@smu.ac.za',
                'contact_phone' => '012 302 2002',
                'contact_fax' => '012 560 0274',
                'latitude' => -25.6180000,
                'longitude' => 28.0160000,
            ],
            'SPU' => [
                'postal_address' => "Private Bag X5008\nKimberley\n8300",
                'physical_address' => "North Campus, Chapel Street\nKimberley, 8300",
                'contact_email' => 'yunus.ballim@spu.ac.za',
                'contact_phone' => '+27 53 491 0120',
                'contact_fax' => '086 416 2285',
                'latitude' => -28.7450000,
                'longitude' => 24.7710000,
            ],
            'UNISA' => [
                'postal_address' => "PO Box 392\nPRETORIA, 0003",
                'physical_address' => "OR Tambo Building\n13th Floor, Office 15\nPreller Street, Muckleneuk Ridge\nPRETORIA, 0001",
                'contact_email' => 'Moganma@unisa.ac.za',
                'contact_phone' => '012 429 2550',
                'contact_fax' => '012 429 2565',
                'latitude' => -25.7679000,
                'longitude' => 28.1997000,
            ],
            'SU' => [
                'postal_address' => "Private Bag X1\nMATIELAND, 7602",
                'physical_address' => "Admin Building B\nVictoria Street\nSTELLENBOSCH, 7602",
                'contact_email' => 'vc@sun.ac.za',
                'contact_phone' => '021 808 4490',
                'contact_fax' => '021 808 3714',
                'latitude' => -33.9328000,
                'longitude' => 18.8644000,
            ],
            'TUT' => [
                'postal_address' => "Private Bag X680\nPRETORIA, 0001",
                'physical_address' => "Building 21, 5th Floor, Room 556\nStaatsartillery Road\nPRETORIA-WEST, 0183",
                'contact_email' => 'vc@tut.ac.za',
                'contact_phone' => '012 382 4112',
                'contact_fax' => '012 382 5422',
                'latitude' => -25.7319000,
                'longitude' => 28.1619000,
            ],
            'VUT' => [
                'postal_address' => "Private Bag X021\nVANDERBIJLPARK, 1911",
                'physical_address' => "A Block\nCnr Andries Potgieter Boulevard & Barrage Road\nVANDERBIJLPARK, 1911",
                'contact_email' => 'gapenyanes@vut.ac.za',
                'contact_phone' => '016 950 9215',
                'contact_fax' => '016 950 9800',
                'latitude' => -26.7100000,
                'longitude' => 27.8500000,
            ],
            'UNIVEN' => [
                'postal_address' => "Private Bag X5050\nTHOHOYANDOU, 0950",
                'physical_address' => "Admin Block, Office 4\nUniversity of Venda\nMphephu Street\nTHOHOYANDOU, 0950",
                'contact_email' => 'vice.chancellor@univen.ac.za',
                'contact_phone' => '015 962 8316',
                'contact_fax' => '015 962 4742',
                'latitude' => -22.9760000,
                'longitude' => 30.4440000,
            ],
            'WSU' => [
                'postal_address' => "Private Bag X1\nUMTATA",
                'physical_address' => "Nelson Mandela Drive\nMTHATHA, 5117",
                'contact_email' => 'vc@wsu.ac.za',
                'contact_phone' => '047 531 2267',
                'contact_fax' => '047 502 2970',
                'latitude' => -31.5880000,
                'longitude' => 28.7900000,
            ],
            'UWC' => [
                'postal_address' => "Private Bag X17\nBELLVILLE, 7535",
                'physical_address' => "Rector's Office, 3rd Floor\nAdmin Building\nModderdam Road\nBELLVILLE, 7535",
                'contact_email' => 'rector@uwc.ac.za',
                'contact_phone' => '021 959 2101',
                'contact_fax' => '021 959 2973',
                'latitude' => -33.9330000,
                'longitude' => 18.6280000,
            ],
            'WITS' => [
                'postal_address' => "Private Bag 3\nWITS, 2050",
                'physical_address' => "Senate House Building\n11th Floor\n3 Jorrison Street\nBRAAMFONTEIN, 2017",
                'contact_email' => 'sammy.masehe1@wits.ac.za',
                'contact_phone' => '011 717 1101',
                'contact_fax' => '011 339 8215',
                'latitude' => -26.1929000,
                'longitude' => 28.0305000,
            ],
            'UNIZULU' => [
                'postal_address' => "Private Bag X1001\nKwa-Dlangezwa, 3886",
                'physical_address' => "Admin Building, 4th floor\nAnne Cooke Drive\nKWADLANGEZWA",
                'contact_email' => 'bhengun@unizulu.ac.za',
                'contact_phone' => '035 902 6634',
                'contact_fax' => '035 902 6601',
                'latitude' => -28.8560000,
                'longitude' => 31.8490000,
            ],
        ];
    }
}
