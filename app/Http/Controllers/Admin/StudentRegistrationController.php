<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Enrolment;
use App\Models\Admin\Guardian;
use App\Models\Admin\SectionOffering;
use App\Models\Admin\Student;
use App\Models\Admin\StudentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentRegistrationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Step 1: Student Details
    |--------------------------------------------------------------------------
    */

    public function studentStep()
    {
        $studentStatuses =
            StudentStatus::where(
                'is_active',
                true
            )
                ->orderBy(
                    'status_name',
                    'asc'
                )
                ->get();


        $studentData =
            session(
                'student_registration.student',
                []
            );


        return view(
            'admin.student-registration.student',
            compact(
                'studentStatuses',
                'studentData'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Step 1: Store Student Details
    |--------------------------------------------------------------------------
    */

    public function storeStudentStep(
        Request $request
    ) {
        $validated =
            $request->validate([
                'external_id' => [
                    'nullable',
                    'string',
                    'max:50',
                    'unique:students,external_id',
                ],

                'student_status_id' => [
                    'required',
                    'integer',
                    'exists:student_statuses,id',
                ],

                'first_name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'last_name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'nickname' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'date_of_birth' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                ],
            ]);


        $statusAvailable =
            StudentStatus::where(
                'id',
                $validated[
                    'student_status_id'
                ]
            )
                ->where(
                    'is_active',
                    true
                )
                ->exists();


        if (!$statusAvailable) {
            return back()
                ->withInput()
                ->withErrors([
                    'student_status_id' =>
                        'The selected student status is unavailable.',
                ]);
        }


        $validated['external_id'] =
            isset(
                $validated[
                    'external_id'
                ]
            )
            &&
            trim(
                $validated[
                    'external_id'
                ]
            )
            !==
            ''
                ? trim(
                    $validated[
                        'external_id'
                    ]
                )
                : null;


        $validated['first_name'] =
            trim(
                $validated[
                    'first_name'
                ]
            );


        $validated['last_name'] =
            trim(
                $validated[
                    'last_name'
                ]
            );


        $validated['nickname'] =
            trim(
                $validated['nickname'] ?? ''
            ) ?: null;


        session([
            'student_registration.student' =>
                $validated,
        ]);


        return redirect()
            ->route(
                'admin.student-registration.guardians'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Step 2: Guardian Details
    |--------------------------------------------------------------------------
    */

    public function guardianStep()
    {
        if (
            !session()->has(
                'student_registration.student'
            )
        ) {
            return redirect()
                ->route(
                    'admin.student-registration.student'
                )
                ->with(
                    'error',
                    'Complete the student details first.'
                );
        }


        $guardianData =
            session(
                'student_registration.guardians',
                [
                    'new' => [],
                ]
            );


        return view(
            'admin.student-registration.guardians',
            compact(
                'guardianData'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Step 2: Store Guardian Details
    |--------------------------------------------------------------------------
    */

    public function storeGuardianStep(
        Request $request
    ) {
        if (
            !session()->has(
                'student_registration.student'
            )
        ) {
            return redirect()
                ->route(
                    'admin.student-registration.student'
                );
        }


        $validated =
            $request->validate([
                'new_guardians' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'new_guardians.*.first_name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'new_guardians.*.last_name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'new_guardians.*.email' => [
                    'required',
                    'email',
                    'max:150',
                ],

                'new_guardians.*.phone' => [
                    'required',
                    'string',
                    'max:30',
                ],
            ]);


        $guardianData = [];


        foreach (
            $validated[
                'new_guardians'
            ]
            as $guardian
        ) {
            $guardianData[] = [
                'first_name' =>
                    trim(
                        $guardian[
                            'first_name'
                        ]
                    ),

                'last_name' =>
                    trim(
                        $guardian[
                            'last_name'
                        ]
                    ),

                'email' =>
                    trim(
                        $guardian[
                            'email'
                        ]
                    ),

                'phone' =>
                    trim(
                        $guardian[
                            'phone'
                        ]
                    ),
            ];
        }


        session([
            'student_registration.guardians' => [
                'new' =>
                    $guardianData,
            ],
        ]);


        return redirect()
            ->route(
                'admin.student-registration.enrolments'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Step 3: Class Enrolment
    |--------------------------------------------------------------------------
    */

    public function enrolmentStep()
    {
        if (
            !session()->has(
                'student_registration.student'
            )
        ) {
            return redirect()
                ->route(
                    'admin.student-registration.student'
                )
                ->with(
                    'error',
                    'Complete the student details first.'
                );
        }


        if (
            !session()->has(
                'student_registration.guardians'
            )
        ) {
            return redirect()
                ->route(
                    'admin.student-registration.guardians'
                )
                ->with(
                    'error',
                    'Complete the guardian details first.'
                );
        }


        $studentData =
            session(
                'student_registration.student'
            );


        $guardianData =
            session(
                'student_registration.guardians'
            );


        /*
        |--------------------------------------------------------------------------
        | Active Section Offerings
        |--------------------------------------------------------------------------
        |
        | Math:
        |
        | One offering contains:
        |   3A
        |   B-D
        |   E+
        |
        | Capacity remains shared at offering level.
        |--------------------------------------------------------------------------
        */

        $sectionOfferings =
            SectionOffering::query()
                ->select(
                    'section_offerings.*'
                )
                ->join(
                    'days',
                    'days.id',
                    '=',
                    'section_offerings.day_id'
                )
                ->join(
                    'sections',
                    'sections.id',
                    '=',
                    'section_offerings.section_id'
                )
                ->with([
                    'day',
                    'section',

                    'subSections' =>
                        function ($query) {
                            $query
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->orderBy(
                                    'sub_section_name'
                                );
                        },
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
                    'section_offerings.is_active',
                    true
                )
                ->where(
                    'days.is_active',
                    true
                )
                ->where(
                    'sections.is_active',
                    true
                )
                ->orderBy(
                    'sections.section_name'
                )
                ->orderBy(
                    'days.sort_order'
                )
                ->orderBy(
                    'section_offerings.start_time'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Shared Available Seats
        |--------------------------------------------------------------------------
        |
        | Math 3A + B-D + E+ all count against
        | this one offering capacity.
        |--------------------------------------------------------------------------
        */

        foreach (
            $sectionOfferings
            as $offering
        ) {
            $offering->available_seats =
                max(
                    0,
                    (int)
                    $offering->max_seats
                    -
                    (int)
                    $offering->allocated_seats
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Available Sub-sections For Filter
        |--------------------------------------------------------------------------
        */

        $availableSubSections =
            $sectionOfferings
                ->flatMap(
                    function ($offering) {
                        return
                            $offering
                                ->subSections;
                    }
                )
                ->unique('id')
                ->sortBy(
                    'sub_section_name'
                )
                ->values();


        return view(
            'admin.student-registration.enrolments',
            compact(
                'studentData',
                'guardianData',
                'sectionOfferings',
                'availableSubSections'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Complete Registration
    |--------------------------------------------------------------------------
    */

    public function complete(
        Request $request
    ) {
        /*
        |--------------------------------------------------------------------------
        | Check Wizard Session
        |--------------------------------------------------------------------------
        */

        if (
            !session()->has(
                'student_registration.student'
            )
        ) {
            return redirect()
                ->route(
                    'admin.student-registration.student'
                );
        }


        if (
            !session()->has(
                'student_registration.guardians'
            )
        ) {
            return redirect()
                ->route(
                    'admin.student-registration.guardians'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Class Selection
        |--------------------------------------------------------------------------
        |
        | sub_section_ids is keyed by offering ID:
        |
        | sub_section_ids[12] = 3
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([
                'confirmed_ids' => [
                    'nullable',
                    'array',
                ],

                'confirmed_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:section_offerings,id',
                ],

                'wishlist_ids' => [
                    'nullable',
                    'array',
                ],

                'wishlist_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:section_offerings,id',
                ],

                'sub_section_ids' => [
                    'nullable',
                    'array',
                ],

                'sub_section_ids.*' => [
                    'nullable',
                    'integer',
                    'exists:sub_sections,id',
                ],
            ]);


        $confirmedIds =
            array_map(
                'intval',
                $validated[
                    'confirmed_ids'
                ]
                ?? []
            );


        $wishlistIds =
            array_map(
                'intval',
                $validated[
                    'wishlist_ids'
                ]
                ?? []
            );


        /*
         * Keep key = offering ID.
         */
        $subSectionIds = [];


        foreach (
            $validated[
                'sub_section_ids'
            ]
            ?? []
            as $offeringId =>
                $subSectionId
        ) {
            if (
                $subSectionId ===
                null
                ||
                $subSectionId ===
                ''
            ) {
                continue;
            }


            $subSectionIds[
                (int)
                $offeringId
            ] =
                (int)
                $subSectionId;
        }


        /*
        |--------------------------------------------------------------------------
        | At Least One Class
        |--------------------------------------------------------------------------
        */

        if (
            empty($confirmedIds)
            &&
            empty($wishlistIds)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'classes' =>
                        'Select at least one class or wishlist option.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Same Offering Cannot Be Both
        |--------------------------------------------------------------------------
        */

        $duplicateIds =
            array_intersect(
                $confirmedIds,
                $wishlistIds
            );


        if (
            !empty(
                $duplicateIds
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'classes' =>
                        'A class offering cannot be selected as both confirmed and wishlist.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | All Selected Offerings
        |--------------------------------------------------------------------------
        */

        $selectedIds =
            array_values(
                array_unique(
                    array_merge(
                        $confirmedIds,
                        $wishlistIds
                    )
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Load Selected Offerings
        |--------------------------------------------------------------------------
        */

        $selectedOfferings =
            SectionOffering::with([
                'section',
                'day',

                'subSections' =>
                    function ($query) {
                        $query
                            ->where(
                                'is_active',
                                true
                            );
                    },
            ])
                ->whereIn(
                    'id',
                    $selectedIds
                )
                ->where(
                    'is_active',
                    true
                )
                ->get();


        if (
            $selectedOfferings->count()
            !==
            count($selectedIds)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'classes' =>
                        'One or more selected classes are unavailable.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent Overlapping Class Times
        |--------------------------------------------------------------------------
        */

        $offeringsByDay =
            $selectedOfferings
                ->groupBy('day_id');

        foreach ($offeringsByDay as $dayOfferings) {

            $sortedDayOfferings =
                $dayOfferings
                    ->sortBy('start_time')
                    ->values();

            for ($i = 0; $i < $sortedDayOfferings->count(); $i++) {

                $firstOffering =
                    $sortedDayOfferings[$i];

                $firstStart =
                    \Carbon\Carbon::parse(
                        $firstOffering->start_time
                    );

                $firstEnd =
                    \Carbon\Carbon::parse(
                        $firstOffering->end_time
                    );

                for ($j = $i + 1; $j < $sortedDayOfferings->count(); $j++) {

                    $secondOffering =
                        $sortedDayOfferings[$j];

                    $secondStart =
                        \Carbon\Carbon::parse(
                            $secondOffering->start_time
                        );

                    $secondEnd =
                        \Carbon\Carbon::parse(
                            $secondOffering->end_time
                        );

                    $firstIsInteractive =
                        strtolower(
                            trim(
                                $firstOffering
                                    ->section
                                    ?->section_name
                                ?? ''
                            )
                        ) === 'interactive';

                    $secondIsInteractive =
                        strtolower(
                            trim(
                                $secondOffering
                                    ->section
                                    ?->section_name
                                ?? ''
                            )
                        ) === 'interactive';

                    /* Math and English may overlap. */
                    if (!$firstIsInteractive && !$secondIsInteractive) {
                        continue;
                    }

                    $overlaps =
                        $firstStart->lt($secondEnd)
                        &&
                        $secondStart->lt($firstEnd);

                    if (!$overlaps) {
                        continue;
                    }

                    $firstName =
                        $firstOffering
                            ->section
                            ?->section_name
                        ?? 'Class';

                    $secondName =
                        $secondOffering
                            ->section
                            ?->section_name
                        ?? 'Class';

                    $dayName =
                        $firstOffering
                            ->day
                            ?->day_name
                        ?? 'the selected day';

                    return back()
                        ->withInput()
                        ->withErrors([
                            'classes' =>
                                'Schedule conflict: '
                                . $firstName
                                . ' ('
                                . \Carbon\Carbon::parse($firstOffering->start_time)->format('g:i A')
                                . ' - '
                                . \Carbon\Carbon::parse($firstOffering->end_time)->format('g:i A')
                                . ') overlaps with '
                                . $secondName
                                . ' ('
                                . \Carbon\Carbon::parse($secondOffering->start_time)->format('g:i A')
                                . ' - '
                                . \Carbon\Carbon::parse($secondOffering->end_time)->format('g:i A')
                                . ') on '
                                . $dayName
                                . '. Please choose a different time.',
                        ]);
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Required Sub-section
        |--------------------------------------------------------------------------
        |
        | If an offering has sub-sections,
        | selecting one is compulsory.
        |--------------------------------------------------------------------------
        */

        foreach (
            $selectedOfferings
            as $offering
        ) {
            if (
                $offering
                    ->subSections
                    ->isEmpty()
            ) {
                continue;
            }


            $selectedSubSectionId =
                $subSectionIds[
                    $offering->id
                ]
                ??
                null;


            if (
                !$selectedSubSectionId
            ) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'classes' =>
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
                            ' at '
                            .
                            \Carbon\Carbon::parse(
                                $offering
                                    ->start_time
                            )->format(
                                'g:i A'
                            )
                            .
                            '.',
                    ]);
            }


            /*
             * Selected sub-section must actually
             * be attached to this offering.
             */
            $subSectionBelongs =
                $offering
                    ->subSections
                    ->contains(
                        'id',
                        $selectedSubSectionId
                    );


            if (!$subSectionBelongs) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'classes' =>
                            'The selected sub-section is not available for this class offering.',
                    ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Session Data
        |--------------------------------------------------------------------------
        */

        $studentData =
            session(
                'student_registration.student'
            );


        $guardianData =
            session(
                'student_registration.guardians'
            );


        /*
        |--------------------------------------------------------------------------
        | Recheck Student ID
        |--------------------------------------------------------------------------
        */

        $studentIdExists =
            false;


        if (
            !empty(
                $studentData[
                    'external_id'
                ]
            )
        ) {

            $studentIdExists =
                Student::where(
                    'external_id',
                    $studentData[
                        'external_id'
                    ]
                )
                    ->exists();
        }


        if ($studentIdExists) {
            return redirect()
                ->route(
                    'admin.student-registration.student'
                )
                ->withErrors([
                    'external_id' =>
                        'This student ID has already been used.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Recheck Student Status
        |--------------------------------------------------------------------------
        */

        $statusAvailable =
            StudentStatus::where(
                'id',
                $studentData[
                    'student_status_id'
                ]
            )
                ->where(
                    'is_active',
                    true
                )
                ->exists();


        if (!$statusAvailable) {
            return redirect()
                ->route(
                    'admin.student-registration.student'
                )
                ->withErrors([
                    'student_status_id' =>
                        'The selected student status is unavailable.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Save Registration
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $studentData,
                $guardianData,
                $wishlistIds,
                $selectedIds,
                $subSectionIds
            ) {
                /*
                |--------------------------------------------------------------------------
                | Create Student
                |--------------------------------------------------------------------------
                */

                $student =
                    Student::create([
                        'external_id' =>
                            $studentData[
                                'external_id'
                            ],

                        'student_status_id' =>
                            $studentData[
                                'student_status_id'
                            ],

                        'first_name' =>
                            $studentData[
                                'first_name'
                            ],

                        'last_name' =>
                            $studentData[
                                'last_name'
                            ],

                        'nickname' =>
                            $studentData[
                                'nickname'
                            ] ?? null,

                        'email' =>
                            null,

                        'phone' =>
                            null,

                        'date_of_birth' =>
                            $studentData[
                                'date_of_birth'
                            ],

                        'address' =>
                            null,

                        'can_leave_alone' =>
                            false,

                        'notes' =>
                            null,

                        'join_date' =>
                            now()
                                ->toDateString(),

                        'is_active' =>
                            true,

                        'inactive_since' =>
                            null,
                    ]);


                /*
                |--------------------------------------------------------------------------
                | Create Guardians
                |--------------------------------------------------------------------------
                */

                foreach (
                    $guardianData[
                        'new'
                    ]
                    ?? []
                    as $newGuardian
                ) {
                    $guardian =
                        Guardian::create([
                            'user_id' =>
                                null,

                            'first_name' =>
                                $newGuardian[
                                    'first_name'
                                ],

                            'last_name' =>
                                $newGuardian[
                                    'last_name'
                                ],

                            'email' =>
                                $newGuardian[
                                    'email'
                                ],

                            'phone' =>
                                $newGuardian[
                                    'phone'
                                ],

                            'address' =>
                                null,

                            'is_active' =>
                                true,
                        ]);


                    $student
                        ->guardians()
                        ->attach(
                            $guardian->id,
                            [
                                'relationship' =>
                                    null,

                                'is_primary' =>
                                    false,

                                'is_emergency_contact' =>
                                    false,
                            ]
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Create Enrolments
                |--------------------------------------------------------------------------
                */

                foreach (
                    $selectedIds
                    as $offeringId
                ) {
                    $isWishlist =
                        in_array(
                            $offeringId,
                            $wishlistIds,
                            true
                        );


                    /*
                     * Lock offering while checking
                     * current shared capacity.
                     */
                    $offering =
                        SectionOffering::with([
                            'subSections',
                        ])
                            ->where(
                                'id',
                                $offeringId
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->lockForUpdate()
                            ->first();


                    if (!$offering) {
                        throw
                            ValidationException::withMessages([
                                'classes' =>
                                    'A selected class is no longer available.',
                            ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Recheck Sub-section
                    |--------------------------------------------------------------------------
                    */

                    $selectedSubSectionId =
                        $subSectionIds[
                            $offeringId
                        ]
                        ??
                        null;


                    if (
                        $offering
                            ->subSections
                            ->isNotEmpty()
                    ) {
                        if (
                            !$selectedSubSectionId
                        ) {
                            throw
                                ValidationException::withMessages([
                                    'classes' =>
                                        'A sub-section must be selected for Math.',
                                ]);
                        }


                        if (
                            !$offering
                                ->subSections
                                ->contains(
                                    'id',
                                    $selectedSubSectionId
                                )
                        ) {
                            throw
                                ValidationException::withMessages([
                                    'classes' =>
                                        'The selected Math sub-section is no longer available.',
                                ]);
                        }
                    } else {
                        $selectedSubSectionId =
                            null;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Shared Capacity Check
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT FOR MATH:
                    |
                    | We count the whole section_offering_id.
                    |
                    | We DO NOT filter by sub_section_id.
                    |
                    | 3A + B-D + E+ together <= 41.
                    |--------------------------------------------------------------------------
                    */

                    if (!$isWishlist) {
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
                            $offering
                                ->max_seats
                        ) {
                            throw
                                ValidationException::withMessages([
                                    'classes' =>
                                        'One selected class is now full. Select it as wishlist instead.',
                                ]);
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Save Enrolment
                    |--------------------------------------------------------------------------
                    */

                    Enrolment::create([
                        'student_id' =>
                            $student->id,

                        'section_offering_id' =>
                            $offering->id,

                        'sub_section_id' =>
                            $selectedSubSectionId,

                        'wishlist_for_enrolment_id' =>
                            null,

                        'enrolment_date' =>
                            now()
                                ->toDateString(),

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


        /*
        |--------------------------------------------------------------------------
        | Clear Registration Session
        |--------------------------------------------------------------------------
        */

        session()->forget(
            'student_registration'
        );


        return redirect()
            ->route(
                'admin.students.index'
            )
            ->with(
                'success',
                'Student, guardian and class enrolment details were created successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Cancel Registration
    |--------------------------------------------------------------------------
    */

    public function cancel()
    {
        session()->forget(
            'student_registration'
        );


        return redirect()
            ->route(
                'admin.student-registration.student'
            );
    }
}
