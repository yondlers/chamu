<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UniversityLogoSeeder extends Seeder
{
    private const LOGOS = [
        'BOLAND' => 'https://bolandcollege.com/wp-content/uploads/2022/11/Boland-Logo.png',
        'CPUT' => 'images/universities/cput.png',
        'CUT' => 'https://www.cut.ac.za/Images/Site/cut-u-logo.png',
        'DUT' => 'https://www.dut.ac.za/wp-content/uploads/2026/03/DUT-Logo_new-1.png',
        'EDUVOS' => 'https://www.eduvos.com/logo.png',
        'NMU' => 'https://webapps.mandela.ac.za/design/Resources/images/logos/FullColourLogo.PNG',
        'NWU' => 'https://www.nwu.ac.za/sites/www.nwu.ac.za/files/NWU-logo-pers_1.png',
        'RU' => 'https://www.ru.ac.za/media/rhodesuniversity/styleassets/2019v6/images/RU_Logo_1.png',
        'SCC' => 'https://sccollege.co.za/wp-content/uploads/2022/05/SCC-Horizontal.png',
        'SU' => 'https://www.su.ac.za/themes/custom/su2023/images/logo.svg',
        'TUT' => 'https://www.tut.ac.za/media/tshwane-interim/site-assets/images/tut-logo.svg',
        'UCT' => 'https://uct.ac.za/themes/custom/blip_uct/logo.svg',
        'UFH' => 'https://www.ufh.ac.za/wp-content/uploads/2024/08/UFH-Logo-web.svg',
        'UFS' => 'https://www.ufs.ac.za/images/librariesprovider5/ufs_redesign_2021/ufsheaderlogo.svg',
        'UJ' => 'images/universities/uj.svg',
        'UKZN' => 'https://ukzn.ac.za/wp-content/uploads/2020/03/Transp_bg.png',
        'UL' => 'https://www.ul.ac.za/wp-content/uploads/2023/10/university-of-limpopo-logo.png',
        'UMP' => 'https://www.ump.ac.za/images/logo.png',
        'UNIVEN' => 'https://www.univen.ac.za/wp-content/uploads/2026/02/logo.png',
        'UP' => 'https://www.up.ac.za/themes/custom/up2024/images/horizontal-logo-bg.png',
        'UWC' => 'https://uwc-za.b-cdn.net/files/images/UWC-2025-trilingual-landscape.svg',
        'VC' => 'https://www.emeris.ac.za/img/emeris-logo-teal.svg',
        'VUT' => 'https://vut.ac.za/wp-content/uploads/2026/03/Vaal-University-of-Technology-60th-logo-scaled-300x72.webp',
        'WESTCOL' => 'https://westcol.co.za/wp-content/uploads/2025/09/Westcol-College-Logo-Main-1024x98.png',
        'WITS' => 'https://www.wits.ac.za/media/wits-university-style-assets/images/wits-logo.svg',
        'WSU' => 'https://www.wsu.ac.za/images/header-logo-main.png',
    ];

    private const STALE_LOGOS = [
        'CPUT' => [
            'https://www.cput.ac.za/images/About/Brand%20ID/img_branding_logo_correct.jpg',
        ],
        'UJ' => [
            'https://pure.uj.ac.za/skin/headerImage/',
            'https://www.uj.ac.za/wp-content/uploads/2026/03/uj_logo.jpg',
        ],
    ];

    public function run(): void
    {
        foreach (self::LOGOS as $abbreviation => $logo) {
            DB::table('universities')
                ->where('abbreviation', $abbreviation)
                ->where(function ($query) use ($abbreviation): void {
                    $query
                        ->whereNull('logo')
                        ->orWhere('logo', '')
                        ->orWhere('logo', 'images/universities/'.strtolower($abbreviation).'.png')
                        ->orWhereIn('logo', self::STALE_LOGOS[$abbreviation] ?? []);
                })
                ->update([
                    'logo' => $logo,
                    'updated_at' => now(),
                ]);
        }
    }

    public static function logoFor(string $abbreviation, ?string $existingLogo = null): ?string
    {
        if ($existingLogo !== null
            && $existingLogo !== ''
            && ! str_starts_with($existingLogo, 'images/universities/')
            && ! in_array($existingLogo, self::STALE_LOGOS[$abbreviation] ?? [], true)
        ) {
            return $existingLogo;
        }

        return self::LOGOS[$abbreviation] ?? null;
    }
}
