<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UniversityLogoSeeder extends Seeder
{
    private const LOGOS = [
        'BCC' => 'https://bccollege.co.za/wp-content/uploads/2026/03/bcc-logo-e1772658171904.png',
        'BOLAND' => 'https://bolandcollege.com/wp-content/uploads/2022/11/Boland-Logo.png',
        'CPUT' => 'images/universities/cput.png',
        'CUT' => 'https://www.cut.ac.za/Images/Site/cut-u-logo.png',
        'DUT' => 'https://www.dut.ac.za/wp-content/uploads/2026/03/DUT-Logo_new-1.png',
        'EDUVOS' => 'https://www.eduvos.com/logo.png',
        'EEC' => 'https://eec.edu.za/wp-content/uploads/2021/06/eec-LOGO.png',
        'EHLANZENI' => 'https://decisive-serenity-7fc9a48de1.media.strapiapp.com/ehlanzeni_college_logo_208e052816.png',
        'CAPRICORN' => 'https://capricorncollege.edu.za/wp-content/uploads/2021/12/cropped-capricornTVETlogo.png',
        'COASTAL' => 'https://www.coastalkzn.co.za/images/ckzn_logo.png',
        'CCT' => 'https://www.cct.edu.za/cctblk_logo.png',
        'EMC' => 'https://www.google.com/s2/favicons?domain=emcol.co.za&sz=256',
        'ELANGENI' => 'https://www.elangeni.edu.za/wp-content/uploads/2024/05/Elangeni-logo-Colour-Vector.png',
        'ESAYIDI' => 'https://esayiditvet.co.za/2025/wp-content/uploads/2023/02/footerlogo-300x171.png',
        'FALSEBAY' => 'https://falsebaycollege.co.za/wp-content/uploads/2022/01/Group-1647.svg',
        'FLAVIUS' => 'https://www.matrichub.co.za/wp-content/uploads/2023/11/Flavius-Mareka-TVET-College.png',
        'GSC' => 'https://www.careersportal.co.za/sites/default/files/styles/max_2600x2600/public/gsc_logo.jpg?itok=z8UtlkhE',
        'GOLDFIELDS' => 'https://www.google.com/s2/favicons?domain=goldfieldstvet.edu.za&sz=256',
        'IKHALA' => 'https://www.google.com/s2/favicons?domain=ikhala.edu.za&sz=256',
        'INGWE' => 'https://www.google.com/s2/favicons?domain=ingwecollege.edu.za&sz=256',
        'KHC' => 'https://www.google.com/s2/favicons?domain=khc.edu.za&sz=256',
        'KSD' => 'https://www.google.com/s2/favicons?domain=ksdcollege.edu.za&sz=256',
        'LEPHALALE' => 'https://www.google.com/s2/favicons?domain=leptvetcol.edu.za&sz=256',
        'LETABA' => 'https://www.google.com/s2/favicons?domain=letcol.co.za&sz=256',
        'LOVEDALE' => 'https://www.google.com/s2/favicons?domain=lovedale.edu.za&sz=256',
        'MAJUBA' => 'https://www.google.com/s2/favicons?domain=majuba.edu.za&sz=256',
        'MALUTI' => 'https://www.google.com/s2/favicons?domain=malutitvet.co.za&sz=256',
        'MNAMBITHI' => 'https://www.google.com/s2/favicons?domain=mnambithicollege.co.za&sz=256',
        'MOPANI' => 'https://www.google.com/s2/favicons?domain=mopanicollege.edu.za&sz=256',
        'MOTHEO' => 'https://era.thestudenthub.co.za/logo/1668083406.jpg',
        'MTHASHANA' => 'https://www.google.com/s2/favicons?domain=mthashanacollege.co.za&sz=256',
        'NKANGALA' => 'https://www.google.com/s2/favicons?domain=ntc.edu.za&sz=256',
        'NCR' => 'https://www.google.com/s2/favicons?domain=ncrtvet.com&sz=256',
        'NCU' => 'https://www.google.com/s2/favicons?domain=ncutvet.edu.za&sz=256',
        'NORTHLINK' => 'https://www.google.com/s2/favicons?domain=northlink.co.za&sz=256',
        'ORBIT' => 'https://www.google.com/s2/favicons?domain=orbitcollege.co.za&sz=256',
        'PEC' => 'https://www.google.com/s2/favicons?domain=pecollege.edu.za&sz=256',
        'SEDCOL' => 'https://www.google.com/s2/favicons?domain=sedcol.co.za&sz=256',
        'SEKHUKHUNE' => 'https://www.google.com/s2/favicons?domain=sekhukhunetvet.edu.za&sz=256',
        'SWGC' => 'https://www.google.com/s2/favicons?domain=swgc.co.za&sz=256',
        'TALETSO' => 'https://www.google.com/s2/favicons?domain=taletso.edu.za&sz=256',
        'THEKWINI' => 'https://www.google.com/s2/favicons?domain=thekwini.edu.za&sz=256',
        'UMFOLOZI' => 'https://www.google.com/s2/favicons?domain=umfolozicollege.co.za&sz=256',
        'UMGUNGUNDLOVU' => 'https://media.licdn.com/dms/image/v2/D4E05AQEcKa_bkeQorg/feedshare-thumbnail_720_1280/feedshare-thumbnail_720_1280/0/1701273282848?e=2147483647&t=jPDC1ODmMjIevw03yRxgFU7WGS3Lbl_48r0pum0DlHE&v=beta',
        'VHEMBE' => 'https://www.google.com/s2/favicons?domain=vhembecollege.edu.za&sz=256',
        'VUSELELA' => 'https://www.google.com/s2/favicons?domain=vuselelacollege.co.za&sz=256',
        'WATERBERG' => 'https://www.google.com/s2/favicons?domain=waterbergcollege.co.za&sz=256',
        'WESTCOAST' => 'https://www.google.com/s2/favicons?domain=westcoastcollege.co.za&sz=256',
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
