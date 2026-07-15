<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\AuditLog;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'name',
        'description',
        'ip_address',
        'user_agent',
        'url',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id','id') ;
    }

    public function team() {
        return $this->belongsTo(Team::class, 'team_id','id') ;
    }

    public static function log($name, $description)
    {
        $user = auth()->user();

        AuditLog::create([
            'user_id'     => $user->id,
            'team_id'     => $user->current_team_id,
            'name'        => $name,
            'description' => $description,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'url'         => request()->fullUrl(),
        ]);
    }
}
