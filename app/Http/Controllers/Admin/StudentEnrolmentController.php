<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Enrolment;
use App\Models\Admin\SectionOffering;
use App\Models\Admin\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentEnrolmentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Add Class Page
    |--------------------------------------------------------------------------
    */

    public function create(
        Student $student
    ) {
        $student->load([
            'studentStatus',
            'enrolments',
        ]);


        /*
         * Existing active offering IDs.
         */
        $existingOfferingIds =
            $student
                ->enrolments
                ->where(
                    'is_active',
                    true
                )
                ->pluck(
                    'section_offering_id'
                )
                ->toArray();


        /*
        |--------------------------------------------------------------------------
        | Active Offerings
        |--------------------------------------------------------------------------
        */

        $offerings =
            SectionOffering::with([
                'section',
                'day',
                'subSections',
            ])
                ->withCount([
                    'enrolments as allocated_seats' =>
                        function ($query) {

                            $query
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->where(
                                    'is_wishlist',
                                    false
                                );
                        },

                    'enrolments as wishlist_count' =>
                        function ($query) {

                            $query
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->where(
                                    'is_wishlist',
                                    true
                                );
                        },
                ])
                ->where(
                    'is_active',
                    true
                )
                ->whereNotIn(
                    'id',
                    $existingOfferingIds
                )
                ->orderBy(
                    'day_id'
                )
                ->orderBy(
                    'start_time'
                )
                ->get();


        return view(
            'admin.student-enrolments.create',
            compact(
                'student',
                'offerings'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store New Class / Direct Wishlist
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        Student $student
    ) {
        $validated =
            $request->validate([
                'section_offering_ids' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'section_offering_ids.*' => [
                    'required',
                    'integer',
                    'distinct',

                    Rule::exists(
                        'section_offerings',
                        'id'
                    )->where(
                        function ($query) {

                            $query->where(
                                'is_active',
                                true
                            );
                        }
                    ),
                ],

                /*
                 * Key = section offering ID
                 * Value = selected sub-section ID
                 */
                'sub_section_ids' => [
                    'nullable',
                    'array',
                ],

                'sub_section_ids.*' => [
                    'nullable',
                    'integer',
                    'exists:sub_sections,id',
                ],

                'enrolment_type' => [
                    'required',

                    Rule::in([
                        'confirmed',
                        'wishlist',
                    ]),
                ],
            ]);


        $selectedOfferingIds =
            collect(
                $validated[
                    'section_offering_ids'
                ]
            )
                ->map(
                    fn ($id) =>
                        (int) $id
                )
                ->unique()
                ->values();


        $existingOfferingIds =
            Enrolment::where(
                'student_id',
                $student->id
            )
                ->whereIn(
                    'section_offering_id',
                    $selectedOfferingIds
                )
                ->where(
                    'is_active',
                    true
                )
                ->pluck(
                    'section_offering_id'
                );


        if (
            $existingOfferingIds
                ->isNotEmpty()
        ) {

            throw ValidationException::withMessages([
                'section_offering_ids' =>
                    'One or more selected classes are already assigned to this student.',
            ]);
        }


        $isWishlist =
            $validated[
                'enrolment_type'
            ]
            ===
            'wishlist';


        DB::transaction(
            function () use (
                $selectedOfferingIds,
                $student,
                $isWishlist,
                $validated
            ) {

                $offerings =
                    SectionOffering::with([
                        'section',
                        'day',
                        'subSections',
                    ])
                        ->whereIn(
                            'id',
                            $selectedOfferingIds
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->orderBy(
                            'id'
                        )
                        ->lockForUpdate()
                        ->get();


                if (
                    $offerings->count()
                    !==
                    $selectedOfferingIds->count()
                ) {

                    throw ValidationException::withMessages([
                        'section_offering_ids' =>
                            'One or more selected classes are no longer available. Please refresh and try again.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Validate Sub-sections
                |--------------------------------------------------------------------------
                */

                $selectedSubSections =
                    collect(
                        $validated[
                            'sub_section_ids'
                        ]
                        ??
                        []
                    );


                foreach (
                    $offerings
                    as $offering
                ) {

                    $subSectionId =
                        $selectedSubSections
                            ->get(
                                (string) $offering->id
                            )
                        ??
                        $selectedSubSections
                            ->get(
                                $offering->id
                            );


                    if (
                        $offering
                            ->subSections
                            ->isNotEmpty()
                    ) {

                        if (!$subSectionId) {

                            throw ValidationException::withMessages([
                                'sub_section_ids' =>
                                    'Please select a sub-section for '
                                    .
                                    (
                                        $offering
                                            ->section
                                            ?->section_name
                                        ??
                                        'the selected class'
                                    )
                                    .
                                    '.',
                            ]);
                        }


                        if (
                            !$offering
                                ->subSections
                                ->contains(
                                    'id',
                                    (int) $subSectionId
                                )
                        ) {

                            throw ValidationException::withMessages([
                                'sub_section_ids' =>
                                    'The selected sub-section is not available for one of the selected classes.',
                            ]);
                        }

                    } else {

                        $subSectionId =
                            null;
                    }


                    $offering->setAttribute(
                        'selected_sub_section_id',
                        $subSectionId
                            ? (int) $subSectionId
                            : null
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | One Confirmed Class Per Day and Sub-section
                |--------------------------------------------------------------------------
                |
                | A student may select the same main section on the same day when the
                | sub-sections differ (for example Interactive English and Interactive
                | Math). The same section/sub-section combination is allowed only once.
                |--------------------------------------------------------------------------
                */

                if (!$isWishlist) {

                    $classKey = function ($offering) {

                        return
                            (int) $offering->section_id
                            . ':' .
                            (int) $offering->day_id
                            . ':' .
                            (int) ($offering->getAttribute('selected_sub_section_id') ?? 0);
                    };

                    $classLabel = function ($offering) {

                        $label =
                            $offering->section?->section_name
                            ?? 'Selected class';

                        $subSectionId =
                            (int) ($offering->getAttribute('selected_sub_section_id') ?? 0);

                        $subSectionName = $subSectionId
                            ? $offering->subSections
                                ->firstWhere('id', $subSectionId)
                                ?->sub_section_name
                            : null;

                        return $subSectionName
                            ? $label . ' - ' . $subSectionName
                            : $label;
                    };

                    $duplicateGroup =
                        $offerings
                            ->groupBy($classKey)
                            ->first(fn ($group) => $group->count() > 1);

                    if ($duplicateGroup) {

                        $duplicateOffering = $duplicateGroup->first();
                        $dayName =
                            $duplicateOffering->day?->day_name
                            ?? 'the selected day';

                        throw ValidationException::withMessages([
                            'section_offering_ids' =>
                                $classLabel($duplicateOffering)
                                . ' can only be selected once on '
                                . $dayName
                                . '. Choose another day, time, or sub-section.',
                        ]);
                    }

                    $existingConfirmedEnrolments =
                        Enrolment::with([
                            'sectionOffering.section',
                            'sectionOffering.day',
                        ])
                            ->where('student_id', $student->id)
                            ->where('is_active', true)
                            ->where('is_wishlist', false)
                            ->get();

                    foreach ($offerings as $newOffering) {

                        $sameClassDaySubSection =
                            $existingConfirmedEnrolments
                                ->first(function ($existingEnrolment) use ($newOffering) {

                                    $existingOffering = $existingEnrolment->sectionOffering;

                                    return
                                        $existingOffering
                                        && (int) $existingOffering->section_id === (int) $newOffering->section_id
                                        && (int) $existingOffering->day_id === (int) $newOffering->day_id
                                        && (int) ($existingEnrolment->sub_section_id ?? 0)
                                            === (int) ($newOffering->getAttribute('selected_sub_section_id') ?? 0);
                                });

                        if ($sameClassDaySubSection) {

                            $dayName =
                                $newOffering->day?->day_name
                                ?? 'the selected day';

                            throw ValidationException::withMessages([
                                'section_offering_ids' =>
                                    'This student already has '
                                    . $classLabel($newOffering)
                                    . ' on '
                                    . $dayName
                                    . '. Choose another day, time, or sub-section.',
                            ]);
                        }
                    }

                    $allConfirmedOfferings =
                        $existingConfirmedEnrolments
                            ->map(fn ($enrolment) => $enrolment->sectionOffering)
                            ->filter();

                    foreach ($offerings as $index => $newOffering) {

                        $otherOfferings =
                            $allConfirmedOfferings
                                ->concat($offerings->slice($index + 1));

                        $newStart = \Carbon\Carbon::parse($newOffering->start_time);
                        $newEnd = \Carbon\Carbon::parse($newOffering->end_time);

                        $conflict = $otherOfferings->first(function ($otherOffering) use (
                            $newOffering,
                            $newStart,
                            $newEnd
                        ) {
                            if ((int) $otherOffering->day_id !== (int) $newOffering->day_id) {
                                return false;
                            }

                            $newIsInteractive =
                                strtolower(
                                    trim(
                                        $newOffering
                                            ->section
                                            ?->section_name
                                        ?? ''
                                    )
                                ) === 'interactive';

                            $otherIsInteractive =
                                strtolower(
                                    trim(
                                        $otherOffering
                                            ->section
                                            ?->section_name
                                        ?? ''
                                    )
                                ) === 'interactive';

                            /* Math and English may overlap. */
                            if (!$newIsInteractive && !$otherIsInteractive) {
                                return false;
                            }

                            $otherStart = \Carbon\Carbon::parse($otherOffering->start_time);
                            $otherEnd = \Carbon\Carbon::parse($otherOffering->end_time);

                            return $newStart->lt($otherEnd) && $otherStart->lt($newEnd);
                        });

                        if ($conflict) {

                            throw ValidationException::withMessages([
                                'section_offering_ids' =>
                                    'Schedule conflict on '
                                    . ($newOffering->day?->day_name ?? 'the selected day')
                                    . ': '
                                    . ($newOffering->section?->section_name ?? 'Selected class')
                                    . ' (' . $newStart->format('g:i A') . ' - ' . $newEnd->format('g:i A') . ') overlaps with '
                                    . ($conflict->section?->section_name ?? 'another class')
                                    . ' (' . \Carbon\Carbon::parse($conflict->start_time)->format('g:i A')
                                    . ' - ' . \Carbon\Carbon::parse($conflict->end_time)->format('g:i A') . ').',
                            ]);
                        }
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Capacity
                |--------------------------------------------------------------------------
                */

                if (!$isWishlist) {

                    foreach (
                        $offerings
                        as $offering
                    ) {

                        $allocatedSeats =
                            Enrolment::where(
                                'section_offering_id',
                                $offering->id
                            )
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->where(
                                    'is_wishlist',
                                    false
                                )
                                ->count();


                        if (
                            $allocatedSeats
                            >=
                            $offering->max_seats
                        ) {

                            $className =
                                $offering
                                    ->section
                                    ?->section_name
                                ??
                                'Selected class';


                            $dayName =
                                $offering
                                    ->day
                                    ?->day_name
                                ??
                                '';


                            throw ValidationException::withMessages([
                                'section_offering_ids' =>
                                    $className
                                    .
                                    (
                                        $dayName
                                            ? ' on ' . $dayName
                                            : ''
                                    )
                                    .
                                    ' is full. Choose another class or add the selections to the wishlist.',
                            ]);
                        }
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Create Enrolments
                |--------------------------------------------------------------------------
                */

                foreach (
                    $offerings
                    as $offering
                ) {

                    Enrolment::create([
                        'student_id' =>
                            $student->id,

                        'section_offering_id' =>
                            $offering->id,

                        'sub_section_id' =>
                            $offering
                                ->getAttribute(
                                    'selected_sub_section_id'
                                ),

                        'wishlist_for_enrolment_id' =>
                            null,

                        'enrolment_date' =>
                            now()->toDateString(),

                        'is_wishlist' =>
                            $isWishlist,

                        'wishlist_status' =>
                            $isWishlist
                                ? 'pending'
                                : null,

                        'is_active' =>
                            true,
                    ]);
                }
            }
        );


        $classCount =
            $selectedOfferingIds
                ->count();


        return redirect()
            ->route(
                'admin.students.show',
                $student
            )
            ->with(
                'success',
                $isWishlist
                    ? (
                        $classCount === 1
                            ? 'Student added to the wishlist successfully.'
                            : $classCount . ' classes added to the wishlist successfully.'
                    )
                    : (
                        $classCount === 1
                            ? 'Class added successfully.'
                            : $classCount . ' classes added successfully.'
                    )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Select Schedule To Edit
    |--------------------------------------------------------------------------
    */

    public function editList(
        Student $student
    ) {
        $student->load([
            'enrolments' =>
                function ($query) {

                    $query
                        ->where(
                            'is_active',
                            true
                        )
                        ->where(
                            'is_wishlist',
                            false
                        );
                },

            'enrolments.subSection',
            'enrolments.sectionOffering.section',
            'enrolments.sectionOffering.day',
        ]);


        $enrolments =
            $student
                ->enrolments;


        return view(
            'admin.student-enrolments.edit-list',
            compact(
                'student',
                'enrolments'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit Current Schedule
    |--------------------------------------------------------------------------
    */

    public function edit(
        Student $student,
        Enrolment $enrolment
    ) {
        /*
         * Must belong to student.
         */
        if (
            (int)
            $enrolment
                ->student_id
            !==
            (int)
            $student->id
        ) {

            abort(404);
        }


        /*
         * Only active confirmed classes.
         */
        if (
            !$enrolment
                ->is_active
            ||
            $enrolment
                ->is_wishlist
        ) {

            abort(404);
        }


        $student->load([
            'guardians',
        ]);


        $enrolment->load([
            'subSection',
            'sectionOffering.section',
            'sectionOffering.day',
            'sectionOffering.subSections',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Other Active Classes
        |--------------------------------------------------------------------------
        */

        $existingOfferingIds =
            Enrolment::where(
                'student_id',
                $student->id
            )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'id',
                    '!=',
                    $enrolment->id
                )
                ->pluck(
                    'section_offering_id'
                )
                ->toArray();


        /*
        |--------------------------------------------------------------------------
        | Active Offerings
        |--------------------------------------------------------------------------
        */

        $offerings =
            SectionOffering::with([
                'section',
                'day',
                'subSections',
            ])
                ->withCount([
                    'enrolments as allocated_seats' =>
                        function ($query) {

                            $query
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->where(
                                    'is_wishlist',
                                    false
                                );
                        },
                ])
                ->where(
                    'is_active',
                    true
                )
                ->whereNotIn(
                    'id',
                    $existingOfferingIds
                )
                ->orderBy(
                    'day_id'
                )
                ->orderBy(
                    'start_time'
                )
                ->get();


        return view(
            'admin.student-enrolments.edit',
            compact(
                'student',
                'enrolment',
                'offerings'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Current Schedule
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Student $student,
        Enrolment $enrolment
    ) {
        if (
            (int) $enrolment->student_id
            !==
            (int) $student->id
        ) {

            abort(404);
        }


        if (
            !$enrolment->is_active
            ||
            $enrolment->is_wishlist
        ) {

            abort(404);
        }


        $validated =
            $request->validate([
                'section_offering_id' => [
                    'required',
                    'integer',

                    Rule::exists(
                        'section_offerings',
                        'id'
                    )->where(
                        function ($query) {

                            $query->where(
                                'is_active',
                                true
                            );
                        }
                    ),
                ],

                'sub_section_id' => [
                    'nullable',
                    'integer',
                    'exists:sub_sections,id',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Offering
        |--------------------------------------------------------------------------
        */

        $duplicate =
            Enrolment::where(
                'student_id',
                $student->id
            )
                ->where(
                    'section_offering_id',
                    $validated[
                        'section_offering_id'
                    ]
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'id',
                    '!=',
                    $enrolment->id
                )
                ->exists();


        if ($duplicate) {

            throw ValidationException::withMessages([
                'section_offering_id' =>
                    'This student is already registered in the selected class.',
            ]);
        }


        DB::transaction(
            function () use (
                $validated,
                $enrolment,
                $student
            ) {

                $newOffering =
                    SectionOffering::with([
                        'section',
                        'subSections',
                    ])
                        ->where(
                            'id',
                            $validated[
                                'section_offering_id'
                            ]
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | Validate Sub-section
                |--------------------------------------------------------------------------
                */

                $subSectionId =
                    $validated[
                        'sub_section_id'
                    ]
                    ??
                    null;


                if (
                    $newOffering
                        ->subSections
                        ->isNotEmpty()
                ) {

                    if (!$subSectionId) {

                        throw ValidationException::withMessages([
                            'sub_section_id' =>
                                'Please select a sub-section for '
                                .
                                (
                                    $newOffering
                                        ->section
                                        ?->section_name
                                    ??
                                    'the selected class'
                                )
                                .
                                '.',
                        ]);
                    }


                    if (
                        !$newOffering
                            ->subSections
                            ->contains(
                                'id',
                                (int) $subSectionId
                            )
                    ) {

                        throw ValidationException::withMessages([
                            'sub_section_id' =>
                                'The selected sub-section is not available for this class.',
                        ]);
                    }

                } else {

                    $subSectionId =
                        null;
                }


                /*
                |--------------------------------------------------------------------------
                | No Change
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $newOffering->id
                    ===
                    (int) $enrolment->section_offering_id
                    &&
                    (
                        $subSectionId
                            ? (int) $subSectionId
                            : null
                    )
                    ===
                    (
                        $enrolment->sub_section_id
                            ? (int) $enrolment->sub_section_id
                            : null
                    )
                ) {

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Prevent Overlapping Class Times
                |--------------------------------------------------------------------------
                |
                | Compare the new offering with every other active confirmed class for
                | this student. The enrolment currently being edited must be excluded.
                | Classes that only touch at an endpoint (for example 5:15-6:00 and
                | 6:00-6:45) are allowed.
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $newOffering->id
                    !==
                    (int) $enrolment->section_offering_id
                ) {

                    $otherEnrolments =
                        Enrolment::with([
                            'sectionOffering.section',
                            'sectionOffering.day',
                        ])
                            ->where(
                                'student_id',
                                $student->id
                            )
                            ->where(
                                'id',
                                '!=',
                                $enrolment->id
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->where(
                                'is_wishlist',
                                false
                            )
                            ->get();


                    $newStart =
                        \Carbon\Carbon::parse(
                            $newOffering->start_time
                        );

                    $newEnd =
                        \Carbon\Carbon::parse(
                            $newOffering->end_time
                        );


                    $conflictingEnrolment =
                        $otherEnrolments
                            ->first(
                                function ($otherEnrolment) use (
                                    $newOffering,
                                    $newStart,
                                    $newEnd
                                ) {

                                    $otherOffering =
                                        $otherEnrolment
                                            ->sectionOffering;


                                    if (
                                        !$otherOffering
                                        ||
                                        (int) $otherOffering->day_id
                                        !==
                                        (int) $newOffering->day_id
                                    ) {

                                        return false;
                                    }

                                    $newIsInteractive =
                                        strtolower(
                                            trim(
                                                $newOffering
                                                    ->section
                                                    ?->section_name
                                                ?? ''
                                            )
                                        ) === 'interactive';

                                    $otherIsInteractive =
                                        strtolower(
                                            trim(
                                                $otherOffering
                                                    ->section
                                                    ?->section_name
                                                ?? ''
                                            )
                                        ) === 'interactive';

                                    /* Math and English may overlap. */
                                    if (!$newIsInteractive && !$otherIsInteractive) {
                                        return false;
                                    }


                                    $otherStart =
                                        \Carbon\Carbon::parse(
                                            $otherOffering->start_time
                                        );

                                    $otherEnd =
                                        \Carbon\Carbon::parse(
                                            $otherOffering->end_time
                                        );


                                    return
                                        $newStart->lt($otherEnd)
                                        &&
                                        $otherStart->lt($newEnd);
                                }
                            );


                    if ($conflictingEnrolment) {

                        $conflictingOffering =
                            $conflictingEnrolment
                                ->sectionOffering;

                        $newSectionName =
                            $newOffering
                                ->section
                                ?->section_name
                            ??
                            'Selected class';

                        $conflictingSectionName =
                            $conflictingOffering
                                ->section
                                ?->section_name
                            ??
                            'Existing class';

                        $dayName =
                            $conflictingOffering
                                ->day
                                ?->day_name
                            ??
                            'the selected day';


                        throw ValidationException::withMessages([
                            'section_offering_id' =>
                                'Schedule conflict: '
                                . $newSectionName
                                . ' ('
                                . $newStart->format('g:i A')
                                . ' - '
                                . $newEnd->format('g:i A')
                                . ') overlaps with '
                                . $conflictingSectionName
                                . ' ('
                                . \Carbon\Carbon::parse($conflictingOffering->start_time)->format('g:i A')
                                . ' - '
                                . \Carbon\Carbon::parse($conflictingOffering->end_time)->format('g:i A')
                                . ') on '
                                . $dayName
                                . '. Please choose a different time.',
                        ]);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Capacity
                |--------------------------------------------------------------------------
                |
                | Only check capacity when moving to a different offering.
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $newOffering->id
                    !==
                    (int) $enrolment->section_offering_id
                ) {

                    $allocatedSeats =
                        Enrolment::where(
                            'section_offering_id',
                            $newOffering->id
                        )
                            ->where(
                                'is_active',
                                true
                            )
                            ->where(
                                'is_wishlist',
                                false
                            )
                            ->count();


                    if (
                        $allocatedSeats
                        >=
                        $newOffering->max_seats
                    ) {

                        throw ValidationException::withMessages([
                            'section_offering_id' =>
                                'The selected class is full. Please select another class.',
                        ]);
                    }
                }


                $enrolment->update([
                    'section_offering_id' =>
                        $newOffering->id,

                    'sub_section_id' =>
                        $subSectionId,

                    'is_wishlist' =>
                        false,

                    'wishlist_status' =>
                        null,

                    'is_active' =>
                        true,
                ]);
            }
        );


        return redirect()
            ->route(
                'admin.students.show',
                $student
            )
            ->with(
                'success',
                'Student schedule updated successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Select Class To Remove
    |--------------------------------------------------------------------------
    */

    public function remove(
        Student $student
    ) {
        $student->load([
            'enrolments' =>
                function ($query) {

                    $query
                        ->where(
                            'is_active',
                            true
                        )
                        ->where(
                            'is_wishlist',
                            false
                        );
                },

            'enrolments.sectionOffering.section',
            'enrolments.sectionOffering.day',
            'enrolments.subSection',
        ]);


        $enrolments =
            $student
                ->enrolments;


        return view(
            'admin.student-enrolments.remove',
            compact(
                'student',
                'enrolments'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Remove Class
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Student $student,
        Enrolment $enrolment
    ) {
        if (
            (int)
            $enrolment
                ->student_id
            !==
            (int)
            $student->id
        ) {

            abort(404);
        }


        if (
            !$enrolment
                ->is_active
        ) {

            return redirect()
                ->route(
                    'admin.students.show',
                    $student
                );
        }


        $enrolment->update([
            'is_active' =>
                false,
        ]);


        return redirect()
            ->route(
                'admin.students.show',
                $student
            )
            ->with(
                'success',
                'Class removed successfully.'
            );
    }
}
