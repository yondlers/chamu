<?php

use App\Http\Controllers\AssetInformationController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ListingPictureController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CheckInOutController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeaseController;
use App\Http\Controllers\LeaseTemplateController;
use App\Http\Controllers\LeaseVersionController;
use App\Http\Controllers\MaintenanceLogController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\SignatureAuthorizationController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::get('/place/{listing_id}/{search}', [SearchController::class, 'place'])->name('place');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

//    createView createView
    Route::get('/asset_informations/v/{property_id}/{unit_id}', [AssetInformationController::class, 'createView'])->name('asset_informations.createView');

    Route::resource('/listings', ListingController::class);
    Route::get('/listings/{unit_id}', [ListingController::class, 'add'])->name('listings.add');
    Route::get('/listings/asset_info/{listing}', [ListingController::class, 'assetInfo'])->name('listings.asset_info');

    Route::get('/upload/{listing_id}', [ListingPictureController::class, 'upload'])->name('listing_pictures.upload');
//    Route::get('/upload/{listing_id}', [ListingController::class, 'upload'])->name('listing_pictures.upload');

    Route::resource('/deposits', DepositController::class);
    Route::get('/deposits/unit/{unit_id}', [DepositController::class, 'add'])->name('deposits.add');

    Route::get('/tenants/user', [TenantController::class, 'user'])->name('tenants.user');

    Route::post('/listing/asset/{listing}/{createNotUpdate}', [ListingController::class, 'assetInfoPost'])->name('listings.asset_post');
    Route::post('/uploadPost/{listing_id}', [ListingPictureController::class, 'uploadPost'])->name('listing_pictures.uploadPost');

//    search.location


    Route::resource('/asset_informations', AssetInformationController::class);
    Route::resource('/properties', PropertyController::class);
    Route::resource('/comments',  CommentController::class);
    Route::resource('/units', UnitController::class);
    Route::resource('/tenants', TenantController::class);
    Route::resource('/lease_templates', LeaseTemplateController::class);
    Route::resource('/signature_authorizations', SignatureAuthorizationController::class);
    Route::resource('/leases', LeaseController::class);
    Route::resource('/lease_versions', LeaseVersionController::class);
    Route::resource('/invoices', InvoiceController::class);
    Route::resource('/maintenance_logs', MaintenanceLogController::class);
    Route::resource('/communications', CommunicationController::class);
    Route::resource('/listing_pictures', ListingPictureController::class);

    Route::resource('/check_in_outs', CheckInOutController::class);

    Route::get('/lease_templates/download/{lease_template}', [LeaseTemplateController::class, 'download'])->name('lease_templates.download');
    Route::get('/lease/download/{lease}', [LeaseController::class, 'download'])->name('leases.download');
    Route::get('/applicants', [TenantController::class, 'applicant'])->name('applicant.approval');
    Route::get('/application/approval/{tenant}', [TenantController::class, 'approveApplication'])->name('application.approval');

});

require __DIR__.'/auth.php';
