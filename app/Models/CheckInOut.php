<?php

namespace App\Models;

use App\Helpers\Constants;
use App\Helpers\HtmlForm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInOut extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'initial_checkin_list',
        'final_checkin_list',
        'initial_check_in',
        'final_check_in',
        'initial_check_out',
        'final_check_out',
        'requires_checkout',
        'lease_id',
        'team_id',
    ];


    /**
     * Convert initial and final check-in lists from string to JSON.
     *
     * @return array
     */
    public function getCheckinListsAsJson(): array
    {
        return [
            'initial_checkin_list' => json_decode($this->initial_checkin_list, true),
            'final_checkin_list'   => json_decode($this->final_checkin_list, true),
        ];
    }

    /**
     * Generate checklist JSON from comma-separated string input.
     *
     * @param string $checklist
     * @return string
     */
    public static function generateChecklistJson(string $checklist): string
    {
        $items = explode(',', $checklist);
        $jsonArray = [];

        foreach ($items as $item) {
            $trimmedItem = trim($item);
            if (!empty($trimmedItem)) {
                $jsonArray[$trimmedItem] = true;
            }
        }

        return json_encode($jsonArray);
    }

    /**
     * Save the checklist to the database as a string.
     *
     * @param string $checklist
     * @param string $field
     * @return void
     */
    public function saveChecklist(string $checklist, string $field): void
    {
        $this->$field = self::generateChecklistJson($checklist);
        $this->save();
    }

    /**
     * Relationships
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function lease()
    {
        return $this->belongsTo(Lease::class, 'lease_id');
    }

    

    public static function htmlForm(CheckInOut $check_in_out)
    {
        return '
            <!-- Name -->
            ' . HtmlForm::generateInput('name', 'text', $check_in_out->name, true) . '

            <!-- Disclaimer -->
            ' . HtmlForm::generateDisclaimer('List your CHeck in and out list (i.e: Light,Door Handle,Keys)', '') . '

            <!-- Initial Check In Lists -->
            ' . HtmlForm::generateInput('initial_checkin_list', 'text', $check_in_out->initial_checkin_list, true) . '

            <!-- Requires Check Out -->
            ' . HtmlForm::generateSelect('requires_checkout', Constants::YES_NO, $check_in_out->requires_checkout, true) . '
            <!-- Lease Id -->
            ' . HtmlForm::generateSelect('lease_id', Lease::where('team_id', auth()->user()->current_team_id)->get(), $check_in_out->lease_id, true) . '
            <!-- Submit -->
            ' . HtmlForm::submitButtonInput() . '
        ';
    }
}
