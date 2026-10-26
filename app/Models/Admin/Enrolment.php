<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrolment extends Model
{
    use HasFactory;


    protected $fillable = [
        'student_id',
        'section_offering_id',
        'sub_section_id',
        'wishlist_for_enrolment_id',
        'enrolment_date',
        'is_wishlist',
        'wishlist_status',
        'requested_by_guardian_id',
        'reviewed_by_user_id',
        'reviewed_at',
        'wishlist_review_note',
        'is_active',
    ];


    protected $casts = [
        'student_id' =>
            'integer',

        'section_offering_id' =>
            'integer',

        'wishlist_for_enrolment_id' =>
            'integer',

        'requested_by_guardian_id' =>
            'integer',

        'reviewed_by_user_id' =>
            'integer',

        'enrolment_date' =>
            'date',

        'reviewed_at' =>
            'datetime',

        'is_wishlist' =>
            'boolean',

        'is_active' =>
            'boolean',
    ];


    /*
    |--------------------------------------------------------------------------
    | Student
    |--------------------------------------------------------------------------
    */

    public function student()
    {
        return $this->belongsTo(
            Student::class,
            'student_id',
            'id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Section Offering
    |--------------------------------------------------------------------------
    */

    public function sectionOffering()
    {
        return $this->belongsTo(
            SectionOffering::class,
            'section_offering_id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Attendance
    |--------------------------------------------------------------------------
    */

    public function attendances()
    {
        return $this->hasMany(
            Attendance::class,
            'enrolment_id',
            'id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Original Enrolment For Wishlist
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | Student currently attends:
    | English - Tuesday 4:30 PM
    |
    | Parent requests:
    | English - Thursday 4:30 PM
    |
    | The wishlist row points back to the
    | original confirmed enrolment using
    | wishlist_for_enrolment_id.
    |
    */

    public function wishlistForEnrolment()
    {
        return $this->belongsTo(
            Enrolment::class,
            'wishlist_for_enrolment_id',
            'id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Wishlist Requests For This Enrolment
    |--------------------------------------------------------------------------
    */

    public function wishlistRequests()
    {
        return $this->hasMany(
            Enrolment::class,
            'wishlist_for_enrolment_id',
            'id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Guardian Who Requested Wishlist
    |--------------------------------------------------------------------------
    */

    public function requestedByGuardian()
    {
        return $this->belongsTo(
            Guardian::class,
            'requested_by_guardian_id',
            'id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Staff Member Who Reviewed Wishlist
    |--------------------------------------------------------------------------
    */

    public function reviewedBy()
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by_user_id',
            'id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Active Scope
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where(
            'is_active',
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Confirmed Enrolment Scope
    |--------------------------------------------------------------------------
    |
    | Normal active student class.
    |
    */

    public function scopeConfirmed($query)
    {
        return $query
            ->where(
                'is_active',
                true
            )
            ->where(
                'is_wishlist',
                false
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Active Wishlist Scope
    |--------------------------------------------------------------------------
    |
    | Mainly pending requests.
    |
    */

    public function scopeWishlist($query)
    {
        return $query
            ->where(
                'is_active',
                true
            )
            ->where(
                'is_wishlist',
                true
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Pending Wishlist
    |--------------------------------------------------------------------------
    */

    public function scopePendingWishlist(
        $query
    ) {
        return $query
            ->where(
                'is_wishlist',
                true
            )
            ->where(
                'wishlist_status',
                'pending'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Approved Wishlist
    |--------------------------------------------------------------------------
    */

    public function scopeApprovedWishlist(
        $query
    ) {
        return $query
            ->where(
                'wishlist_status',
                'approved'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Rejected Wishlist
    |--------------------------------------------------------------------------
    */

    public function scopeRejectedWishlist(
        $query
    ) {
        return $query
            ->where(
                'wishlist_status',
                'rejected'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Cancelled Wishlist
    |--------------------------------------------------------------------------
    */

    public function scopeCancelledWishlist(
        $query
    ) {
        return $query
            ->where(
                'wishlist_status',
                'cancelled'
            );
    }

    public function subSection()
    {
        return $this->belongsTo(
            SubSection::class,
            'sub_section_id',
            'id'
        );
    }
}
