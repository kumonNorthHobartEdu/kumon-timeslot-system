@extends('layouts.admin')

@section('title', 'Class Enrolment')

@section('page-title', 'Class Enrolment')


@php

    $oldConfirmed =
        array_map(
            'intval',
            old(
                'confirmed_ids',
                []
            )
        );


    $oldWishlist =
        array_map(
            'intval',
            old(
                'wishlist_ids',
                []
            )
        );


    $oldSubSections =
        old(
            'sub_section_ids',
            []
        );


    /*
    |--------------------------------------------------------------------------
    | Filter Options
    |--------------------------------------------------------------------------
    */

    $availableSections =
        $sectionOfferings
            ->pluck('section')
            ->filter()
            ->unique('id')
            ->sortBy('section_name');


    $availableDays =
        $sectionOfferings
            ->pluck('day')
            ->filter()
            ->unique('id')
            ->sortBy('sort_order');


    $availableTimes =
        $sectionOfferings
            ->map(
                function ($offering) {
                    return [
                        'raw' =>
                            $offering
                                ->start_time,

                        'label' =>
                            \Carbon\Carbon::parse(
                                $offering
                                    ->start_time
                            )->format(
                                'g:i A'
                            ),
                    ];
                }
            )
            ->unique('raw')
            ->sortBy('raw')
            ->values();

@endphp


@section('content')

