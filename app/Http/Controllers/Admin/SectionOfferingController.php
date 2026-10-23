<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Day;
use App\Models\Admin\Section;
use App\Models\Admin\SectionOffering;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SectionOfferingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        /*
         * Only show days which currently contain
         * at least one ACTIVE class offering.
         *
         * Example:
         * Wednesday has no class -> Wednesday is hidden.
         *
         * If a class is created on Wednesday later,
         * Wednesday automatically appears.
         */
        $days = Day::where('is_active', true)
            ->whereIn(
                'id',
                SectionOffering::query()
                    ->where('is_active', true)
                    ->select('day_id')
                    ->distinct()
            )
            ->orderBy('sort_order')
            ->get();


        /*
         * Selected day.
         */
        $requestedDayId = (int) $request->input(
            'day_id',
            0
        );

        if (
            $requestedDayId > 0 &&
            $days->contains('id', $requestedDayId)
        ) {
            $selectedDayId = $requestedDayId;
        } else {
            $selectedDayId = $days->first()?->id;
        }


        /*
         * Selected subject.
         */
        $selectedSubject = strtolower(
            (string) $request->input(
                'subject',
                'english'
            )
        );

        if (
            !in_array(
                $selectedSubject,
                [
                    'english',
                    'math',
                    'interactive',
                ],
                true
            )
        ) {
            $selectedSubject = 'english';
        }


        /*
         * Keep compatibility with existing view.
         */
        $viewType =
            $selectedSubject === 'interactive'
            ? 'interactive'
            : 'regular';


        /*
        |--------------------------------------------------------------------------
        | Sections
        |--------------------------------------------------------------------------
        */

        $sections = Section::where('is_active', true)
            ->with([
                'subSections' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->orderBy('sub_section_name');
                },
            ])
            ->orderBy('section_name')
            ->get();


        $englishSection = $sections->first(
            function ($section) {
                return strtolower(
                    trim($section->section_name)
                ) === 'english';
            }
        );


        $mathSection = $sections->first(
            function ($section) {
                return strtolower(
                    trim($section->section_name)
                ) === 'math';
            }
        );


        $interactiveSection = $sections->first(
            function ($section) {
                return strtolower(
                    trim($section->section_name)
                ) === 'interactive';
            }
        );


        /*
         * Math sub-sections:
         *
         * 3A
         * B-D
         * E+
         */
        $mathSubSections =
            $mathSection?->subSections
            ?? collect();


        /*
         * Interactive sub-sections:
         *
         * English
         * Math
         *
         * Both use one shared capacity of five seats.
         */
        $interactiveSubSections =
            $interactiveSection?->subSections
            ?? collect();


        /*
        |--------------------------------------------------------------------------
        | Load Offerings
        |--------------------------------------------------------------------------
        */

        $offerings = collect();

        if ($selectedDayId) {
            $offerings = SectionOffering::with([
                'day',
                'section',
                'subSections',

                /*
                 * Only confirmed active enrolments
                 * occupy seats.
                 */
                'enrolments' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->where('is_wishlist', false);
                },
            ])
                ->withCount([
                    'enrolments as wishlist_count' =>
                        function ($query) {
                            $query
                                ->where('is_active', true)
                                ->where('is_wishlist', true);
                        },
                ])
                ->where(
                    'day_id',
                    $selectedDayId
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('start_time')
                ->get();
        }


        /*
        |--------------------------------------------------------------------------
        | English Rows
        |--------------------------------------------------------------------------
        */

        $englishRows = collect();

        if ($englishSection) {
            $englishRows = $offerings
                ->where(
                    'section_id',
                    $englishSection->id
                )
                ->map(function ($offering) {
                    $allocated =
                        $offering
                            ->enrolments
                            ->count();

                    return [
                        'id' =>
                            $offering->id,

                        'time' =>
                            Carbon::parse(
                                $offering->start_time
                            )->format('g:i A'),

                        'end_time' =>
                            Carbon::parse(
                                $offering->end_time
                            )->format('g:i A'),

                        'allocated' =>
                            $allocated,

                        'maximum' =>
                            (int)
                            $offering->max_seats,

                        'wishlist_count' =>
                            (int)
                            $offering->wishlist_count,
                    ];
                })
                ->values();
        }


        /*
        |--------------------------------------------------------------------------
        | Math Rows
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | One Math offering has one shared capacity.
        |
        | Example:
        |
        | Math Monday 4:30 PM
        | Maximum seats = 41
        |
        | 3A + B-D + E+ combined <= 41
        |--------------------------------------------------------------------------
        */

        $mathRows = collect();

        if ($mathSection) {
            $mathRows = $offerings
                ->where(
                    'section_id',
                    $mathSection->id
                )
                ->map(
                    function ($offering) use ($mathSubSections) {
                        $allocations = [];

                        foreach (
                            $mathSubSections
                            as $subSection
                        ) {
                            $allocations[
                                $subSection->id
                            ] = $offering
                                    ->enrolments
                                    ->where(
                                        'sub_section_id',
                                        $subSection->id
                                    )
                                    ->count();
                        }


                        /*
                         * Shared Math total.
                         *
                         * Do NOT filter by sub_section_id
                         * here.
                         */
                        $mathTotal =
                            $offering
                                ->enrolments
                                ->count();


                        return [
                            'id' =>
                                $offering->id,

                            'time' =>
                                Carbon::parse(
                                    $offering->start_time
                                )->format('g:i A'),

                            'end_time' =>
                                Carbon::parse(
                                    $offering->end_time
                                )->format('g:i A'),

                            'math_allocations' =>
                                $allocations,

                            'math_total' =>
                                $mathTotal,

                            'math_maximum' =>
                                (int)
                                $offering->max_seats,

                            'wishlist_count' =>
                                (int)
                                $offering->wishlist_count,

                            'available_sub_sections' =>
                                $offering
                                    ->subSections
                                    ->pluck('id')
                                    ->map(
                                        fn($id) =>
                                            (int) $id
                                    )
                                    ->toArray(),
                        ];
                    }
                )
                ->values();
        }


        /*
        |--------------------------------------------------------------------------
        | Interactive Rows
        |--------------------------------------------------------------------------
        */

        $interactiveRows = collect();

        if ($interactiveSection) {
            $interactiveRows = $offerings
                ->where(
                    'section_id',
                    $interactiveSection->id
                )
                ->map(
                    function ($offering) use ($interactiveSubSections) {
                        $interactiveAllocations = [];


                        foreach (
                            $interactiveSubSections
                            as $subSection
                        ) {

                            $interactiveAllocations[
                                $subSection->id
                            ] = $offering
                                    ->enrolments
                                    ->where(
                                        'sub_section_id',
                                        $subSection->id
                                    )
                                    ->count();
                        }


                        /*
                         * Shared Interactive total.
                         *
                         * Do not filter by sub_section_id here.
                         * Interactive English + Interactive Math
                         * must remain within the same maximum of five.
                         */
                        $interactiveTotal =
                            $offering
                                ->enrolments
                                ->count();

                        return [
                            'id' =>
                                $offering->id,

                            'time' =>
                                Carbon::parse(
                                    $offering->start_time
                                )->format('g:i A'),

                            'end_time' =>
                                Carbon::parse(
                                    $offering->end_time
                                )->format('g:i A'),

                            'allocated' =>
                                $interactiveTotal,

                            'interactive_allocations' =>
                                $interactiveAllocations,

                            'maximum' =>
                                (int)
                                $offering->max_seats,

                            'wishlist_count' =>
                                (int)
                                $offering->wishlist_count,

                            'available_sub_sections' =>
                                $offering
                                    ->subSections
                                    ->pluck('id')
                                    ->map(
                                        fn($id) =>
                                            (int) $id
                                    )
                                    ->toArray(),
                        ];
                    }
                )
                ->values();
        }


        /*
        |--------------------------------------------------------------------------
        | Capacity Information
        |--------------------------------------------------------------------------
        */

        $englishCapacity = (int) (
            $englishRows->max(
                'maximum'
            ) ?? 0
        );


        $mathCapacity = (int) (
            $mathRows->max(
                'math_maximum'
            ) ?? 0
        );


        $selectedDay = $days->firstWhere(
            'id',
            (int) $selectedDayId
        );


        return view(
            'admin.section-offerings.index',
            compact(
                'days',
                'selectedDayId',
                'selectedDay',
                'selectedSubject',
                'viewType',

                'englishSection',
                'mathSection',
                'interactiveSection',
                'mathSubSections',
                'interactiveSubSections',

                'englishCapacity',
                'mathCapacity',

                'englishRows',
                'mathRows',
                'interactiveRows'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        /*
         * Create page must show ALL active days.
         *
         * This means Admin can create the first
         * Wednesday class even if Wednesday is
         * currently hidden on the index.
         */
        $days = Day::where(
            'is_active',
            true
        )
            ->orderBy('sort_order')
            ->get();


        $sections = Section::where(
            'is_active',
            true
        )
            ->with([
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
            ->orderBy('section_name')
            ->get();


        /*
         * Prepare simple array for JavaScript.
         *
         * This avoids the Blade @json parsing
         * error you received.
         */
        $sectionsForJs = $this
            ->prepareSectionsForJs(
                $sections
            );


        return view(
            'admin.section-offerings.create',
            compact(
                'days',
                'sections',
                'sectionsForJs'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'day_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'day_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:days,id',
            ],

            'section_id' => [
                'required',
                'integer',
                'exists:sections,id',
            ],

            'sub_section_ids' => [
                'nullable',
                'array',
            ],

            'sub_section_ids.*' => [
                'integer',
                'distinct',
                'exists:sub_sections,id',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'duration_minutes' => [
                'required',
                'integer',
                'min:5',
                'max:480',
            ],

            'max_seats' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);


        $section = Section::with([
            'subSections' =>
                function ($query) {
                    $query->where(
                        'is_active',
                        true
                    );
                },
        ])
            ->where(
                'id',
                $validated[
                    'section_id'
                ]
            )
            ->where(
                'is_active',
                true
            )
            ->first();


        if (!$section) {
            return back()
                ->withInput()
                ->withErrors([
                    'section_id' =>
                        'The selected section is unavailable.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Sub-sections
        |--------------------------------------------------------------------------
        */

        $selectedSubSectionIds =
            collect(
                $validated[
                    'sub_section_ids'
                ] ?? []
            )
                ->map(
                    fn($id) =>
                        (int) $id
                )
                ->unique()
                ->values();


        $availableSubSectionIds =
            $section
                ->subSections
                ->pluck('id')
                ->map(
                    fn($id) =>
                        (int) $id
                )
                ->values();


        /*
         * If section has sub-sections,
         * at least one must be selected.
         */
        if (
            $availableSubSectionIds
                ->isNotEmpty() &&
            $selectedSubSectionIds
                ->isEmpty()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'sub_section_ids' =>
                        'Please select at least one sub-section.',
                ]);
        }


        /*
         * Selected sub-sections must belong
         * to selected section.
         */
        if (
            $selectedSubSectionIds
                ->diff(
                    $availableSubSectionIds
                )
                ->isNotEmpty()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'sub_section_ids' =>
                        'One or more selected sub-sections do not belong to the selected section.',
                ]);
        }


        /*
         * English / Interactive etc.
         */
        if (
            $availableSubSectionIds
                ->isEmpty()
        ) {
            $selectedSubSectionIds =
                collect();
        }


        /*
        |--------------------------------------------------------------------------
        | Days
        |--------------------------------------------------------------------------
        */

        $selectedDays = Day::whereIn(
            'id',
            $validated[
                'day_ids'
            ]
        )
            ->where(
                'is_active',
                true
            )
            ->get();


        if (
            $selectedDays->count()
            !==
            count(
                $validated[
                    'day_ids'
                ]
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'day_ids' =>
                        'One or more selected days are unavailable.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Time
        |--------------------------------------------------------------------------
        */

        $startTime =
            Carbon::createFromFormat(
                'H:i',
                $validated[
                    'start_time'
                ]
            );


        $endTime =
            $startTime
                ->copy()
                ->addMinutes(
                    $validated[
                        'duration_minutes'
                    ]
                );


        if (
            $startTime->format(
                'Y-m-d'
            )
            !==
            $endTime->format(
                'Y-m-d'
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'duration_minutes' =>
                        'The class must finish on the same day.',
                ]);
        }


        $startTimeValue =
            $startTime->format(
                'H:i:s'
            );

        $endTimeValue =
            $endTime->format(
                'H:i:s'
            );


        /*
        |--------------------------------------------------------------------------
        | Prevent Overlapping Offering
        |--------------------------------------------------------------------------
        */

        foreach (
            $selectedDays as $day
        ) {
            $overlapExists =
                SectionOffering::where(
                    'day_id',
                    $day->id
                )
                    ->where(
                        'section_id',
                        $section->id
                    )
                    ->where(
                        'start_time',
                        '<',
                        $endTimeValue
                    )
                    ->where(
                        'end_time',
                        '>',
                        $startTimeValue
                    )
                    ->exists();


            if ($overlapExists) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'start_time' =>
                            $section->section_name .
                            ' already has an overlapping offering on ' .
                            $day->day_name .
                            '.',
                    ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Create
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use ($selectedDays, $section, $validated, $selectedSubSectionIds, $startTimeValue, $endTimeValue) {
                foreach (
                    $selectedDays as $day
                ) {
                    $offering =
                        SectionOffering::create([
                            'day_id' =>
                                $day->id,

                            'section_id' =>
                                $section->id,

                            'start_time' =>
                                $startTimeValue,

                            'duration_minutes' =>
                                $validated[
                                    'duration_minutes'
                                ],

                            'end_time' =>
                                $endTimeValue,

                            'max_seats' =>
                                $validated[
                                    'max_seats'
                                ],

                            'is_active' =>
                                $validated[
                                    'is_active'
                                ],
                        ]);


                    $offering
                        ->subSections()
                        ->sync(
                            $selectedSubSectionIds
                                ->toArray()
                        );
                }
            }
        );


        return redirect()
            ->route(
                'admin.section-offerings.index'
            )
            ->with(
                'success',
                $selectedDays->count() .
                ' class offering(s) created successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        SectionOffering $sectionOffering
    ) {
        $sectionOffering->load([
            'subSections',
        ]);


        $days = Day::where(
            'is_active',
            true
        )
            ->orWhere(
                'id',
                $sectionOffering->day_id
            )
            ->orderBy('sort_order')
            ->get();


        $sections = Section::where(
            'is_active',
            true
        )
            ->orWhere(
                'id',
                $sectionOffering
                    ->section_id
            )
            ->with([
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
            ->orderBy('section_name')
            ->get();


        /*
         * Safe JavaScript data.
         */
        $sectionsForJs =
            $this->prepareSectionsForJs(
                $sections
            );


        $selectedSubSectionIds =
            $sectionOffering
                ->subSections
                ->pluck('id')
                ->map(
                    fn($id) =>
                        (int) $id
                )
                ->values()
                ->toArray();


        return view(
            'admin.section-offerings.edit',
            compact(
                'sectionOffering',
                'days',
                'sections',
                'sectionsForJs',
                'selectedSubSectionIds'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        SectionOffering $sectionOffering
    ) {
        $validated = $request->validate([
            'day_id' => [
                'required',
                'integer',
                'exists:days,id',
            ],

            'section_id' => [
                'required',
                'integer',
                'exists:sections,id',
            ],

            'sub_section_ids' => [
                'nullable',
                'array',
            ],

            'sub_section_ids.*' => [
                'integer',
                'distinct',
                'exists:sub_sections,id',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'duration_minutes' => [
                'required',
                'integer',
                'min:5',
                'max:480',
            ],

            'max_seats' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);


        $day = Day::where(
            'id',
            $validated['day_id']
        )
            ->where(
                'is_active',
                true
            )
            ->first();


        $section = Section::with([
            'subSections' =>
                function ($query) {
                    $query->where(
                        'is_active',
                        true
                    );
                },
        ])
            ->find(
                $validated[
                    'section_id'
                ]
            );


        if (
            !$day ||
            !$section
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'section_id' =>
                        'The selected day or section is unavailable.',
                ]);
        }


        /*
         * Don't change main section when
         * student records already use offering.
         */
        $hasEnrolments =
            $sectionOffering
                ->enrolments()
                ->exists();


        if (
            $hasEnrolments &&
            (int)
            $sectionOffering
                ->section_id
            !==
            (int)
            $section->id
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'section_id' =>
                        'The section cannot be changed because student enrolment records are already attached to this offering.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Sub-sections
        |--------------------------------------------------------------------------
        */

        $selectedSubSectionIds =
            collect(
                $validated[
                    'sub_section_ids'
                ] ?? []
            )
                ->map(
                    fn($id) =>
                        (int) $id
                )
                ->unique()
                ->values();


        $availableSubSectionIds =
            $section
                ->subSections
                ->pluck('id')
                ->map(
                    fn($id) =>
                        (int) $id
                )
                ->values();


        if (
            $availableSubSectionIds
                ->isNotEmpty() &&
            $selectedSubSectionIds
                ->isEmpty()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'sub_section_ids' =>
                        'Please select at least one sub-section.',
                ]);
        }


        if (
            $selectedSubSectionIds
                ->diff(
                    $availableSubSectionIds
                )
                ->isNotEmpty()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'sub_section_ids' =>
                        'One or more selected sub-sections do not belong to the selected section.',
                ]);
        }


        if (
            $availableSubSectionIds
                ->isEmpty()
        ) {
            $selectedSubSectionIds =
                collect();
        }


        /*
         * Don't remove a sub-section if students
         * are currently enrolled in it.
         */
        $usedSubSectionIds =
            $sectionOffering
                ->enrolments()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'is_wishlist',
                    false
                )
                ->whereNotNull(
                    'sub_section_id'
                )
                ->pluck(
                    'sub_section_id'
                )
                ->map(
                    fn($id) =>
                        (int) $id
                )
                ->unique();


        if (
            $usedSubSectionIds
                ->diff(
                    $selectedSubSectionIds
                )
                ->isNotEmpty()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'sub_section_ids' =>
                        'A sub-section cannot be removed while students are enrolled in it.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Time
        |--------------------------------------------------------------------------
        */

        $startTime =
            Carbon::createFromFormat(
                'H:i',
                $validated[
                    'start_time'
                ]
            );


        $endTime =
            $startTime
                ->copy()
                ->addMinutes(
                    $validated[
                        'duration_minutes'
                    ]
                );


        if (
            $startTime->format(
                'Y-m-d'
            )
            !==
            $endTime->format(
                'Y-m-d'
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'duration_minutes' =>
                        'The class must finish on the same day.',
                ]);
        }


        $startTimeValue =
            $startTime->format(
                'H:i:s'
            );

        $endTimeValue =
            $endTime->format(
                'H:i:s'
            );


        /*
        |--------------------------------------------------------------------------
        | Prevent Overlap
        |--------------------------------------------------------------------------
        */

        $overlapExists =
            SectionOffering::where(
                'day_id',
                $validated[
                    'day_id'
                ]
            )
                ->where(
                    'section_id',
                    $validated[
                        'section_id'
                    ]
                )
                ->where(
                    'id',
                    '!=',
                    $sectionOffering->id
                )
                ->where(
                    'start_time',
                    '<',
                    $endTimeValue
                )
                ->where(
                    'end_time',
                    '>',
                    $startTimeValue
                )
                ->exists();


        if ($overlapExists) {
            return back()
                ->withInput()
                ->withErrors([
                    'start_time' =>
                        $section->section_name .
                        ' already has an overlapping offering on ' .
                        $day->day_name .
                        '.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Shared Capacity Protection
        |--------------------------------------------------------------------------
        */

        $currentStudents =
            $sectionOffering
                ->enrolments()
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
            $validated[
                'max_seats'
            ]
            <
            $currentStudents
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'max_seats' =>
                        'Maximum seats cannot be lower than the current enrolled student count of ' .
                        $currentStudents .
                        '.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use ($sectionOffering, $validated, $selectedSubSectionIds, $startTimeValue, $endTimeValue) {
                $sectionOffering->update([
                    'day_id' =>
                        $validated[
                            'day_id'
                        ],

                    'section_id' =>
                        $validated[
                            'section_id'
                        ],

                    'start_time' =>
                        $startTimeValue,

                    'duration_minutes' =>
                        $validated[
                            'duration_minutes'
                        ],

                    'end_time' =>
                        $endTimeValue,

                    'max_seats' =>
                        $validated[
                            'max_seats'
                        ],

                    'is_active' =>
                        $validated[
                            'is_active'
                        ],
                ]);


                $sectionOffering
                    ->subSections()
                    ->sync(
                        $selectedSubSectionIds
                            ->toArray()
                    );
            }
        );


        return redirect()
            ->route(
                'admin.section-offerings.index'
            )
            ->with(
                'success',
                'Class offering updated successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        SectionOffering $sectionOffering
    ): RedirectResponse {
        $request->validate([
            'confirm_delete' => [
                'required',
                'accepted',
            ],
        ]);

        DB::transaction(function () use ($sectionOffering) {
            DB::table(
                'section_offering_sub_section'
            )
                ->where(
                    'section_offering_id',
                    $sectionOffering->id
                )
                ->delete();

            DB::table('enrolments')
                ->where(
                    'section_offering_id',
                    $sectionOffering->id
                )
                ->delete();

            $sectionOffering->delete();
        });

        return redirect()
            ->route(
                'admin.section-offerings.index'
            )
            ->with(
                'success',
                'Class and related class data deleted successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Prepare Sections For JavaScript
    |--------------------------------------------------------------------------
    */

    private function prepareSectionsForJs(
        $sections
    ): array {
        return $sections
            ->map(function ($section) {
                return [
                    'id' =>
                        (int) $section->id,

                    'name' =>
                        $section
                            ->section_name,

                    'sub_sections' =>
                        $section
                            ->subSections
                            ->map(
                                function ($subSection) {
                                    return [
                                        'id' =>
                                            (int)
                                            $subSection->id,

                                        'name' =>
                                            $subSection
                                                ->sub_section_name,
                                    ];
                                }
                            )
                            ->values()
                            ->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }



    public function confirmDestroy(
        SectionOffering $sectionOffering
    ): View {
        $enrolments = DB::table('enrolments')
            ->where(
                'section_offering_id',
                $sectionOffering->id
            )
            ->get();

        $subSections = DB::table(
            'section_offering_sub_section'
        )
            ->leftJoin(
                'sub_sections',
                'sub_sections.id',
                '=',
                'section_offering_sub_section.sub_section_id'
            )
            ->where(
                'section_offering_sub_section.section_offering_id',
                $sectionOffering->id
            )
            ->select(
                'section_offering_sub_section.*',
                'sub_sections.sub_section_name'
            )
            ->get();

        return view(
            'admin.section-offerings.confirm-delete',
            [
                'sectionOffering' => $sectionOffering,
                'enrolments' => $enrolments,
                'subSections' => $subSections,
            ]
        );
    }
}
