<?php

namespace App\Models;

use App\Helpers\HtmlForm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nette\Utils\Html;

class LeaseTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'active',

        'template_html',

        'file_prefix',
        'lease_template_file',

        'team_id',
    ];

    /**
     * Get the Team who this lease template belongs to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public static function htmlShow(LeaseTemplate $template) {
        return
        '
            <!-- Name -->
            Name
            ' . HtmlForm::generateDisclaimer($template->name, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Slug -->
            Slug
            ' . HtmlForm::generateDisclaimer($template->slug, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
        ';
    }


    public static function htmlForm(LeaseTemplate $template) {
        return
        '
            <!-- Name -->
            ' . HtmlForm::generateInput("name", "text", $template->name, true) . '
            <!-- Slug -->
            ' . HtmlForm::generateInput("slug", "text", $template->slug, true) . '
            <!-- Template -->
            ' . HtmlForm::templateInput($template->template_html ?? '') . '

            <!-- Tags -->
            ' . HtmlForm::tagsHtml() . '
            <!-- Submit -->
            ' . HtmlForm::submitButtonInput() . '
        ';

//        <!-- Lease Template File -->
//        ' . HtmlForm::generateInput("lease_template_file", "file", null, false) . '
    }

}
