<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListingPicture extends Model
{
    //
    use HasFactory;

    protected $table = 'listing_pictures';

    protected $fillable = [
        'listing_id',
        'team_id',
        'image_1',
        'image_2',
        'image_3',
        'image_4',
        'image_5',
        'image_6',
        'image_7',
        'image_8',
        'image_9',
        'image_10'
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

//    public function html
}
