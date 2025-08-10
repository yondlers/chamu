<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

class Team extends JetstreamTeam
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_team',
        'user_id',

        'office_name',
        'address_line_1',
        'address_line_2',
        'suburb_id',
        'city_id',
        'province_id',
        'country_id',
    ];

    public static function getTeamList()
    {
        $team = [];
        $team_owner = Team::where('id', auth()->user()->current_team_id)->first();
        $team_members = TeamUser::where('team_id', auth()->user()->current_team_id)->get();

        if($team_members->isNotEmpty()) {
            foreach($team_members as $team_member) {
                $obj = ['id' => $team_member->user->id, 'name' => $team_member->user->name];
                array_push($team, $obj);
            }

        }

        $obj = ['id' => $team_owner->user->id, 'name' => $team_owner->user->name];
        array_push($team, $obj);

        return $team;
    }

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }

    /**
     * Get the Foregin Key
     */
    //Team Owner
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the Address for this Team.
     */

    public function suburb()
    {
        return $this->belongsTo(Suburb::class, 'suburb_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
