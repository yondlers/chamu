<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Apply collected TVET college logos, websites and prospectus/source URLs.
 */
class TvetCollegeAssetsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $hasContact = Schema::hasColumn('universities', 'contact_source_url');

        foreach (self::assets() as $abbreviation => $asset) {
            $values = [
                'logo' => $asset['logo'],
                'website' => $asset['website'],
                'updated_at' => $now,
            ];

            if ($hasContact) {
                $values['contact_source_url'] = $asset['prospectus_url'];
            }

            DB::table('universities')
                ->where('abbreviation', $abbreviation)
                ->update($values);
        }
    }

    /**
     * @return array<string, array{logo: string, website: string, prospectus_url: string}>
     */
    public static function assets(): array
    {
        return [
            'CAPRICORN' => [
                'logo' => 'https://capricorncollege.edu.za/wp-content/uploads/2021/12/cropped-capricornTVETlogo.png',
                'website' => 'https://capricorncollege.edu.za/',
                'prospectus_url' => 'https://capricorncollege.edu.za/download-view-prospectus/',
            ],
            'COASTAL' => [
                'logo' => 'https://www.coastalkzn.co.za/images/ckzn_logo.png',
                'website' => 'https://www.coastalkzn.co.za/',
                'prospectus_url' => 'https://www.coastalkzn.co.za/programmes_ncv.html',
            ],
            'CCT' => [
                'logo' => 'https://www.cct.edu.za/cctblk_logo.png',
                'website' => 'https://www.cct.edu.za/',
                'prospectus_url' => 'https://cct.edu.za/index.php/en/programmes/mainbusiness-studies/nc-v-finance-economics-accounting-l2-4',
            ],
            'EMC' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=emcol.co.za&sz=256',
                'website' => 'https://emcol.co.za/',
                'prospectus_url' => 'https://emcol.co.za/index.php/business-studies/',
            ],
            'ELANGENI' => [
                'logo' => 'https://www.elangeni.edu.za/wp-content/uploads/2024/05/Elangeni-logo-Colour-Vector.png',
                'website' => 'https://www.elangeni.edu.za/',
                'prospectus_url' => 'https://www.elangeni.edu.za/wp-content/uploads/2024/08/ETVET_PROSPECTUS_UPDATED_2023_Compressed-1.pdf',
            ],
            'ESAYIDI' => [
                'logo' => 'https://esayiditvet.co.za/2025/wp-content/uploads/2023/02/footerlogo-300x171.png',
                'website' => 'https://esayiditvet.co.za/',
                'prospectus_url' => 'https://www.esayiditvet-online.co.za/',
            ],
            'FALSEBAY' => [
                'logo' => 'https://falsebaycollege.co.za/wp-content/uploads/2022/01/Group-1647.svg',
                'website' => 'https://falsebaycollege.co.za/',
                'prospectus_url' => 'https://falsebaycollege.co.za/study-here/course-matrix/',
            ],
            'FLAVIUS' => [
                'logo' => 'https://www.matrichub.co.za/wp-content/uploads/2023/11/Flavius-Mareka-TVET-College.png',
                'website' => 'https://flaviusmareka.net/',
                'prospectus_url' => 'https://flaviusmareka.net/wp-content/uploads/2024/06/Flavius-Mareka-TVET-College-A5-Prospectus-2024.pdf',
            ],
            'GSC' => [
                'logo' => 'https://www.careersportal.co.za/sites/default/files/styles/max_2600x2600/public/gsc_logo.jpg?itok=z8UtlkhE',
                'website' => 'https://gscollege.edu.za/',
                'prospectus_url' => 'https://gscollege.edu.za/',
            ],
            'GOLDFIELDS' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=goldfieldstvet.edu.za&sz=256',
                'website' => 'https://goldfieldstvet.edu.za/',
                'prospectus_url' => 'https://goldfieldstvet.edu.za/study/apply/frequently-asked-questions/',
            ],
            'IKHALA' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=ikhala.edu.za&sz=256',
                'website' => 'https://ikhala.edu.za/',
                'prospectus_url' => 'https://ikhala.edu.za/course-category/national-certificate-courses/',
            ],
            'INGWE' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=ingwecollege.edu.za&sz=256',
                'website' => 'https://ingwecollege.edu.za/',
                'prospectus_url' => 'https://ingwecollege.edu.za/media/content/documents/2021/9/o_1fi1fgu20cnp1151hpd1ijo187md.pdf?filename=INGWE+TVET+COLLEGE+PROSPECTUS.pdf',
            ],
            'KHC' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=khc.edu.za&sz=256',
                'website' => 'https://www.khc.edu.za/',
                'prospectus_url' => 'https://www.khc.edu.za/wp-content/uploads/2025/04/King-Hintsa-FVET-College-Prospectus-2025-26-BMP.pdf',
            ],
            'KSD' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=ksdcollege.edu.za&sz=256',
                'website' => 'https://www.ksdcollege.edu.za/',
                'prospectus_url' => 'https://www.ksdcollege.edu.za/node/27',
            ],
            'LEPHALALE' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=leptvetcol.edu.za&sz=256',
                'website' => 'https://www.leptvetcol.edu.za/',
                'prospectus_url' => 'https://lephalale.coltech.co.za/Student/Brochure',
            ],
            'LETABA' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=letcol.co.za&sz=256',
                'website' => 'https://www.letcol.co.za/',
                'prospectus_url' => 'https://www.letcol.co.za/COLLEGE_COURSE_INFO?course=1',
            ],
            'LOVEDALE' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=lovedale.edu.za&sz=256',
                'website' => 'https://www.lovedale.edu.za/',
                'prospectus_url' => 'https://www.lovedale.edu.za/ncv-office-administration.php',
            ],
            'MAJUBA' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=majuba.edu.za&sz=256',
                'website' => 'https://www.majuba.edu.za/',
                'prospectus_url' => 'https://www.majuba.edu.za/wp-content/uploads/2023/06/Prospectus-Pre-pressed-Version.pdf',
            ],
            'MALUTI' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=malutitvet.co.za&sz=256',
                'website' => 'https://www.malutitvet.co.za/',
                'prospectus_url' => 'https://www.malutitvet.co.za/registration/',
            ],
            'MNAMBITHI' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=mnambithicollege.co.za&sz=256',
                'website' => 'https://www.mnambithicollege.co.za/',
                'prospectus_url' => 'https://www.mnambithicollege.co.za/index.php/media-mnambithi-college/prospectus?layout=table',
            ],
            'MOPANI' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=mopanicollege.edu.za&sz=256',
                'website' => 'https://mopanicollege.edu.za/',
                'prospectus_url' => 'https://mopanicollege.edu.za/',
            ],
            'MOTHEO' => [
                'logo' => 'https://era.thestudenthub.co.za/logo/1668083406.jpg',
                'website' => 'https://www.motheotvet.co.za/',
                'prospectus_url' => 'https://www.motheotvet.co.za/',
            ],
            'MTHASHANA' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=mthashanacollege.co.za&sz=256',
                'website' => 'https://www.mthashanacollege.co.za/',
                'prospectus_url' => 'https://www.mthashanacollege.co.za/prospectus-2026/',
            ],
            'NKANGALA' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=ntc.edu.za&sz=256',
                'website' => 'https://www.ntc.edu.za/',
                'prospectus_url' => 'https://www.ntc.edu.za/ncv-engineering-studies.html',
            ],
            'NCR' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=ncrtvet.com&sz=256',
                'website' => 'https://ncrtvet.com/',
                'prospectus_url' => 'https://ncrtvet.com/',
            ],
            'NCU' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=ncutvet.edu.za&sz=256',
                'website' => 'https://www.ncutvet.edu.za/',
                'prospectus_url' => 'https://www.ncutvet.edu.za/admission-requirements/',
            ],
            'NORTHLINK' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=northlink.co.za&sz=256',
                'website' => 'https://www.northlink.co.za/',
                'prospectus_url' => 'https://www.northlink.co.za/wp-content/uploads/2026/04/PDF-Jan-2026-PROGRAMME-OFFERINGS.pdf',
            ],
            'ORBIT' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=orbitcollege.co.za&sz=256',
                'website' => 'https://www.orbitcollege.co.za/',
                'prospectus_url' => 'https://www.orbitcollege.co.za/PROSPECTUS.pdf',
            ],
            'PEC' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=pecollege.edu.za&sz=256',
                'website' => 'https://www.pecollege.edu.za/',
                'prospectus_url' => 'https://www.pecollege.edu.za/admission-requirements/',
            ],
            'SEDCOL' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=sedcol.co.za&sz=256',
                'website' => 'https://www.sedcol.co.za/',
                'prospectus_url' => 'https://www.sedcol.co.za/programmes.aspx',
            ],
            'SEKHUKHUNE' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=sekhukhunetvet.edu.za&sz=256',
                'website' => 'https://www.sekhukhunetvet.edu.za/',
                'prospectus_url' => 'https://www.sekhukhunetvet.edu.za/home/about-us/prospectus.html',
            ],
            'SWGC' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=swgc.co.za&sz=256',
                'website' => 'https://www.swgc.co.za/',
                'prospectus_url' => 'https://www.swgc.co.za/study-with-us/online-applications-process/',
            ],
            'TALETSO' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=taletso.edu.za&sz=256',
                'website' => 'https://taletso.edu.za/',
                'prospectus_url' => 'https://taletso.edu.za/mafikengcourses/',
            ],
            'THEKWINI' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=thekwini.edu.za&sz=256',
                'website' => 'https://www.thekwini.edu.za/',
                'prospectus_url' => 'https://www.thekwini.edu.za/campuses/',
            ],
            'UMFOLOZI' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=umfolozicollege.co.za&sz=256',
                'website' => 'https://umfolozicollege.co.za/',
                'prospectus_url' => 'https://umfolozicollege.co.za/application/',
            ],
            'UMGUNGUNDLOVU' => [
                'logo' => 'https://media.licdn.com/dms/image/v2/D4E05AQEcKa_bkeQorg/feedshare-thumbnail_720_1280/feedshare-thumbnail_720_1280/0/1701273282848?e=2147483647&t=jPDC1ODmMjIevw03yRxgFU7WGS3Lbl_48r0pum0DlHE&v=beta',
                'website' => 'https://umgungundlovu.coltech.co.za/',
                'prospectus_url' => 'https://umgungundlovu.coltech.co.za/',
            ],
            'VHEMBE' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=vhembecollege.edu.za&sz=256',
                'website' => 'https://www.vhembecollege.edu.za/',
                'prospectus_url' => 'https://www.vhembecollege.edu.za/academic/courses/',
            ],
            'VUSELELA' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=vuselelacollege.co.za&sz=256',
                'website' => 'https://vuselelacollege.co.za/',
                'prospectus_url' => 'https://vuselelacollege.co.za/education-development-l2-l4/',
            ],
            'WATERBERG' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=waterbergcollege.co.za&sz=256',
                'website' => 'https://waterbergcollege.co.za/',
                'prospectus_url' => 'https://waterbergcollege.co.za/faq.php/courses.php',
            ],
            'WESTCOAST' => [
                'logo' => 'https://www.google.com/s2/favicons?domain=westcoastcollege.co.za&sz=256',
                'website' => 'https://www.westcoastcollege.co.za/',
                'prospectus_url' => 'https://www.westcoastcollege.co.za/programmes/',
            ],
        ];
    }
}
