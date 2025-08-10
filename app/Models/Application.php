<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    //
    use HasFactory;

    protected $table = 'applications';

    protected $fillable = [
        'active',
        'tenant_id',
        'listing_id',
        'status',
        'status_reason',
        'notes',
        'move_in_date'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }
}