<div class="mx-auto max-w-7xl">


    {{-- =========================================================
        STEPS
    ========================================================== --}}

    <div
        class="mb-6
               flex flex-wrap
               items-center
               gap-3"
    >

        <a
            href="{{ route(
                'admin.student-registration.student'
            ) }}"
            class="font-semibold
                   text-blue-600"
        >
            ✓ Student Details
        </a>


        <span class="text-gray-400">
            →
        </span>


        <a
            href="{{ route(
                'admin.student-registration.guardians'
            ) }}"
            class="font-semibold
                   text-blue-600"
        >
            ✓ Guardian Details
        </a>


        <span class="text-gray-400">
            →
        </span>


        <span
            class="font-semibold
                   text-blue-700"
        >
            3. Class Enrolment
        </span>

    </div>



    {{-- =========================================================
        BACKEND ERRORS
    ========================================================== --}}

    @if ($errors->any())

        <div
            class="mb-5
                   rounded-xl
                   border
                   border-red-200
                   bg-red-50
                   px-5 py-4"
        >

            <p
                class="font-semibold
                       text-red-700"
            >
                Please check the class selection.
            </p>


            <ul
                class="mt-2
                       list-disc
                       space-y-1
                       pl-5
                       text-sm
                       text-red-600"
            >

                @foreach (
                    $errors->all()
                    as $error
                )

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif



    {{-- =========================================================
        JS ERROR
    ========================================================== --}}

    <div
        id="classSelectionError"
        class="mb-5 hidden
               rounded-xl
               border
               border-red-200
               bg-red-50
               px-5 py-4
               text-sm
               font-semibold
               text-red-700"
    >
    </div>



    {{-- =========================================================
        STUDENT SUMMARY
    ========================================================== --}}

    <div
        class="mb-6
               rounded-2xl
               border
               border-gray-200
               bg-white
               p-6
               shadow-sm"
    >

        <h2
            class="text-xl
                   font-bold
                   text-gray-900"
        >
            Student Registration Summary
        </h2>


        <div
            class="mt-5
                   grid
                   gap-5
                   md:grid-cols-3"
        >

            <div>

                <p
                    class="text-xs
                           font-semibold
                           uppercase
                           tracking-wide
                           text-gray-400"
                >
                    Student
                </p>

                <p
                    class="mt-1
                           font-bold
                           text-gray-900"
                >
                    {{ $studentData['first_name'] }}
                    {{ $studentData['last_name'] }}
                </p>

            </div>


            <div>

                <p
                    class="text-xs
                           font-semibold
                           uppercase
                           tracking-wide
                           text-gray-400"
                >
                    Student ID
                </p>

                <p
                    class="mt-1
                           font-medium
                           text-gray-800"
                >
                    {{ $studentData['external_id'] ?: 'Not added yet' }}
                </p>

            </div>


            <div>

                <p
                    class="text-xs
                           font-semibold
                           uppercase
                           tracking-wide
                           text-gray-400"
                >
                    Guardian Count
                </p>

                <p
                    class="mt-1
                           font-medium
                           text-gray-800"
                >
                    {{
                        count(
                            $guardianData[
                                'new'
                            ]
                            ?? []
                        )
                    }}
                </p>

            </div>

        </div>

    </div>



    <form
        method="POST"
        action="{{ route(
            'admin.student-registration.complete'
        ) }}"
        id="classEnrolmentForm"
    >

        @csrf


        <div
            class="overflow-hidden
                   rounded-2xl
                   border
                   border-gray-200
                   bg-white
                   shadow-sm"
        >


            {{-- =================================================
                HEADER
            ================================================== --}}

            <div
                class="border-b
                       border-gray-200
                       p-6"
            >

                <h2
                    class="text-xl
                           font-bold
                           text-gray-900"
                >
                    Select Classes
                </h2>


                <p
                    class="mt-1
                           text-sm
                           text-gray-500"
                >
                    Select one time for each class.
                    Confirmed enrolments use a seat;
                    Waitlist selections do not.
                </p>


                <div
                    class="mt-4
                           rounded-xl
                           border
                           border-blue-100
                           bg-blue-50
                           px-4 py-3
                           text-sm
                           text-blue-700"
                >
                    <strong>
                        Sub-sections:
                    </strong>

                    choose the required sub-section before
                    selecting Enrol or Waitlist.

                    Math uses 3A, B-D and E+.
                    Interactive uses English and Math.
                </div>

            </div>



            {{-- =================================================
                FILTERS
            ================================================== --}}

            <div
                class="border-b
                       border-gray-200
                       bg-gray-50
                       p-6"
            >

                <div
                    class="grid
                           gap-4
                           md:grid-cols-2
                           xl:grid-cols-5"
                >


                    {{-- Class --}}
                    <div>

                        <label
                            for="classFilter"
                            class="mb-2
                                   block
                                   text-xs
                                   font-bold
                                   uppercase
                                   tracking-wide
                                   text-gray-500"
                        >
                            1. Class
                        </label>


                        <select
                            id="classFilter"
                            class="h-11
                                   w-full
                                   rounded-xl
                                   border-slate-500
                                   bg-white
                                   text-sm"
                        >

                            <option value="">
                                All Classes
                            </option>


                            @foreach (
                                $availableSections
                                as $section
                            )

                                <option
                                    value="{{ $section->id }}"
                                >
                                    {{ $section->section_name }}
                                </option>

                            @endforeach

                        </select>

                    </div>



                    {{-- Sub-section --}}
                    <div>

                        <label
                            for="subSectionFilter"
                            class="mb-2
                                   block
                                   text-xs
                                   font-bold
                                   uppercase
                                   tracking-wide
                                   text-gray-500"
                        >
                            2. Sub-section
                        </label>


                        <select
                            id="subSectionFilter"
                            class="h-11
                                   w-full
                                   rounded-xl
                                   border-slate-500
                                   bg-white
                                   text-sm"
                        >

                            <option value="">
                                All Sub-sections
                            </option>


                            @foreach (
                                $availableSubSections
                                as $subSection
                            )

                                <option
                                    value="{{
                                        $subSection->id
                                    }}"
                                >
                                    {{
                                        $subSection
                                            ->sub_section_name
                                    }}
                                </option>

                            @endforeach

                        </select>

                    </div>



                    {{-- Day --}}
                    <div>

                        <label
                            for="dayFilter"
                            class="mb-2
                                   block
                                   text-xs
                                   font-bold
                                   uppercase
                                   tracking-wide
                                   text-gray-500"
                        >
                            3. Day
                        </label>


                        <select
                            id="dayFilter"
                            class="h-11
                                   w-full
                                   rounded-xl
                                   border-slate-500
                                   bg-white
                                   text-sm"
                        >

                            <option value="">
                                All Days
                            </option>


                            @foreach (
                                $availableDays
                                as $day
                            )

                                <option
                                    value="{{ $day->id }}"
                                >
                                    {{ $day->day_name }}
                                </option>

                            @endforeach

                        </select>

                    </div>



                    {{-- Time --}}
                    <div>

                        <label
                            for="timeFilter"
                            class="mb-2
                                   block
                                   text-xs
                                   font-bold
                                   uppercase
                                   tracking-wide
                                   text-gray-500"
                        >
                            4. Time
                        </label>


                        <select
                            id="timeFilter"
                            class="h-11
                                   w-full
                                   rounded-xl
                                   border-slate-500
                                   bg-white
                                   text-sm"
                        >

                            <option value="">
                                All Times
                            </option>


                            @foreach (
                                $availableTimes
                                as $time
                            )

                                <option
                                    value="{{
                                        $time['raw']
                                    }}"
                                >
                                    {{ $time['label'] }}
                                </option>

                            @endforeach

                        </select>

                    </div>



                    {{-- Clear --}}
                    <div
                        class="flex
                               items-end"
                    >

                        <button
                            type="button"
                            id="clearFilters"
                            class="inline-flex
                                   h-11
                                   w-full
                                   items-center
                                   justify-center
                                   rounded-xl
                                   border
                                   border-slate-500
                                   bg-white
                                   px-5
                                   text-sm
                                   font-semibold
                                   text-gray-700
                                   hover:bg-gray-100"
                        >
                            Clear Filters
                        </button>

                    </div>

                </div>



                <div
                    class="mt-4
                           flex
                           flex-col
                           gap-2
                           sm:flex-row
                           sm:items-center
                           sm:justify-between"
                >

                    <p
                        id="filterResultCount"
                        class="text-xs
                               text-gray-500"
                    >
                        Showing available classes
                    </p>


                    <p
                        id="selectedSummary"
                        class="text-xs
                               font-semibold
                               text-blue-600"
                    >
                        No class selected yet.
                    </p>

                </div>

            </div>



            {{-- =================================================
                TABLE
            ================================================== --}}

            <div class="overflow-x-auto">

                <table
                    class="min-w-full
                           divide-y
                           divide-gray-200"
                >

                    <thead class="bg-gray-50">

                        <tr>

                            <th
                                class="px-5 py-3
                                       text-left
                                       text-xs
                                       font-semibold
                                       uppercase
                                       text-gray-500"
                            >
                                Class
                            </th>


                            <th
                                class="px-5 py-3
                                       text-left
                                       text-xs
                                       font-semibold
                                       uppercase
                                       text-gray-500"
                            >
                                Sub-section
                            </th>


                            <th
                                class="px-5 py-3
                                       text-left
                                       text-xs
                                       font-semibold
                                       uppercase
                                       text-gray-500"
                            >
                                Day
                            </th>


                            <th
                                class="px-5 py-3
                                       text-left
                                       text-xs
                                       font-semibold
                                       uppercase
                                       text-gray-500"
                            >
                                Time
                            </th>


                            <th
                                class="px-5 py-3
                                       text-center
                                       text-xs
                                       font-semibold
                                       uppercase
                                       text-gray-500"
                            >
                                Capacity
                            </th>


                            <th
                                class="px-5 py-3
                                       text-center
                                       text-xs
                                       font-semibold
                                       uppercase
                                       text-gray-500"
                            >
                                Available
                            </th>


                            <th
                                class="px-5 py-3
                                       text-center
                                       text-xs
                                       font-semibold
                                       uppercase
                                       text-gray-500"
                            >
                                Enrol
                            </th>


                            <th
                                class="px-5 py-3
                                       text-center
                                       text-xs
                                       font-semibold
                                       uppercase
                                       text-gray-500"
                            >
                                Waitlist
                            </th>

                        </tr>

                    </thead>


                    <tbody
                        id="classOfferingTable"
                        class="divide-y
                               divide-gray-200"
                    >

                        @forelse (
                            $sectionOfferings
                            as $offering
                        )

                            @php

                                $isFull =
                                    $offering
                                        ->available_seats
                                    <= 0;


                                $subSectionIds =
                                    $offering
                                        ->subSections
                                        ->pluck('id')
                                        ->map(
                                            fn ($id) =>
                                                (int) $id
                                        )
                                        ->implode(',');


                                $oldSelectedSubSection =
                                    $oldSubSections[
                                        $offering->id
                                    ]
                                    ??
                                    '';

                            @endphp


                            <tr
                                class="class-offering-row
                                {{
                                    $isFull
                                        ? 'bg-red-50/40'
                                        : 'hover:bg-gray-50'
                                }}"
                                data-section-id="{{
                                    $offering
                                        ->section_id
                                }}"
                                data-section-name="{{
                                    $offering
                                        ->section
                                        ->section_name
                                }}"
                                data-sub-section-ids="{{
                                    $subSectionIds
                                }}"
                                data-day-id="{{
                                    $offering
                                        ->day_id
                                }}"
                                data-time="{{
                                    $offering
                                        ->start_time
                                }}"
                                data-end-time="{{
                                    $offering
                                        ->end_time
                                }}"
                            >


                                {{-- Class --}}
                                <td
                                    class="whitespace-nowrap
                                           px-5 py-4"
                                >

                                    <p
                                        class="text-sm
                                               font-bold
                                               text-gray-900"
                                    >
                                        {{
                                            $offering
                                                ->section
                                                ->section_name
                                        }}
                                    </p>


                                    @if (
                                        in_array(
                                            strtolower(
                                                $offering
                                                    ->section
                                                    ->section_name
                                            ),
                                            [
                                                'math',
                                                'interactive',
                                            ],
                                            true
                                        )
                                    )

                                        <p
                                            class="mt-1
                                                   text-xs
                                                   text-blue-600"
                                        >
                                            Shared capacity:
                                            {{
                                                $offering
                                                    ->max_seats
                                            }}
                                        </p>

                                    @endif

                                </td>



                                {{-- Subject / Sub-section --}}
                                <td
                                    class="min-w-44
                                           px-5 py-4"
                                >

                                    @if (
                                        $offering
                                            ->subSections
                                            ->isNotEmpty()
                                    )

                                        <select
                                            name="sub_section_ids[{{
                                                $offering->id
                                            }}]"
                                            class="sub-section-select
                                                   h-10
                                                   w-full
                                                   rounded-lg
                                                   border-slate-500
                                                   bg-white
                                                   text-sm
                                                   focus:border-blue-500
                                                   focus:ring-blue-500"
                                            data-offering="{{
                                                $offering
                                                    ->id
                                            }}"
                                        >

                                            <option value="">
                                                {{
                                                    strtolower(
                                                        $offering
                                                            ->section
                                                            ->section_name
                                                    )
                                                    ===
                                                    'interactive'
                                                        ? 'Select subject...'
                                                        : 'Select...'
                                                }}
                                            </option>


                                            @foreach (
                                                $offering
                                                    ->subSections
                                                as $subSection
                                            )

                                                <option
                                                    value="{{
                                                        $subSection
                                                            ->id
                                                    }}"
                                                    @selected(
                                                        (int)
                                                        $oldSelectedSubSection
                                                        ===
                                                        (int)
                                                        $subSection
                                                            ->id
                                                    )
                                                >
                                                    {{
                                                        $subSection
                                                            ->sub_section_name
                                                    }}
                                                </option>

                                            @endforeach

                                        </select>


                                        <p
                                            class="mt-1
                                                   text-xs
                                                   text-gray-400"
                                        >
                                            @if (
                                                strtolower(
                                                    $offering
                                                        ->section
                                                        ->section_name
                                                )
                                                ===
                                                'interactive'
                                            )

                                                Required Interactive subject

                                            @else

                                                Required for this class

                                            @endif
                                        </p>

                                    @else

                                        <span
                                            class="text-sm
                                                   text-gray-400"
                                        >
                                            —
                                        </span>

                                    @endif

                                </td>



                                {{-- Day --}}
                                <td
                                    class="whitespace-nowrap
                                           px-5 py-4
                                           text-sm
                                           font-semibold
                                           text-gray-800"
                                >
                                    {{
                                        $offering
                                            ->day
                                            ->day_name
                                    }}
                                </td>



                                {{-- Time --}}
                                <td
                                    class="whitespace-nowrap
                                           px-5 py-4
                                           text-sm
                                           text-gray-700"
                                >

                                    {{
                                        \Carbon\Carbon::parse(
                                            $offering
                                                ->start_time
                                        )->format(
                                            'g:i A'
                                        )
                                    }}

                                    –

                                    {{
                                        \Carbon\Carbon::parse(
                                            $offering
                                                ->end_time
                                        )->format(
                                            'g:i A'
                                        )
                                    }}

                                </td>



                                {{-- Capacity --}}
                                <td
                                    class="px-5 py-4
                                           text-center"
                                >

                                    <p
                                        class="text-sm
                                               font-semibold
                                               text-gray-800"
                                    >
                                        {{
                                            $offering
                                                ->allocated_seats
                                        }}
                                        /
                                        {{
                                            $offering
                                                ->max_seats
                                        }}
                                    </p>


                                    @if (
                                        strtolower(
                                            $offering
                                                ->section
                                                ->section_name
                                        )
                                        ===
                                        'math'
                                    )

                                        <p
                                            class="mt-1
                                                   text-xs
                                                   text-gray-400"
                                        >
                                            total Math
                                        </p>

                                    @endif

                                </td>



                                {{-- Available --}}
                                <td
                                    class="px-5 py-4
                                           text-center"
                                >

                                    @if ($isFull)

                                        <span
                                            class="inline-flex
                                                   rounded-full
                                                   bg-red-100
                                                   px-3 py-1
                                                   text-xs
                                                   font-semibold
                                                   text-red-700"
                                        >
                                            Full
                                        </span>

                                    @else

                                        <span
                                            class="inline-flex
                                                   rounded-full
                                                   bg-green-100
                                                   px-3 py-1
                                                   text-xs
                                                   font-semibold
                                                   text-green-700"
                                        >
                                            {{
                                                $offering
                                                    ->available_seats
                                            }}
                                        </span>

                                    @endif

                                </td>



                                {{-- Enrol --}}
                                <td
                                    class="px-5 py-4
                                           text-center"
                                >

                                    <input
                                        type="checkbox"
                                        name="confirmed_ids[]"
                                        value="{{
                                            $offering
                                                ->id
                                        }}"
                                        class="class-selection-checkbox
                                               confirmed-checkbox
                                               h-4 w-4
                                               rounded
                                               border-slate-700
                                               text-blue-600"
                                        data-offering="{{
                                            $offering
                                                ->id
                                        }}"
                                        data-section="{{
                                            $offering
                                                ->section_id
                                        }}"
                                        data-section-name="{{
                                            $offering
                                                ->section
                                                ->section_name
                                        }}"
                                        data-day="{{
                                            $offering
                                                ->day_id
                                        }}"
                                        data-day-name="{{
                                            $offering
                                                ->day
                                                ->day_name
                                        }}"
                                        data-start-time="{{
                                            $offering
                                                ->start_time
                                        }}"
                                        data-end-time="{{
                                            $offering
                                                ->end_time
                                        }}"
                                        data-has-sub-sections="{{
                                            $offering
                                                ->subSections
                                                ->isNotEmpty()
                                                ? '1'
                                                : '0'
                                        }}"
                                        @checked(
                                            in_array(
                                                $offering
                                                    ->id,
                                                $oldConfirmed
                                            )
                                        )
                                        @disabled(
                                            $isFull
                                        )
                                    >

                                </td>


                                {{-- Wishlist --}}
                                <td
                                    class="px-5 py-4
                                           text-center"
                                >

                                    <input
                                        type="checkbox"
                                        name="wishlist_ids[]"
                                        value="{{
                                            $offering
                                                ->id
                                        }}"
                                        class="class-selection-checkbox
                                               wishlist-checkbox
                                               h-4 w-4
                                               rounded
                                               border-slate-700
                                               text-purple-600"
                                        data-offering="{{
                                            $offering
                                                ->id
                                        }}"
                                        data-section="{{
                                            $offering
                                                ->section_id
                                        }}"
                                        data-section-name="{{
                                            $offering
                                                ->section
                                                ->section_name
                                        }}"
                                        data-day="{{
                                            $offering
                                                ->day_id
                                        }}"
                                        data-day-name="{{
                                            $offering
                                                ->day
                                                ->day_name
                                        }}"
                                        data-start-time="{{
                                            $offering
                                                ->start_time
                                        }}"
                                        data-end-time="{{
                                            $offering
                                                ->end_time
                                        }}"
                                        data-has-sub-sections="{{
                                            $offering
                                                ->subSections
                                                ->isNotEmpty()
                                                ? '1'
                                                : '0'
                                        }}"
                                        @checked(
                                            in_array(
                                                $offering
                                                    ->id,
                                                $oldWishlist
                                            )
                                        )
                                    >


                                    @if (
                                        $offering
                                            ->wishlist_count
                                        >
                                        0
                                    )

                                        <p
                                            class="mt-1
                                                   text-xs
                                                   text-gray-400"
                                        >
                                            {{
                                                $offering
                                                    ->wishlist_count
                                            }}
                                            waiting
                                        </p>

                                    @endif

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="8"
                                    class="px-6 py-12
                                           text-center
                                           text-gray-500"
                                >
                                    No active classes are available.
                                </td>

                            </tr>

                        @endforelse



                        <tr
                            id="noFilterResults"
                            class="hidden"
                        >

                            <td
                                colspan="8"
                                class="px-6 py-12
                                       text-center
                                       text-sm
                                       text-gray-500"
                            >
                                No classes match
                                the selected filters.
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>



            {{-- =================================================
                FOOTER
            ================================================== --}}

            <div
                class="flex
                       flex-col
                       gap-3
                       border-t
                       border-gray-200
                       bg-gray-50
                       p-6
                       sm:flex-row
                       sm:justify-end"
            >

                <a
                    href="{{ route(
                        'admin.student-registration.guardians'
                    ) }}"
                    class="rounded-xl
                           border
                           border-slate-500
                           bg-white
                           px-5 py-2.5
                           text-center
                           text-sm
                           font-semibold
                           text-gray-700
                           hover:bg-gray-100"
                >
                    ← Back to Guardians
                </a>


                <button
                    type="submit"
                    class="rounded-xl
                           bg-blue-600
                           px-6 py-2.5
                           text-sm
                           font-semibold
                           text-white
                           hover:bg-blue-700"
                >
                    Complete Registration
                </button>

            </div>

        </div>

    </form>

