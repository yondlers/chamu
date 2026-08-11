<?php

namespace Database\Seeders;

use Database\Seeders\Universities\BOLAND\RequirementSeeder as BolandRequirementSeeder;
use Database\Seeders\Universities\BCC\RequirementSeeder as BccRequirementSeeder;
use Database\Seeders\Universities\CJC\RequirementSeeder as CjcRequirementSeeder;
use Database\Seeders\Universities\CPUT\RequirementSeeder as CputRequirementSeeder;
use Database\Seeders\Universities\CUT\RequirementSeeder as CutRequirementSeeder;
use Database\Seeders\Universities\DUT\RequirementSeeder as DutRequirementSeeder;
use Database\Seeders\Universities\EDUVOS\RequirementSeeder as EduvosRequirementSeeder;
use Database\Seeders\Universities\EEC\RequirementSeeder as EecRequirementSeeder;
use Database\Seeders\Universities\EHLANZENI\RequirementSeeder as EhlanzeniRequirementSeeder;
use Database\Seeders\Universities\EWC\RequirementSeeder as EwcRequirementSeeder;
use Database\Seeders\Universities\NMU\RequirementSeeder as NmuRequirementSeeder;
use Database\Seeders\Universities\NWU\RequirementSeeder as NwuRequirementSeeder;
use Database\Seeders\Universities\RU\RequirementSeeder as RuRequirementSeeder;
use Database\Seeders\Universities\SCC\RequirementSeeder as SccRequirementSeeder;
use Database\Seeders\Universities\SMU\RequirementSeeder as SmuRequirementSeeder;
use Database\Seeders\Universities\SPU\RequirementSeeder as SpuRequirementSeeder;
use Database\Seeders\Universities\SU\RequirementSeeder as SuRequirementSeeder;
use Database\Seeders\Universities\TNC\RequirementSeeder as TncRequirementSeeder;
use Database\Seeders\Universities\TSC\RequirementSeeder as TscRequirementSeeder;
use Database\Seeders\Universities\TUT\RequirementSeeder as TutRequirementSeeder;
use Database\Seeders\Universities\UCT\RequirementSeeder as UctRequirementSeeder;
use Database\Seeders\Universities\UFH\RequirementSeeder as UfhRequirementSeeder;
use Database\Seeders\Universities\UFS\RequirementSeeder as UfsRequirementSeeder;
use Database\Seeders\Universities\UJ\RequirementSeeder as UjRequirementSeeder;
use Database\Seeders\Universities\UKZN\RequirementSeeder as UkznRequirementSeeder;
use Database\Seeders\Universities\UL\RequirementSeeder as UlRequirementSeeder;
use Database\Seeders\Universities\UMP\RequirementSeeder as UmpRequirementSeeder;
use Database\Seeders\Universities\UNISA\RequirementSeeder as UnisaRequirementSeeder;
use Database\Seeders\Universities\UNIVEN\RequirementSeeder as UnivenRequirementSeeder;
use Database\Seeders\Universities\UNIZULU\RequirementSeeder as UnizuluRequirementSeeder;
use Database\Seeders\Universities\UP\RequirementSeeder as UpRequirementSeeder;
use Database\Seeders\Universities\UWC\RequirementSeeder as UwcRequirementSeeder;
use Database\Seeders\Universities\VC\RequirementSeeder as VcRequirementSeeder;
use Database\Seeders\Universities\VUT\RequirementSeeder as VutRequirementSeeder;
use Database\Seeders\Universities\WESTCOL\RequirementSeeder as WestcolRequirementSeeder;
use Database\Seeders\Universities\WITS\RequirementSeeder as WitsRequirementSeeder;
use Database\Seeders\Universities\WSU\RequirementSeeder as WsuRequirementSeeder;
use Illuminate\Database\Seeder;

class UniversitySeeder extends Seeder
{
    /**
     * Seed all university requirement catalogues and logos.
     */
    public function run(): void
    {
        $this->call([
            TvetCollegeDirectorySeeder::class,
            UpRequirementSeeder::class,
            BolandRequirementSeeder::class,
            BccRequirementSeeder::class,
            CjcRequirementSeeder::class,
            EecRequirementSeeder::class,
            EhlanzeniRequirementSeeder::class,
            EwcRequirementSeeder::class,
            TncRequirementSeeder::class,
            TscRequirementSeeder::class,
            WestcolRequirementSeeder::class,
            SccRequirementSeeder::class,
            TutRequirementSeeder::class,
            UfsRequirementSeeder::class,
            NmuRequirementSeeder::class,
            NwuRequirementSeeder::class,
            UkznRequirementSeeder::class,
            UlRequirementSeeder::class,
            UnisaRequirementSeeder::class,
            UnizuluRequirementSeeder::class,
            UnivenRequirementSeeder::class,
            UmpRequirementSeeder::class,
            UjRequirementSeeder::class,
            CputRequirementSeeder::class,
            CutRequirementSeeder::class,
            DutRequirementSeeder::class,
            UwcRequirementSeeder::class,
            VcRequirementSeeder::class,
            EduvosRequirementSeeder::class,
            VutRequirementSeeder::class,
            WsuRequirementSeeder::class,
            RuRequirementSeeder::class,
            SmuRequirementSeeder::class,
            SpuRequirementSeeder::class,
            SuRequirementSeeder::class,
            WitsRequirementSeeder::class,
            UctRequirementSeeder::class,
            UfhRequirementSeeder::class,
            UniversityLogoSeeder::class,
            UniversityContactSeeder::class,
        ]);
    }
}
