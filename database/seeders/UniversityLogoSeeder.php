<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UniversityLogoSeeder extends Seeder
{
    private const LOGOS = [
        'CPUT' => 'https://www.cput.ac.za/images/About/Brand%20ID/img_branding_logo_correct.jpg',
        'CUT' => 'https://www.cut.ac.za/Images/Site/cut-u-logo.png',
        'DUT' => 'https://www.dut.ac.za/wp-content/uploads/2026/03/DUT-Logo_new-1.png',
        'NMU' => 'https://webapps.mandela.ac.za/design/Resources/images/logos/FullColourLogo.PNG',
        'NWU' => 'https://www.nwu.ac.za/sites/www.nwu.ac.za/files/NWU-logo-pers_1.png',
        'RU' => 'https://www.ru.ac.za/media/rhodesuniversity/styleassets/2019v6/images/RU_Logo_1.png',
        'SU' => 'https://www.su.ac.za/themes/custom/su2023/images/logo.svg',
        'TUT' => 'https://www.tut.ac.za/media/tshwane-interim/site-assets/images/tut-logo.svg',
        'UCT' => 'https://uct.ac.za/themes/custom/blip_uct/logo.svg',
        'UFH' => 'https://www.ufh.ac.za/wp-content/uploads/2024/08/UFH-Logo-web.svg',
        'UFS' => 'https://www.ufs.ac.za/images/librariesprovider5/ufs_redesign_2021/ufsheaderlogo.svg',
        'UJ' => 'https://pure.uj.ac.za/skin/headerImage/',
        'UKZN' => 'https://ukzn.ac.za/wp-content/uploads/2020/03/Transp_bg.png',
        'UL' => 'https://www.ul.ac.za/wp-content/uploads/2023/10/university-of-limpopo-logo.png',
        'UMP' => 'https://www.ump.ac.za/images/logo.png',
        'UNIVEN' => 'https://www.univen.ac.za/wp-content/uploads/2026/02/logo.png',
        'UP' => 'https://www.up.ac.za/themes/custom/up2024/images/horizontal-logo-bg.png',
        'UWC' => 'https://uwc-za.b-cdn.net/files/images/UWC-2025-trilingual-landscape.svg',
        'VC' => 'https://www.emeris.ac.za/img/emeris-logo-teal.svg',
        'VUT' => 'https://vut.ac.za/wp-content/uploads/2026/03/Vaal-University-of-Technology-60th-logo-scaled-300x72.webp',
        'WITS' => 'https://www.wits.ac.za/media/wits-university-style-assets/images/wits-logo.svg',
        'WSU' => 'https://www.wsu.ac.za/images/header-logo-main.png',
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
                        ->orWhere('logo', 'images/universities/'.strtolower($abbreviation).'.png');
                })
                ->update([
                    'logo' => $logo,
                    'updated_at' => now(),
                ]);
        }
    }

    public static function logoFor(string $abbreviation, ?string $existingLogo = null): ?string
    {
        if ($existingLogo !== null && $existingLogo !== '' && ! str_starts_with($existingLogo, 'images/universities/')) {
            return $existingLogo;
        }

        return self::LOGOS[$abbreviation] ?? null;
    }
}
