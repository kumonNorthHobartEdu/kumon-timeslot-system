<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Attendance;
use App\Models\Admin\Day;
use App\Models\Admin\Enrolment;
use App\Models\Admin\Section;
use App\Models\Admin\SectionOffering;
use App\Models\Admin\StudentLeave;
use App\Models\Admin\SubSection;
use App\Services\ScheduleExcelExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Schedule
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $context =
            $this->getDayContext(
                $request->input('day_id')
            );


        $days =
            $context['days'];


        $dayDates =
            $context['dayDates'];


        $selectedDay =
            $context['selectedDay'];


        $selectedDayId =
            $selectedDay?->id;


        $selectedDate =
            $selectedDay
            ? $dayDates[$selectedDay->id]
            : null;


        /*
        |--------------------------------------------------------------------------
        | No Active Day
        |--------------------------------------------------------------------------
        */

        if (!$selectedDay) {

            return view(
                'admin.schedule.index',
                [
                    'days' =>
                        $days,

                    'dayDates' =>
                        $dayDates,

                    'selectedDay' =>
                        null,

                    'selectedDayId' =>
                        null,

                    'selectedDate' =>
                        null,

                    'scheduleRows' =>
                        collect(),

                    'timeOfferings' =>
                        collect(),

                    'selectedTime' =>
                        null,

                    'selectedOffering' =>
                        null,

                    'previewEnrolments' =>
                        collect(),

                    'vacationStudentIds' =>
                        collect(),

                    'totalClasses' =>
                        0,

                    'totalStudents' =>
                        0,

                    'totalWishlist' =>
                        0,
                ]
            );
        }


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
                    'day_id',
                    $selectedDay->id
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'start_time'
                )
                ->orderBy(
                    'section_id'
                )
                ->get();


        $offeringIds =
            $offerings
                ->pluck('id');


        $totalClasses =
            $offerings
                ->count();


        if (
            $offeringIds
                ->isEmpty()
        ) {

            $totalStudents =
                0;


            $totalWishlist =
                0;

        } else {

            $totalStudents =
                Enrolment::whereIn(
                    'section_offering_id',
                    $offeringIds
                )
                    ->where(
                        'is_active',
                        true
                    )
                    ->where(
                        'is_wishlist',
                        false
                    )
                    ->distinct()
                    ->count(
                        'student_id'
                    );


            $totalWishlist =
                Enrolment::whereIn(
                    'section_offering_id',
                    $offeringIds
                )
                    ->where(
                        'is_active',
                        true
                    )
                    ->where(
                        'is_wishlist',
                        true
                    )
                    ->count();
        }


        /*
        |--------------------------------------------------------------------------
        | Group Offerings By Time
        |--------------------------------------------------------------------------
        */

        $scheduleRows =
            collect();


        $groupedOfferings =
            $offerings
                ->groupBy(
                    function ($offering) {

                        return
                            Carbon::parse(
                                $offering
                                    ->start_time
                            )->format(
                                    'H:i:s'
                                );
                    }
                );


        foreach (
            $groupedOfferings
            as $rawTime =>
            $group
        ) {

            $scheduleRows->push([
                'raw_time' =>
                    $rawTime,

                'time' =>
                    Carbon::parse(
                        $rawTime
                    )->format(
                            'g:i A'
                        ),

                'class_count' =>
                    $group
                        ->count(),

                'enrolment_count' =>
                    $group
                        ->sum(
                            'allocated_seats'
                        ),

                'wishlist_count' =>
                    $group
                        ->sum(
                            'wishlist_count'
                        ),
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Selected Time
        |--------------------------------------------------------------------------
        */

        $selectedTime =
            $request->input(
                'time'
            );


        $validTimes =
            $scheduleRows
                ->pluck(
                    'raw_time'
                )
                ->toArray();


        if (
            !$selectedTime
            ||
            !in_array(
                $selectedTime,
                $validTimes,
                true
            )
        ) {

            $selectedTime =
                $scheduleRows
                    ->first()['raw_time']
                ??
                null;
        }


        /*
        |--------------------------------------------------------------------------
        | Offerings At Selected Time
        |--------------------------------------------------------------------------
        */

        $timeOfferings =
            $offerings
                ->filter(
                    function ($offering) use ($selectedTime) {

                        return
                            Carbon::parse(
                                $offering
                                    ->start_time
                            )->format(
                                    'H:i:s'
                                )
                            ===
                            $selectedTime;
                    }
                )
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Selected Offering
        |--------------------------------------------------------------------------
        */

        $requestedOfferingId =
            (int)
            $request->input(
                'offering_id'
            );


        $selectedOffering =
            $timeOfferings
                ->firstWhere(
                    'id',
                    $requestedOfferingId
                );


        if (!$selectedOffering) {

            $selectedOffering =
                $timeOfferings
                    ->first();
        }


        /*
        |--------------------------------------------------------------------------
        | Preview Students
        |--------------------------------------------------------------------------
        */

        $previewEnrolments =
            collect();


        $vacationStudentIds =
            collect();


        if ($selectedOffering) {

            $selectedOffering->load([
                'section',
                'day',
                'subSections',

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
                            )
                            ->orderBy(
                                'student_id'
                            );
                    },

                'enrolments.subSection',
                'enrolments.student.studentStatus',
                'enrolments.student.guardians',
            ]);


            $previewEnrolments =
                $selectedOffering
                    ->enrolments
                    ->take(8);


            $previewStudentIds =
                $previewEnrolments
                    ->pluck(
                        'student_id'
                    );


            if (
                $selectedDate
                &&
                $previewStudentIds
                    ->isNotEmpty()
            ) {

                $vacationStudentIds =
                    StudentLeave::activeOnDate(
                        $selectedDate
                    )
                        ->whereIn(
                            'student_id',
                            $previewStudentIds
                        )
                        ->pluck(
                            'student_id'
                        )
                        ->map(
                            fn($id) =>
                                (int)
                                $id
                        )
                        ->unique()
                        ->values();
            }
        }


        return view(
            'admin.schedule.index',
            compact(
                'days',
                'dayDates',
                'selectedDay',
                'selectedDayId',
                'selectedDate',
                'scheduleRows',
                'timeOfferings',
                'selectedTime',
                'selectedOffering',
                'previewEnrolments',
                'vacationStudentIds',
                'totalClasses',
                'totalStudents',
                'totalWishlist'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Class Students
    |--------------------------------------------------------------------------
    */

    public function classStudents(
        Request $request,
        SectionOffering $sectionOffering
    ) {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'sub_section_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = trim($validated['search'] ?? '');
        $selectedSubSectionId = $validated['sub_section_id'] ?? null;

        $sectionOffering->load([
            'section',
            'day',
            'subSections',
        ]);

        $subSections = $sectionOffering->subSections
            ->sortBy('sub_section_name')
            ->values();

        // Always restrict results to this class.
        $baseQuery = Enrolment::query()
            ->where('section_offering_id', $sectionOffering->id)
            ->where('is_active', true)
            ->where('is_wishlist', false);

        // Keep the summary count for the whole class.
        $confirmedCount = (clone $baseQuery)->count();

        $query = (clone $baseQuery)->with([
            'subSection',
            'student.studentStatus',
            'student.guardians',
        ]);

        if ($selectedSubSectionId !== null) {
            $query->where('sub_section_id', $selectedSubSectionId);
        }

        if ($search !== '') {
            // Supports first name, last name, full name and Student ID.
            $terms = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY);

            $query->whereHas('student', function ($studentQuery) use ($terms) {
                foreach ($terms as $term) {
                    $studentQuery->where(function ($match) use ($term) {
                        $match
                            ->where('first_name', 'like', '%' . $term . '%')
                            ->orWhere('last_name', 'like', '%' . $term . '%')
                            ->orWhere('external_id', 'like', '%' . $term . '%');
                    });
                }
            });
        }

        $enrolments = $query
            ->orderBy('student_id')
            ->orderBy('id')
            ->paginate(20)
            ->appends([
                'search' => $search,
                'sub_section_id' => $selectedSubSectionId,
            ]);

        $wishlistCount = Enrolment::query()
            ->where('section_offering_id', $sectionOffering->id)
            ->where('is_active', true)
            ->where('is_wishlist', true)
            ->count();

        $today = now()->startOfDay();
        $dayName = $sectionOffering->day?->day_name;

        if (
            $dayName
            && strtolower($dayName) !== strtolower($today->format('l'))
        ) {
            $classDate = $today->copy()->next($dayName);
        } else {
            $classDate = $today->copy();
        }

        $studentIds = $enrolments
            ->getCollection()
            ->pluck('student_id')
            ->unique();

        $vacationStudentIds = collect();

        if ($studentIds->isNotEmpty()) {
            $vacationStudentIds = StudentLeave::activeOnDate($classDate)
                ->whereIn('student_id', $studentIds)
                ->pluck('student_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();
        }

        return view('admin.schedule.class-students', compact(
            'sectionOffering',
            'enrolments',
            'confirmedCount',
            'wishlistCount',
            'vacationStudentIds',
            'classDate',
            'subSections',
            'search',
            'selectedSubSectionId'
        ));
    }


    /*
    |--------------------------------------------------------------------------
    | Export Form
    |--------------------------------------------------------------------------
    */

    public function exportForm(
        Request $request
    ) {
        $requestedDayId =
            $request->input(
                'day_id'
            );


        $allDaysSelected =
            $requestedDayId
            ===
            'all';


        $selectedExportDate =
            $request->input(
                'date'
            );


        if ($selectedExportDate) {

            try {

                $dateForDay =
                    Carbon::parse(
                        $selectedExportDate
                    );


                $dateDay =
                    Day::whereRaw(
                        'LOWER(day_name) = ?',
                        [
                            strtolower(
                                $dateForDay->format(
                                    'l'
                                )
                            ),
                        ]
                    )
                        ->where(
                            'is_active',
                            true
                        )
                        ->first();


                if ($dateDay) {

                    $requestedDayId =
                        $dateDay->id;


                    $allDaysSelected =
                        false;
                }

            } catch (\Exception $exception) {

                $selectedExportDate =
                    null;
            }
        }


        $context =
            $this->getDayContext(
                $allDaysSelected
                ? null
                : $requestedDayId
            );


        $days =
            $context[
                'days'
            ];


        $selectedDay =
            $allDaysSelected
            ? null
            : $context[
                'selectedDay'
            ];


        $selectedDate =
            $selectedDay
            ? $context[
                'dayDates'
            ][
                $selectedDay->id
            ]
            : null;


        /*
        |--------------------------------------------------------------------------
        | Classes
        |--------------------------------------------------------------------------
        */

        $sections =
            Section::where(
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
                ->orderBy(
                    'section_name'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Sub-sections
        |--------------------------------------------------------------------------
        */

        $subSections =
            SubSection::with(
                'section'
            )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'sub_section_name'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Offerings
        |--------------------------------------------------------------------------
        */

        $offeringQuery =
            SectionOffering::with([
                'section',
                'day',
                'subSections',
            ])
                ->where(
                    'is_active',
                    true
                );


        if ($allDaysSelected) {

            $offeringQuery->whereIn(
                'day_id',
                $days
                    ->pluck(
                        'id'
                    )
            );

        } elseif ($selectedDay) {

            $offeringQuery->where(
                'day_id',
                $selectedDay->id
            );
        }


        $offerings =
            $offeringQuery
                ->orderBy(
                    'day_id'
                )
                ->orderBy(
                    'start_time'
                )
                ->orderBy(
                    'section_id'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Times
        |--------------------------------------------------------------------------
        */

        $times =
            $offerings
                ->map(
                    function ($offering) {

                        return
                            Carbon::parse(
                                $offering
                                    ->start_time
                            )->format(
                                    'H:i:s'
                                );
                    }
                )
                ->unique()
                ->sort()
                ->values();


        $selectedOfferingId =
            $allDaysSelected
            ? null
            : $request->input(
                'offering_id'
            );


        return view(
            'admin.schedule.export',
            compact(
                'days',
                'selectedDay',
                'selectedDate',
                'selectedExportDate',
                'allDaysSelected',
                'sections',
                'subSections',
                'offerings',
                'times',
                'selectedOfferingId'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    */

    public function export(
        Request $request,
        ScheduleExcelExporter $excelExporter
    ) {
        $validated =
            $request->validate([
                'date' => [
                    'nullable',
                    'date',
                ],

                'day_id' => [
                    'required',

                    function ($attribute, $value, $fail) {

                        if (
                            $value
                            ===
                            'all'
                        ) {

                            return;
                        }


                        if (
                            !Day::whereKey(
                                $value
                            )->exists()
                        ) {

                            $fail(
                                'The selected day is invalid.'
                            );
                        }
                    },
                ],

                'section_id' => [
                    'nullable',
                    'exists:sections,id',
                ],

                'sub_section_id' => [
                    'nullable',
                    'exists:sub_sections,id',
                ],

                'time' => [
                    'nullable',
                    'date_format:H:i:s',
                ],

                'offering_id' => [
                    'nullable',
                    'exists:section_offerings,id',
                ],

                'attendance_status' => [
                    'required',
                    'in:all,present,absent,vacation,not_marked',
                ],

                'include_attendance_status' => [
                    'nullable',
                    'boolean',
                ],

                'format' => [
                    'required',
                    'in:pdf,excel',
                ],
            ]);


        $validated[
            'include_attendance_status'
        ] =
            $request->boolean(
                'include_attendance_status'
            );


        /*
        |--------------------------------------------------------------------------
        | Historical Attendance Date
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
            $validated[
                'date'
            ]
        )
        ) {

            $historicalDate =
                Carbon::parse(
                    $validated[
                        'date'
                    ]
                )
                    ->startOfDay();


            $historicalDay =
                Day::whereRaw(
                    'LOWER(day_name) = ?',
                    [
                        strtolower(
                            $historicalDate
                                ->format(
                                    'l'
                                )
                        ),
                    ]
                )
                    ->where(
                        'is_active',
                        true
                    )
                    ->first();


            if (!$historicalDay) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'date' =>
                            'No active class day exists for the selected date.',
                    ]);
            }


            $validated[
                'day_id'
            ] =
                $historicalDay->id;
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Sub-section
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
            $validated[
                'sub_section_id'
            ]
        )
            &&
            !empty(
            $validated[
                'section_id'
            ]
        )
        ) {

            $validSubSection =
                SubSection::where(
                    'id',
                    $validated[
                        'sub_section_id'
                    ]
                )
                    ->where(
                        'section_id',
                        $validated[
                            'section_id'
                        ]
                    )
                    ->exists();


            if (!$validSubSection) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'sub_section_id' =>
                            'The selected sub-section does not belong to the selected class.',
                    ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | All Days Export
        |--------------------------------------------------------------------------
        */

        if (
            $validated[
                'day_id'
            ]
            ===
            'all'
        ) {

            /*
             * A Specific Class belongs to one day only,
             * so it is ignored for an All Days export.
             */
            $validated[
                'offering_id'
            ] =
                null;


            $workingDays =
                Day::where(
                    'is_active',
                    true
                )
                    ->whereIn(
                        'id',
                        SectionOffering::query()
                            ->where(
                                'is_active',
                                true
                            )
                            ->select(
                                'day_id'
                            )
                            ->distinct()
                    )
                    ->orderBy(
                        'sort_order'
                    )
                    ->get();


            $daySchedules =
                collect();


            foreach (
                $workingDays
                as $day
            ) {

                $date =
                    $this->resolveDateForDay(
                        $day
                    );


                $dayFilters =
                    $validated;


                $dayFilters[
                    'day_id'
                ] =
                    $day->id;


                $dayFilters[
                    'date'
                ] =
                    $date
                        ->toDateString();


                $dayRows =
                    $this->buildExportRows(
                        $dayFilters
                    );


                /*
                 * Do not add a completely empty day.
                 */
                if (
                    $dayRows
                        ->isEmpty()
                ) {

                    continue;
                }


                $daySchedules->push([
                    'day' =>
                        $day,

                    'date' =>
                        $date,

                    'rows' =>
                        $dayRows,

                    'timeGroups' =>
                        $dayRows
                            ->groupBy(
                                'start_time'
                            ),
                ]);
            }


            $fileName =
                'kumon-class-list-all-days-'
                .
                now()->format(
                    'Y-m-d'
                );


            /*
            |--------------------------------------------------------------------------
            | Excel - one worksheet per day
            |--------------------------------------------------------------------------
            */

            if (
                $validated[
                    'format'
                ]
                ===
                'excel'
            ) {

                return $excelExporter
                    ->downloadMultiDay(
                        $daySchedules,
                        $fileName
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | PDF - each day starts on a new page
            |--------------------------------------------------------------------------
            */

            $pdf =
                Pdf::loadView(
                    'admin.schedule.export-pdf',
                    [
                        'multiDay' =>
                            true,

                        'daySchedules' =>
                            $daySchedules,

                        'filters' =>
                            $validated,
                    ]
                )
                    ->setPaper(
                        'a4',
                        'landscape'
                    );


            return $pdf->download(
                $fileName
                .
                '.pdf'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Single Day Export
        |--------------------------------------------------------------------------
        */

        $selectedDay =
            Day::findOrFail(
                $validated[
                    'day_id'
                ]
            );


        $date =
            !empty(
            $validated[
                'date'
            ]
        )
            ? Carbon::parse(
                $validated[
                    'date'
                ]
            )
                ->startOfDay()

            : $this->resolveDateForDay(
                $selectedDay
            );


        $validated[
            'date'
        ] =
            $date
                ->toDateString();


        $rows =
            $this->buildExportRows(
                $validated
            );


        $fileName =
            'kumon-class-list-'
            .
            $date->format(
                'Y-m-d'
            );


        if (
            $validated[
                'format'
            ]
            ===
            'excel'
        ) {

            return $excelExporter
                ->download(
                    $rows,
                    $date,
                    $fileName
                );
        }


        $timeGroups =
            $rows
                ->groupBy(
                    'start_time'
                );


        $pdf =
            Pdf::loadView(
                'admin.schedule.export-pdf',
                [
                    'multiDay' =>
                        false,

                    'rows' =>
                        $rows,

                    'timeGroups' =>
                        $timeGroups,

                    'date' =>
                        $date,

                    'filters' =>
                        $validated,
                ]
            )
                ->setPaper(
                    'a4',
                    'landscape'
                );


        return $pdf->download(
            $fileName
            .
            '.pdf'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Build Export Rows
    |--------------------------------------------------------------------------
    */

    private function buildExportRows(
        array $filters
    ) {
        $date =
            Carbon::parse(
                $filters[
                    'date'
                ]
            )
                ->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | Offering Query
        |--------------------------------------------------------------------------
        */

        $offeringQuery =
            SectionOffering::with([
                'section',
                'day',
                'subSections',
            ])
                ->where(
                    'day_id',
                    $filters[
                        'day_id'
                    ]
                )
                ->where(
                    'is_active',
                    true
                );


        /*
        |--------------------------------------------------------------------------
        | Class Filter
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
            $filters[
                'section_id'
            ]
        )
        ) {

            $offeringQuery->where(
                'section_id',
                $filters[
                    'section_id'
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Time Filter
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
            $filters[
                'time'
            ]
        )
        ) {

            $offeringQuery
                ->whereTime(
                    'start_time',
                    $filters[
                        'time'
                    ]
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Specific Offering
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
            $filters[
                'offering_id'
            ]
        )
        ) {

            $offeringQuery->where(
                'id',
                $filters[
                    'offering_id'
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Sub-section Filter
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
            $filters[
                'sub_section_id'
            ]
        )
        ) {

            $offeringQuery
                ->whereHas(
                    'subSections',
                    function ($query) use ($filters) {

                        $query->where(
                            'sub_sections.id',
                            $filters[
                                'sub_section_id'
                            ]
                        );
                    }
                );
        }


        $offerings =
            $offeringQuery
                ->orderBy(
                    'start_time'
                )
                ->orderBy(
                    'section_id'
                )
                ->get();


        $offeringIds =
            $offerings
                ->pluck(
                    'id'
                );


        if (
            $offeringIds
                ->isEmpty()
        ) {

            return collect();
        }


        /*
        |--------------------------------------------------------------------------
        | Enrolments
        |--------------------------------------------------------------------------
        */

        $enrolmentsQuery =
            Enrolment::with([
                'subSection',
                'student.studentStatus',
                'sectionOffering.section',
                'sectionOffering.day',
            ])
                ->whereIn(
                    'section_offering_id',
                    $offeringIds
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'is_wishlist',
                    false
                );


        if (
            !empty(
            $filters[
                'sub_section_id'
            ]
        )
        ) {

            $enrolmentsQuery->where(
                'sub_section_id',
                $filters[
                    'sub_section_id'
                ]
            );
        }


        $enrolments =
            $enrolmentsQuery
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Attendance Records
        |--------------------------------------------------------------------------
        */

        $attendanceRecords =
            Attendance::whereIn(
                'enrolment_id',
                $enrolments
                    ->pluck(
                        'id'
                    )
            )
                ->whereDate(
                    'attendance_date',
                    $date
                        ->toDateString()
                )
                ->get()
                ->keyBy(
                    'enrolment_id'
                );


        /*
        |--------------------------------------------------------------------------
        | Vacation Students
        |--------------------------------------------------------------------------
        */

        $studentIds =
            $enrolments
                ->pluck(
                    'student_id'
                )
                ->unique()
                ->values();


        $vacationStudentIds =
            collect();


        if (
            $studentIds
                ->isNotEmpty()
        ) {

            $vacationStudentIds =
                StudentLeave::activeOnDate(
                    $date
                )
                    ->whereIn(
                        'student_id',
                        $studentIds
                    )
                    ->pluck(
                        'student_id'
                    )
                    ->map(
                        fn($id) =>
                            (int)
                            $id
                    )
                    ->unique()
                    ->values();
        }


        /*
        |--------------------------------------------------------------------------
        | Build Rows
        |--------------------------------------------------------------------------
        */

        $rows =
            collect();


        foreach (
            $enrolments
            as $enrolment
        ) {

            $student =
                $enrolment
                    ->student;


            $offering =
                $enrolment
                    ->sectionOffering;


            if (
                !$student
                ||
                !$offering
            ) {

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Vacation Override
            |--------------------------------------------------------------------------
            */

            $isVacation =
                $vacationStudentIds
                    ->contains(
                        (int)
                        $student->id
                    );


            $attendance =
                $attendanceRecords
                    ->get(
                        $enrolment->id
                    );


            if ($isVacation) {

                $attendanceStatus =
                    'vacation';


                $displayStudentStatus =
                    StudentLeave::VACATION_LABEL;


                $statusColour =
                    StudentLeave::VACATION_COLOR;

            } else {

                $attendanceStatus =
                    $attendance
                            ?->status
                    ??
                    'not_marked';


                $displayStudentStatus =
                    $student
                        ->studentStatus
                            ?->status_name
                    ??
                    'Active';


                $statusColour =
                    $student
                        ->studentStatus
                            ?->color_code
                    ??
                    null;
            }


            /*
            |--------------------------------------------------------------------------
            | Student Filter
            |--------------------------------------------------------------------------
            */

            if (
                $filters[
                    'attendance_status'
                ]
                !==
                'all'
                &&
                $attendanceStatus
                !==
                $filters[
                    'attendance_status'
                ]
            ) {

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Status Fill
            |--------------------------------------------------------------------------
            |
            | Active student = no colour.
            |--------------------------------------------------------------------------
            */

            $statusFill =
                null;


            /*
            |--------------------------------------------------------------------------
            | Export Highlight Priority
            |--------------------------------------------------------------------------
            |
            | Vacation = grey
            | Absent   = red
            | Otherwise keep the existing student-status colour behaviour.
            |--------------------------------------------------------------------------
            */

            if ($isVacation) {

                $statusFill =
                    '#9ca3af';

            } elseif (
                $attendanceStatus
                ===
                'absent'
            ) {

                $statusFill =
                    '#dc2626';

            } elseif (
                strtolower(
                    trim(
                        $displayStudentStatus
                    )
                )
                !==
                'active'
            ) {

                if (
                    $statusColour
                    &&
                    preg_match(
                        '/^#[0-9A-Fa-f]{6}$/',
                        $statusColour
                    )
                ) {

                    $statusFill =
                        $statusColour;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Subject
            |--------------------------------------------------------------------------
            */

            $sectionName =
                $offering
                    ->section
                        ?->section_name
                ??
                'Class';


            $subSectionName =
                $enrolment
                    ->subSection
                        ?->sub_section_name;


            $classDisplay =
                $sectionName;


            if ($subSectionName) {

                $classDisplay .=
                    ' - '
                    .
                    $subSectionName;
            }


            /*
            |--------------------------------------------------------------------------
            | Student Display Name
            |--------------------------------------------------------------------------
            */

            $studentName = trim(
                $student->first_name . ' ' . $student->last_name
            );

            $studentNickname = trim(
                (string) ($student->nickname ?? '')
            );

            if ($studentNickname !== '') {
                $studentName .= ' (' . $studentNickname . ')';
            }


            /*
            |--------------------------------------------------------------------------
            | Interactive Subject Beside Student Name
            |--------------------------------------------------------------------------
            |
            | Interactive has one shared capacity of five seats. Each enrolled
            | student is labelled as either Math or English on printed/exported
            | schedules so staff can see the subject beside the student's name.
            |--------------------------------------------------------------------------
            */

            if (
                strtolower(
                    trim(
                        $sectionName
                    )
                )
                ===
                'interactive'
                &&
                $subSectionName
            ) {

                $studentName .=
                    ' — '
                    .
                    $subSectionName;
            }


            if (
                !empty(
                $filters[
                    'include_attendance_status'
                ]
            )
            ) {

                $attendanceLabel =
                    match (
                    $attendanceStatus
                    ) {
                        'present' =>
                            'Present',

                        'absent' =>
                            'Absent',

                        'vacation' =>
                            'Vacation',

                        default =>
                            'Not Marked',
                    };


                $studentName .=
                    ' — '
                    .
                    $attendanceLabel;
            }


            /*
            |--------------------------------------------------------------------------
            | Row
            |--------------------------------------------------------------------------
            */

            $rows->push([
                'student_name' =>
                    $studentName,

                'day' =>
                    $offering
                        ->day
                            ?->day_name
                    ??
                    $date->format(
                        'l'
                    ),

                'start_time' =>
                    Carbon::parse(
                        $offering
                            ->start_time
                    )->format(
                            'g:i A'
                        ),

                'start_time_sort' =>
                    Carbon::parse(
                        $offering
                            ->start_time
                    )->format(
                            'H:i:s'
                        ),

                'section' =>
                    $sectionName,

                'sub_section' =>
                    $subSectionName,

                'class_display' =>
                    $classDisplay,

                'student_status' =>
                    $displayStudentStatus,

                'status_fill' =>
                    $statusFill,

                'attendance_status' =>
                    $attendanceStatus,
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Sort
        |--------------------------------------------------------------------------
        |
        | Time
        | → Subject
        | → Student
        |--------------------------------------------------------------------------
        */

        return $rows
            ->sortBy(
                function ($row) {

                    return
                        $row[
                            'start_time_sort'
                        ]
                        .
                        '|'
                        .
                        strtolower(
                            $row[
                                'class_display'
                            ]
                        )
                        .
                        '|'
                        .
                        strtolower(
                            $row[
                                'student_name'
                            ]
                        );
                }
            )
            ->values();
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Next Date For A Working Day
    |--------------------------------------------------------------------------
    */

    private function resolveDateForDay(
        Day $day
    ): Carbon {

        $today =
            now()
                ->startOfDay();


        if (
            strtolower(
                $day
                    ->day_name
            )
            ===
            strtolower(
                $today->format(
                    'l'
                )
            )
        ) {

            return
                $today
                    ->copy();
        }


        return
            $today
                ->copy()
                ->next(
                    $day
                        ->day_name
                );
    }


    /*
    |--------------------------------------------------------------------------
    | Print Day
    |--------------------------------------------------------------------------
    */

    public function printDay(
        Request $request
    ) {
        return redirect()
            ->route(
                'admin.schedule.export.form',
                [
                    'day_id' =>
                        $request->input(
                            'day_id'
                        ),
                ]
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Print Class
    |--------------------------------------------------------------------------
    */

    public function printClass(
        SectionOffering $sectionOffering
    ) {
        return redirect()
            ->route(
                'admin.schedule.export.form',
                [
                    'day_id' =>
                        $sectionOffering
                            ->day_id,

                    'offering_id' =>
                        $sectionOffering
                            ->id,
                ]
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Day Context
    |--------------------------------------------------------------------------
    */

    private function getDayContext(
        $requestedDayId = null
    ) {
        /*
        |--------------------------------------------------------------------------
        | Only Working Days
        |--------------------------------------------------------------------------
        */

        $days =
            Day::where(
                'is_active',
                true
            )
                ->whereIn(
                    'id',
                    SectionOffering::query()
                        ->where(
                            'is_active',
                            true
                        )
                        ->select(
                            'day_id'
                        )
                        ->distinct()
                )
                ->orderBy(
                    'sort_order'
                )
                ->get();


        $today =
            now()
                ->startOfDay();


        $todayName =
            $today
                ->format(
                    'l'
                );


        $dayDates =
            [];


        /*
        |--------------------------------------------------------------------------
        | Calculate Next Date For Each Working Day
        |--------------------------------------------------------------------------
        */

        foreach (
            $days
            as $day
        ) {

            if (
                strtolower(
                    $day
                        ->day_name
                )
                ===
                strtolower(
                    $todayName
                )
            ) {

                $date =
                    $today
                        ->copy();

            } else {

                $date =
                    $today
                        ->copy()
                        ->next(
                            $day
                                ->day_name
                        );
            }


            $dayDates[
                $day->id
            ] =
                $date;
        }


        /*
        |--------------------------------------------------------------------------
        | Default Day
        |--------------------------------------------------------------------------
        */

        $defaultDay =
            $days
                ->first(
                    function ($day) use ($todayName) {

                        return
                            strtolower(
                                $day
                                    ->day_name
                            )
                            ===
                            strtolower(
                                $todayName
                            );
                    }
                );


        if (
            !$defaultDay
            &&
            $days
                ->isNotEmpty()
        ) {

            $defaultDay =
                $days
                    ->sortBy(
                        function ($day) use ($dayDates) {

                            return
                                $dayDates[
                                    $day->id
                                ]
                                    ->timestamp;
                        }
                    )
                    ->first();
        }


        /*
        |--------------------------------------------------------------------------
        | Selected Day
        |--------------------------------------------------------------------------
        */

        $selectedDay =
            $days
                ->firstWhere(
                    'id',
                    (int)
                    $requestedDayId
                );


        if (!$selectedDay) {

            $selectedDay =
                $defaultDay;
        }


        return [
            'days' =>
                $days,

            'dayDates' =>
                $dayDates,

            'selectedDay' =>
                $selectedDay,
        ];
    }
}
