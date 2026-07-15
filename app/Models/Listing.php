<?php

namespace App\Models;

use App\Helpers\HtmlForm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use function PHPUnit\Framework\isEmpty;

class Listing extends Model
{
    //
    use HasFactory;

    protected $table = 'listings';

    protected $fillable = [
        'active',
        'team_id',
        'unit_id',
        'title',
        'description',
    ];

    public function getNameAttribute()
    {
        return $this->unit->assetInformation->number_of_bedroom . ' ' . $this->unit->unitType->name . ',  ' . $this->unit->property->address;
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'listing_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function listingPictures()
    {
        return $this->hasMany(ListingPicture::class, 'listing_id');
    }

    public static function search1($suburb_ids)
    {
        return Listing::where('active', 1)
            ->whereHas('unit.property', function ($query) use ($suburb_ids) {
                $query->whereIn('suburb_id', $suburb_ids);
            })
            ->get();
    }


    public static function htmlPictures()
    {

    }

    public static function htmlForm(Listing $listing)
    {

        $units = Unit::where("team_id", auth()->user()->current_team_id)->get();
        $unit_filter = [];
        foreach ($units as $unit) {
            $listing = Listing::where("unit_id", $unit->id)->first();
            if ($listing) {
                continue;
            }
            array_push($unit_filter, $unit);
        }

            return '
            <!-- unit_id -->
            ' . HtmlForm::generateSelect("unit_id", $unit_filter, $listing->unit_id, true) . '
            <!-- title -->
            ' . HtmlForm::generateInput("title", "text", $listing->title, true) . '
            <!-- description -->
            ' . HtmlForm::generateInput("description", "text", $listing->description, true) . '

            ' . HtmlForm::submitButtonInput() . '
        ';
    }

}
