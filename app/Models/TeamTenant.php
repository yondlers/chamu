<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamTenant extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'active',
        'team_id',
        'tenant_id'
    ];



    /**
     * Get the Team who this record belongs to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Get the Tenant who this record belongs to.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
