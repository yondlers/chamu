<?php

namespace App\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamUser extends Model
{
    use HasFactory;

    protected $table = 'team_user';

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
    ];


    public static function getTeamUserList()
    {
        $user = auth()->user();

        $team_users = TeamUser::where('team_id', $user->currentTeam->id)->get();
        $team_lead = Team::where('id', $user->currentTeam->id)->first();

        $user_list = [];

        foreach ($team_users as $team_user) {
            $user_list[] = $team_user->user_id;
        }

        $user_list[] = $team_lead?->user;

        return $user_list;

    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * Relationships
     */

    public function team() {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

}
