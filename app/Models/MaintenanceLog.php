<?php

namespace App\Models;

use App\Helpers\HtmlForm;
use App\Helpers\LookUp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',

        'url_code',
        'lease_id',
        'maintenance_type_id',

        'name',
        'description',

        'material_cost',
        'labour_cost',
        'total_cost',

        'before_image',
        'after_image',

        'performed_by',
        'performed_at',

        'team_id',
    ];

    /**
     * Relationship with MaintenanceType
     */
    public function maintenanceType()
    {
        return $this->belongsTo(MaintenanceType::class);
    }

    /**
     * Relationship with Property (or Unit)
     * Adjust the relationship name and model if it's `Unit` or `Property`.
     */
    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public static function htmlForm(MaintenanceLog $log)
    {
        return '
            <!-- Name -->
            ' . HtmlForm::generateInput('name', 'text', $log->name, true) . '
            <!-- Lease -->
            ' . HtmlForm::generateSelect('lease_id', Lease::where('team_id', auth()->user()->current_team_id)->get(), $log->lease_id, true) . '
            <!-- Maintenance Type -->
            ' . HtmlForm::generateSelect('maintenance_type_id', LookUp::MAINTENANCE_TYPES, $log->maintenance_type_id, true) . '
            <!-- Description -->
            ' . HtmlForm::generateInput('description', 'text', $log->description, false) . '
            <!-- Material Cost -->
            ' . HtmlForm::generateInput('material_cost', 'decimal', $log->material_cost, false) . '
            <!-- Labour Cost -->
            ' . HtmlForm::generateInput('labour_cost', 'decimal', $log->labour_cost, false) . '

            <!-- Images Before -->
            ' . HtmlForm::generateInput('before_image', 'file', $log->before_image, false) . '
            <!-- Images After -->
            ' . HtmlForm::generateInput('after_image', 'file', $log->after_image, false) . '

            <!-- Performed By -->
            ' . HtmlForm::generateInput('performed_by', 'text', $log->performed_by, false) . '
            <!-- Performed At -->
            ' . HtmlForm::generateInput('performed_at', 'date', $log->performed_at, false) . '

            <!-- Submit -->
            ' . HtmlForm::submitButtonInput() . '
        ';
    }
}
