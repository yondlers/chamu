<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CPUT_LOGO = 'images/universities/cput.png';

    private const STALE_CPUT_LOGO = 'https://www.cput.ac.za/images/About/Brand%20ID/img_branding_logo_correct.jpg';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('universities')
            ->where('abbreviation', 'CPUT')
            ->where(function ($query): void {
                $query
                    ->whereNull('logo')
                    ->orWhere('logo', '')
                    ->orWhere('logo', self::STALE_CPUT_LOGO)
                    ->orWhere('logo', self::CPUT_LOGO);
            })
            ->update([
                'logo' => self::CPUT_LOGO,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