</div>

@endsection



@push('scripts')

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | Elements
        |--------------------------------------------------------------------------
        */

        const allSelections =
            document.querySelectorAll(
                '.class-selection-checkbox'
            );


        const confirmedCheckboxes =
            document.querySelectorAll(
                '.confirmed-checkbox'
            );


        const wishlistCheckboxes =
            document.querySelectorAll(
                '.wishlist-checkbox'
            );


        const subSectionSelects =
            document.querySelectorAll(
                '.sub-section-select'
            );


        const rows =
            document.querySelectorAll(
                '.class-offering-row'
            );


        const classFilter =
            document.getElementById(
                'classFilter'
            );


        const subSectionFilter =
            document.getElementById(
                'subSectionFilter'
            );


        const dayFilter =
            document.getElementById(
                'dayFilter'
            );


        const timeFilter =
            document.getElementById(
                'timeFilter'
            );


        const clearFilters =
            document.getElementById(
                'clearFilters'
            );


        const resultCount =
            document.getElementById(
                'filterResultCount'
            );


        const selectedSummary =
            document.getElementById(
                'selectedSummary'
            );


        const noFilterResults =
            document.getElementById(
                'noFilterResults'
            );


        const selectionError =
            document.getElementById(
                'classSelectionError'
            );


        const form =
            document.getElementById(
                'classEnrolmentForm'
            );



        /*
        |--------------------------------------------------------------------------
        | Error
        |--------------------------------------------------------------------------
        */

        function showError(
            message
        ) {
            selectionError.textContent =
                message;


            selectionError
                .classList
                .remove(
                    'hidden'
                );


            selectionError
                .scrollIntoView({
                    behavior:
                        'smooth',

                    block:
                        'center',
                });
        }


        function clearError()
        {
            selectionError
                .classList
                .add(
                    'hidden'
                );
        }



        /*
        |--------------------------------------------------------------------------
        | Get Sub-section Select
        |--------------------------------------------------------------------------
        */

        function getSubSectionSelect(
            offeringId
        ) {
            return document.querySelector(
                '.sub-section-select'
                +
                '[data-offering="'
                +
                offeringId
                +
                '"]'
            );
        }



        /*
        |--------------------------------------------------------------------------
        | Require Sub-section
        |--------------------------------------------------------------------------
        */

        function ensureSubSection(
            checkbox
        ) {
            if (
                checkbox
                    .dataset
                    .hasSubSections
                !==
                '1'
            ) {
                return true;
            }


            const select =
                getSubSectionSelect(
                    checkbox
                        .dataset
                        .offering
                );


            if (
                !select ||
                !select.value
            ) {
                checkbox.checked =
                    false;


                showError(
                    'Please select a sub-section before choosing Enrol or Waitlist.'
                );


                if (select) {
                    select.focus();
                }


                return false;
            }


            return true;
        }



        /*
        |--------------------------------------------------------------------------
        | Prevent Overlapping Class Times
        |--------------------------------------------------------------------------
        */

        function timeToMinutes(value)
        {
            if (!value) {
                return null;
            }

            const parts = value.split(':');

            return (
                parseInt(parts[0], 10) * 60
                +
                parseInt(parts[1], 10)
            );
        }


        function selectionsOverlap(first, second)
        {
            if (
                first.dataset.day
                !==
                second.dataset.day
            ) {
                return false;
            }

            const firstStart =
                timeToMinutes(
                    first.dataset.startTime
                );

            const firstEnd =
                timeToMinutes(
                    first.dataset.endTime
                );

            const secondStart =
                timeToMinutes(
                    second.dataset.startTime
                );

            const secondEnd =
                timeToMinutes(
                    second.dataset.endTime
                );

            return (
                firstStart < secondEnd
                &&
                secondStart < firstEnd
            );
        }


        function isInteractive(checkbox)
        {
            return (
                checkbox.dataset.sectionName
                ??
                ''
            ).trim().toLowerCase() === 'interactive';
        }


        function enforceNoTimeConflict(checkbox)
        {
            if (!checkbox.checked) {
                return true;
            }

            const conflict =
                Array.from(allSelections)
                    .find(
                        existing =>
                            existing !== checkbox
                            &&
                            existing.checked
                            &&
                            (
                                isInteractive(checkbox)
                                ||
                                isInteractive(existing)
                            )
                            &&
                            selectionsOverlap(
                                checkbox,
                                existing
                            )
                    );

            if (!conflict) {
                return true;
            }

            checkbox.checked = false;

            showError(
                'Schedule conflict: '
                +
                checkbox.dataset.sectionName
                +
                ' overlaps with '
                +
                conflict.dataset.sectionName
                +
                ' on '
                +
                checkbox.dataset.dayName
                +
                '. Please choose a different time.'
            );

            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Same Offering Cannot Be Enrol + Wishlist
        |--------------------------------------------------------------------------
        */

        function clearOpposite(
            checkbox,
            oppositeClass
        ) {
            const opposite =
                document.querySelector(
                    oppositeClass
                    +
                    '[data-offering="'
                    +
                    checkbox
                        .dataset
                        .offering
                    +
                    '"]'
                );


            if (opposite) {
                opposite.checked =
                    false;
            }
        }



        /*
        |--------------------------------------------------------------------------
        | Confirmed
        |--------------------------------------------------------------------------
        */

        confirmedCheckboxes
            .forEach(
                function (
                    checkbox
                ) {
                    checkbox
                        .addEventListener(
                            'change',
                            function () {

                                clearError();


                                if (
                                    !checkbox
                                        .checked
                                ) {
                                    updateSummary();

                                    return;
                                }


                                if (
                                    !ensureSubSection(
                                        checkbox
                                    )
                                ) {
                                    updateSummary();

                                    return;
                                }


                                if (
                                    !enforceNoTimeConflict(
                                        checkbox
                                    )
                                ) {
                                    updateSummary();

                                    return;
                                }


                                clearOpposite(
                                    checkbox,
                                    '.wishlist-checkbox'
                                );


                                updateSummary();
                            }
                        );
                }
            );



        /*
        |--------------------------------------------------------------------------
        | Wishlist
        |--------------------------------------------------------------------------
        */

        wishlistCheckboxes
            .forEach(
                function (
                    checkbox
                ) {
                    checkbox
                        .addEventListener(
                            'change',
                            function () {

                                clearError();


                                if (
                                    !checkbox
                                        .checked
                                ) {
                                    updateSummary();

                                    return;
                                }


                                if (
                                    !ensureSubSection(
                                        checkbox
                                    )
                                ) {
                                    updateSummary();

                                    return;
                                }


                                if (
                                    !enforceNoTimeConflict(
                                        checkbox
                                    )
                                ) {
                                    updateSummary();

                                    return;
                                }


                                clearOpposite(
                                    checkbox,
                                    '.confirmed-checkbox'
                                );


                                updateSummary();
                            }
                        );
                }
            );



        /*
        |--------------------------------------------------------------------------
        | Changing Sub-section
        |--------------------------------------------------------------------------
        */

        subSectionSelects
            .forEach(
                function (
                    select
                ) {
                    select
                        .addEventListener(
                            'change',
                            function () {

                                clearError();


                                /*
                                 * Useful filter behaviour:
                                 * when choosing a row's sub-section,
                                 * automatically set the filter too.
                                 */
                                if (
                                    select.value
                                    &&
                                    subSectionFilter
                                ) {
                                    subSectionFilter.value =
                                        select.value;
                                }


                                updateSummary();
                            }
                        );
                }
            );



        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        function applyFilters()
        {
            const selectedClass =
                classFilter.value;


            const selectedSubSection =
                subSectionFilter.value;


            const selectedDay =
                dayFilter.value;


            const selectedTime =
                timeFilter.value;


            let visible =
                0;


            rows.forEach(
                function (row) {

                    const rowSubSections =
                        row
                            .dataset
                            .subSectionIds
                            .split(',')
                            .filter(
                                value =>
                                    value !==
                                    ''
                            );


                    const classMatches =
                        !selectedClass
                        ||
                        row
                            .dataset
                            .sectionId
                        ===
                        selectedClass;


                    const subSectionMatches =
                        !selectedSubSection
                        ||
                        rowSubSections
                            .includes(
                                selectedSubSection
                            );


                    const dayMatches =
                        !selectedDay
                        ||
                        row
                            .dataset
                            .dayId
                        ===
                        selectedDay;


                    const timeMatches =
                        !selectedTime
                        ||
                        row
                            .dataset
                            .time
                        ===
                        selectedTime;


                    const show =
                        classMatches
                        &&
                        subSectionMatches
                        &&
                        dayMatches
                        &&
                        timeMatches;


                    row.classList.toggle(
                        'hidden',
                        !show
                    );


                    if (show) {
                        visible++;
                    }
                }
            );


            noFilterResults
                .classList
                .toggle(
                    'hidden',
                    visible !== 0
                );


            resultCount.textContent =
                'Showing '
                +
                visible
                +
                (
                    visible === 1
                        ? ' matching class'
                        : ' matching classes'
                );
        }



        /*
        |--------------------------------------------------------------------------
        | Class Filter Improvement
        |--------------------------------------------------------------------------
        |
        | If selected class has no sub-sections,
        | disable the sub-section filter.
        |--------------------------------------------------------------------------
        */

        function updateSubSectionFilterState()
        {
            const selectedClass =
                classFilter.value;


            if (!selectedClass) {
                subSectionFilter.disabled =
                    false;

                return;
            }


            const matchingRows =
                Array.from(
                    rows
                )
                    .filter(
                        row =>
                            row
                                .dataset
                                .sectionId
                            ===
                            selectedClass
                    );


            const hasSubSections =
                matchingRows.some(
                    row =>
                        row
                            .dataset
                            .subSectionIds
                            !==
                            ''
                );


            if (!hasSubSections) {
                subSectionFilter.value =
                    '';

                subSectionFilter.disabled =
                    true;
            } else {
                subSectionFilter.disabled =
                    false;
            }
        }



        classFilter
            .addEventListener(
                'change',
                function () {
                    updateSubSectionFilterState();

                    applyFilters();
                }
            );


        subSectionFilter
            .addEventListener(
                'change',
                applyFilters
            );


        dayFilter
            .addEventListener(
                'change',
                applyFilters
            );


        timeFilter
            .addEventListener(
                'change',
                applyFilters
            );



        /*
        |--------------------------------------------------------------------------
        | Clear Filters
        |--------------------------------------------------------------------------
        */

        clearFilters
            .addEventListener(
                'click',
                function () {

                    classFilter.value =
                        '';

                    subSectionFilter.value =
                        '';

                    subSectionFilter.disabled =
                        false;

                    dayFilter.value =
                        '';

                    timeFilter.value =
                        '';


                    applyFilters();
                }
            );



        /*
        |--------------------------------------------------------------------------
        | Selection Summary
        |--------------------------------------------------------------------------
        */

        function updateSummary()
        {
            const confirmed =
                Array
                    .from(
                        confirmedCheckboxes
                    )
                    .filter(
                        checkbox =>
                            checkbox
                                .checked
                    )
                    .length;


            const wishlist =
                Array
                    .from(
                        wishlistCheckboxes
                    )
                    .filter(
                        checkbox =>
                            checkbox
                                .checked
                    )
                    .length;


            if (
                confirmed === 0
                &&
                wishlist === 0
            ) {
                selectedSummary.textContent =
                    'No class selected yet.';

                return;
            }


            selectedSummary.textContent =
                confirmed
                +
                ' enrolled · '
                +
                wishlist
                +
                ' Waitlist';
        }



        /*
        |--------------------------------------------------------------------------
        | Submit Validation
        |--------------------------------------------------------------------------
        */

        form.addEventListener(
            'submit',
            function (event) {

                clearError();


                for (
                    const checkbox
                    of allSelections
                ) {
                    if (
                        !checkbox
                            .checked
                    ) {
                        continue;
                    }


                    /*
                     * Sub-section required.
                     */
                    if (
                        !ensureSubSection(
                            checkbox
                        )
                    ) {
                        event.preventDefault();

                        return;
                    }
                }
            }
        );



        /*
        |--------------------------------------------------------------------------
        | Initial State
        |--------------------------------------------------------------------------
        */

        updateSubSectionFilterState();

        applyFilters();

        updateSummary();

    }
);

</script>

@endpush
