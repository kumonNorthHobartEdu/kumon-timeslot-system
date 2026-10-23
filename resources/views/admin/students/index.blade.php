@extends('layouts.admin')

@section('title', 'Students')

@section('page-title', 'Students')


@php

    $breadcrumbs = [
        [
            'label' => 'Student Setup',
            'url' => null,
        ],

        [
            'label' => 'Student List',
            'url' => null,
        ],
    ];

@endphp


@section('content')

    <div class="space-y-6">


        {{-- =====================================================
        MESSAGES
    ====================================================== --}}

        @if (session('success'))
            <div
                class="rounded-xl
                   border border-green-200
                   bg-green-50
                   px-5 py-4
                   text-sm
                   text-green-700">
                {{ session('success') }}
            </div>
        @endif


        @if (session('error'))
            <div
                class="rounded-xl
                   border border-red-200
                   bg-red-50
                   px-5 py-4
                   text-sm
                   text-red-700">
                {{ session('error') }}
            </div>
        @endif



        {{-- =====================================================
        HEADER
    ====================================================== --}}

        <div
            class="flex
               flex-col
               gap-5
               lg:flex-row
               lg:items-center
               lg:justify-between">

            <div>

                <h1
                    class="text-3xl
                       font-bold
                       tracking-tight
                       text-cyan-950">
                    Students
                </h1>


                <p class="mt-1
                       text-sm
                       text-slate-500">
                    Search, filter and manage student records
                    and linked guardians.
                </p>

            </div>


            <div class="flex flex-wrap gap-3">


                {{-- =================================================
                GUARDIANS
            ================================================== --}}

                @if (Route::has('admin.guardians.index'))
                    <a href="{{ route('admin.guardians.index') }}"
                        class="inline-flex
                           h-11
                           items-center
                           justify-center
                           rounded-xl
                           border
                           border-cyan-200
                           bg-white
                           px-6
                           text-sm
                           font-semibold
                           text-cyan-800
                           hover:bg-cyan-50">
                        Guardians
                    </a>
                @endif



                {{-- =================================================
                IMPORT STUDENTS
            ================================================== --}}

                @if (auth()->user()->hasPermission('students.create') && auth()->user()->hasPermission('enrolments.create'))
                    <a href="{{ route('admin.student-import.index') }}"
                        class="inline-flex
                           h-11
                           items-center
                           justify-center
                           gap-2
                           rounded-xl
                           border
                           border-violet-200
                           bg-violet-50
                           px-6
                           text-sm
                           font-semibold
                           text-violet-700
                           hover:bg-violet-100">
                        Import Students
                    </a>
                @endif



                {{-- =================================================
                ADD STUDENT
            ================================================== --}}

                @if (auth()->user()->hasPermission('students.create'))
                    <a href="{{ route('admin.student-registration.student') }}"
                        class="inline-flex
                           h-11
                           items-center
                           justify-center
                           gap-2
                           rounded-xl
                           bg-cyan-500
                           px-6
                           text-sm
                           font-semibold
                           text-white
                           hover:bg-cyan-600">
                        + Add Student
                    </a>
                @endif

            </div>

        </div>



        {{-- =====================================================
        FILTERS
    ====================================================== --}}

        <div
            class="rounded-2xl
               border
               border-cyan-200
               bg-white
               p-5
               shadow-sm">

            <form method="GET" action="{{ route('admin.students.index') }}">

                <div
                    class="grid
                       gap-5
                       md:grid-cols-2
                       xl:grid-cols-4">


                    {{-- Student Search --}}

                    <div>

                        <label for="search"
                            class="mb-2
                               block
                               text-xs
                               font-semibold
                               text-slate-600">
                            Student
                        </label>


                        <input id="search" type="text" name="search" value="{{ request('search') }}"
                            placeholder="Student name or ID"
                            class="h-12
                               w-full
                               rounded-xl
                               border-slate-300">

                    </div>



                    {{-- Status --}}

                    <div>

                        <label for="student_status_id"
                            class="mb-2
                               block
                               text-xs
                               font-semibold
                               text-slate-600">
                            Status
                        </label>


                        <select id="student_status_id" name="student_status_id"
                            class="h-12
                               w-full
                               rounded-xl
                               border-slate-300">

                            <option value="">
                                Any Status
                            </option>


                            <option value="vacation" @selected(request('student_status_id') === 'vacation')>
                                Vacation
                            </option>

                            @foreach ($studentStatuses as $studentStatus)
                                @continue(strcasecmp(trim($studentStatus->status_name), 'Vacation') === 0)

                                <option value="{{ $studentStatus->id }}" @selected(request('student_status_id') == $studentStatus->id)>
                                    {{ $studentStatus->status_name }}
                                </option>
                            @endforeach

                        </select>

                    </div>



                    {{-- Guardian --}}

                    <div>

                        <label for="guardian"
                            class="mb-2
                               block
                               text-xs
                               font-semibold
                               text-slate-600">
                            Guardian
                        </label>


                        <input id="guardian" type="text" name="guardian" value="{{ request('guardian') }}"
                            placeholder="Name, phone or email"
                            class="h-12
                               w-full
                               rounded-xl
                               border-slate-300">

                    </div>



                    {{-- Day --}}

                    <div>

                        <label for="day_id"
                            class="mb-2
                               block
                               text-xs
                               font-semibold
                               text-slate-600">
                            Day
                        </label>


                        <select id="day_id" name="day_id"
                            class="h-12
                               w-full
                               rounded-xl
                               border-slate-300">

                            <option value="">
                                Any Day
                            </option>


                            @foreach ($days as $day)
                                <option value="{{ $day->id }}" @selected(request('day_id') == $day->id)>
                                    {{ $day->day_name }}
                                </option>
                            @endforeach

                        </select>

                    </div>



                    {{-- Time --}}

                    <div>

                        <label for="timeslot"
                            class="mb-2
                               block
                               text-xs
                               font-semibold
                               text-slate-600">
                            Class Time
                        </label>


                        <select id="timeslot" name="timeslot"
                            class="h-12
                               w-full
                               rounded-xl
                               border-slate-300">

                            <option value="">
                                Any Time
                            </option>


                            @foreach ($timeslots as $timeslot)
                                <option value="{{ $timeslot }}" @selected(request('timeslot') == $timeslot)>
                                    {{ \Carbon\Carbon::parse($timeslot)->format('g:i A') }}
                                </option>
                            @endforeach

                        </select>

                    </div>



                    {{-- Class --}}

                    <div>

                        <label for="section_id"
                            class="mb-2
                               block
                               text-xs
                               font-semibold
                               text-slate-600">
                            Class
                        </label>


                        <select id="section_id" name="section_id"
                            class="h-12
                               w-full
                               rounded-xl
                               border-slate-300">

                            <option value="">
                                Any Class
                            </option>


                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected(request('section_id') == $section->id)>
                                    {{ $section->section_name }}
                                </option>
                            @endforeach

                        </select>

                    </div>


                    {{-- Subsection --}}
                    <div>
                        <label for="sub_section_id" class="mb-2 block text-xs font-semibold text-slate-600">
                            Subsection
                        </label>
                        <select id="sub_section_id" name="sub_section_id" class="h-12 w-full rounded-xl border-slate-300">
                            <option value="">Any Subsection</option>
                            @foreach ($subSections as $subSection)
                                <option value="{{ $subSection->id }}" data-section-id="{{ $subSection->section_id }}"
                                    @selected(request('sub_section_id') == $subSection->id)>
                                    {{ $subSection->section?->section_name }} ({{ $subSection->sub_section_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Sort Order --}}

                    <div>

                        <label for="sort"
                            class="mb-2
                               block
                               text-xs
                               font-semibold
                               text-slate-600">
                            Sort Students
                        </label>


                        <select id="sort" name="sort"
                            class="h-12
                               w-full
                               rounded-xl
                               border-slate-300">

                            <option value="latest" @selected(request('sort', $sort ?? 'latest') === 'latest')>
                                Latest Added First
                            </option>


                            <option value="earliest" @selected(request('sort', $sort ?? 'latest') === 'earliest')>
                                Earliest Added First
                            </option>

                        </select>

                    </div>

                </div>


                <div
                    class="mt-5
                       flex
                       flex-col
                       gap-3
                       sm:flex-row
                       sm:justify-end">

                    <a href="{{ route('admin.students.index') }}"
                        class="inline-flex
                           h-11
                           items-center
                           justify-center
                           rounded-xl
                           border
                           border-cyan-200
                           px-8
                           font-semibold
                           text-cyan-800">
                        Clear
                    </a>


                    <button type="submit"
                        class="inline-flex
                           h-11
                           items-center
                           justify-center
                           rounded-xl
                           bg-cyan-500
                           px-8
                           font-semibold
                           text-white">
                        Apply Filters
                    </button>

                </div>

            </form>

        </div>



        {{-- =====================================================
        RESULTS
    ====================================================== --}}

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <p class="text-sm text-slate-500">

                {{ $students->total() }}

                {{ $students->total() === 1 ? 'result' : 'results' }}

            </p>


            @if (auth()->user()->hasPermission('students.delete'))
                <button type="button" id="bulkDeleteButton" disabled
                    class="hidden h-10 items-center justify-center gap-2 rounded-xl border border-red-300 bg-red-50 px-4 text-sm font-semibold text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50">
                    Delete Selected
                    <span id="bulkDeleteCount" class="rounded-full bg-red-600 px-2 py-0.5 text-xs text-white">
                        0
                    </span>
                </button>


                <form id="bulkDeleteForm" method="POST" action="{{ route('admin.students.bulk-destroy') }}"
                    class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endif

        </div>



        {{-- =====================================================
        TABLE
    ====================================================== --}}

        <div
            class="overflow-hidden
               rounded-2xl
               border
               border-cyan-200
               bg-white
               shadow-sm">

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead
                        class="border-b
                           border-cyan-200
                           bg-cyan-50/60">

                        <tr>

                            <th class="w-14 px-5 py-4 text-center">

                                @if (auth()->user()->hasPermission('students.delete'))
                                    <input type="checkbox" id="selectAllStudents"
                                        aria-label="Select all students on this page"
                                        class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                                @endif

                            </th>

                            <th
                                class="px-5 py-4
                                   text-left
                                   text-xs
                                   font-semibold
                                   uppercase
                                   text-slate-500">
                                Student
                            </th>


                            <th
                                class="px-5 py-4
                                   text-left
                                   text-xs
                                   font-semibold
                                   uppercase
                                   text-slate-500">
                                Guardian
                            </th>


                            <th
                                class="px-5 py-4
                                   text-left
                                   text-xs
                                   font-semibold
                                   uppercase
                                   text-slate-500">
                                Student ID
                            </th>


                            <th
                                class="px-5 py-4
                                   text-left
                                   text-xs
                                   font-semibold
                                   uppercase
                                   text-slate-500">
                                DOB
                            </th>


                            <th
                                class="px-5 py-4
                                   text-left
                                   text-xs
                                   font-semibold
                                   uppercase
                                   text-slate-500">
                                Status
                            </th>


                            <th
                                class="px-5 py-4
                                   text-left
                                   text-xs
                                   font-semibold
                                   uppercase
                                   text-slate-500">
                                Schedule
                            </th>


                            <th
                                class="px-5 py-4
                                   text-center
                                   text-xs
                                   font-semibold
                                   uppercase
                                   text-slate-500">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-cyan-100">

                        @forelse (
                            $students
                            as $student
                        )

                            @php

                                /*
                            |--------------------------------------------------------------------------
                            | Guardian
                            |--------------------------------------------------------------------------
                            */

                                $primaryGuardian = $student->guardians->first(function ($guardian) {
                                    return (bool) $guardian->pivot->is_primary;
                                });

                                if (!$primaryGuardian) {
                                    $primaryGuardian = $student->guardians->first();
                                }

                                /*
                            |--------------------------------------------------------------------------
                            | Enrolments
                            |--------------------------------------------------------------------------
                            */

                                $confirmedEnrolments = $student->enrolments->filter(function ($enrolment) {
                                    return $enrolment->is_active && !$enrolment->is_wishlist;
                                });

                                $wishlistEnrolments = $student->enrolments->filter(function ($enrolment) {
                                    return $enrolment->is_active && $enrolment->is_wishlist;
                                });

                                $sortedConfirmedEnrolments = $confirmedEnrolments
                                    ->sortBy(function ($enrolment) {
                                        $offering = $enrolment->sectionOffering;

                                        return sprintf(
                                            '%05d-%s',
                                            (int) ($offering?->day_id ?? 99999),
                                            $offering?->start_time ?? '99:99:99',
                                        );
                                    })
                                    ->values();

                                /*
                            |--------------------------------------------------------------------------
                            | Vacation Override
                            |--------------------------------------------------------------------------
                            */

                                $isVacation = $vacationStudentIds->contains((int) $student->id);

                                if ($isVacation) {
                                    $displayStatus = \App\Models\Admin\StudentLeave::VACATION_LABEL;

                                    $statusColour = \App\Models\Admin\StudentLeave::VACATION_COLOR;
                                } else {
                                    $displayStatus = $student->studentStatus?->status_name ?? '—';

                                    $statusColour = $student->studentStatus?->color_code ?? '#64748b';
                                }

                                if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $statusColour)) {
                                    $statusColour = '#64748b';
                                }
                            @endphp


                            <tr
                                class="
                                transition
                                hover:bg-cyan-50/50

                                {{ $isVacation ? 'bg-slate-100/70' : '' }}
                            ">

                                {{-- Select --}}

                                <td class="px-5 py-5 text-center align-top">

                                    @if (auth()->user()->hasPermission('students.delete'))
                                        <input type="checkbox" value="{{ $student->id }}"
                                            data-student-name="{{ $student->first_name }} {{ $student->last_name }}"
                                            data-delete-url="{{ route('admin.students.destroy', $student) }}"
                                            aria-label="Select {{ $student->first_name }} {{ $student->last_name }}"
                                            class="student-select h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                                    @endif

                                </td>

                                {{-- Student --}}

                                <td class="whitespace-nowrap
                                       px-5 py-5">

                                    <p class="font-semibold" style="color: {{ $statusColour }};">
                                        {{ $student->first_name }}
                                        {{ $student->last_name }}

                                        @if (filled($student->nickname))
                                            ({{ $student->nickname }})
                                        @endif
                                    </p>

                                </td>



                                {{-- Guardian --}}

                                <td
                                    class="px-5 py-5
                                       text-sm
                                       text-slate-700">

                                    @if ($primaryGuardian)
                                        <p>
                                            {{ $primaryGuardian->first_name }}

                                            {{ $primaryGuardian->last_name }}
                                        </p>


                                        @if ($student->guardians->count() > 1)
                                            <p
                                                class="mt-1
                                                   text-xs
                                                   text-slate-400">
                                                +
                                                {{ $student->guardians->count() - 1 }}
                                                more
                                            </p>
                                        @endif
                                    @else
                                        —
                                    @endif

                                </td>



                                {{-- Student ID --}}

                                <td
                                    class="px-5 py-5
                                       text-sm
                                       text-slate-700">
                                    {{ $student->external_id }}
                                </td>



                                {{-- DOB --}}

                                <td
                                    class="px-5 py-5
                                       text-sm
                                       text-slate-700">

                                    {{ $student->date_of_birth?->format('d M Y') ?? '—' }}

                                </td>



                                {{-- Status --}}

                                <td class="whitespace-nowrap
                                       px-5 py-5">

                                    @if ($displayStatus !== '—')
                                        <span
                                            class="inline-flex
                                               items-center
                                               gap-2
                                               text-xs
                                               font-semibold
                                               uppercase"
                                            style="
                                            color:
                                            {{ $statusColour }};
                                        ">

                                            <span
                                                class="h-2.5
                                                   w-2.5
                                                   rounded-full"
                                                style="
                                                background-color:
                                                {{ $statusColour }};
                                            "></span>


                                            {{ $displayStatus }}

                                        </span>


                                        @if ($isVacation)
                                            <p class="mt-1
                                                   text-[11px]
                                                   font-medium"
                                                style="
                                                color:
                                                {{ $statusColour }};
                                            ">
                                                On Leave
                                            </p>
                                        @endif
                                    @else
                                        —
                                    @endif

                                </td>



                                {{-- Schedule --}}

                                <td class="min-w-[360px] px-5 py-5 align-top">

                                    @forelse ($sortedConfirmedEnrolments
                                        as $enrolment)
                                        @php

                                            $offering = $enrolment->sectionOffering;

                                        @endphp


                                        @if ($offering)
                                            <div
                                                class="mb-2 flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 last:mb-0">

                                                <div class="min-w-0">

                                                    <p class="truncate text-sm font-semibold text-slate-800">
                                                        {{ $offering->section?->section_name ?? 'Class' }}

                                                        @if ($enrolment->subSection)
                                                            <span class="font-medium text-cyan-700">
                                                                · {{ $enrolment->subSection->sub_section_name }}
                                                            </span>
                                                        @endif
                                                    </p>

                                                </div>

                                                <div class="shrink-0 text-right">

                                                    <p class="text-xs font-bold uppercase tracking-wide text-cyan-700">
                                                        {{ $offering->day?->day_name ?? '—' }}
                                                    </p>

                                                    <p class="mt-0.5 whitespace-nowrap text-xs font-medium text-slate-600">
                                                        {{ \Carbon\Carbon::parse($offering->start_time)->format('g:i A') }}
                                                        –
                                                        {{ \Carbon\Carbon::parse($offering->end_time)->format('g:i A') }}
                                                    </p>

                                                </div>

                                            </div>
                                        @endif

                                    @empty

                                        —
                                    @endforelse


                                    @if ($wishlistEnrolments->count() > 0)
                                        <span
                                            class="mt-2 inline-flex
                                               rounded-full
                                               bg-purple-100
                                               px-2.5 py-1
                                               text-xs
                                               font-semibold
                                               text-purple-700">
                                            {{ $wishlistEnrolments->count() }}
                                            wishlist
                                        </span>
                                    @endif

                                </td>



                                {{-- Actions --}}

                                <td class="px-5 py-5">

                                    <div
                                        class="flex
                                           justify-center
                                           gap-2">

                                        <a href="{{ route('admin.students.show', $student) }}"
                                            class="inline-flex
                                               h-10
                                               items-center
                                               rounded-xl
                                               border
                                               border-cyan-300
                                               bg-cyan-50
                                               px-5
                                               font-semibold
                                               text-cyan-800">
                                            View
                                        </a>


                                        @if (auth()->user()->hasPermission('students.delete'))
                                            <form method="POST"
                                                action="{{ route('admin.students.destroy', $student) }}"
                                                onsubmit="
                                                return confirm(
                                                    'Are you sure you want to delete this student?'
                                                );
                                            ">

                                                @csrf

                                                @method('DELETE')


                                                <button type="submit"
                                                    class="inline-flex
                                                       h-10
                                                       items-center
                                                       rounded-xl
                                                       border
                                                       border-red-300
                                                       bg-red-50
                                                       px-5
                                                       font-semibold
                                                       text-red-700">
                                                    Delete
                                                </button>

                                            </form>
                                        @endif

                                    </div>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td colspan="8"
                                    class="px-6 py-16
                                       text-center
                                       text-slate-500">
                                    No students matched the selected filters.
                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>


            @if ($students->hasPages())
                <div class="border-t
                       border-cyan-100
                       px-6 py-4">
                    {{ $students->links() }}
                </div>
            @endif

        </div>

    </div>

@endsection


@push('scripts')
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {

                const classFilter = document.getElementById('section_id');
                const subsectionFilter = document.getElementById('sub_section_id');

                function updateSubsections() {
                    const sectionId = classFilter?.value || '';

                    Array.from(subsectionFilter?.options || []).forEach(function(option) {
                        if (!option.value) return;

                        const available = !sectionId || option.dataset.sectionId === sectionId;
                        option.disabled = !available;
                        option.hidden = !available;
                    });

                    if (subsectionFilter?.selectedOptions[0]?.disabled) {
                        subsectionFilter.value = '';
                    }
                }

                classFilter?.addEventListener('change', updateSubsections);
                updateSubsections();

                const selectAll =
                    document.getElementById(
                        'selectAllStudents'
                    );

                const studentCheckboxes =
                    Array.from(
                        document.querySelectorAll(
                            '.student-select'
                        )
                    );

                const bulkDeleteButton =
                    document.getElementById(
                        'bulkDeleteButton'
                    );

                const bulkDeleteCount =
                    document.getElementById(
                        'bulkDeleteCount'
                    );

                const bulkDeleteForm =
                    document.getElementById(
                        'bulkDeleteForm'
                    );


                function selectedStudents() {
                    return studentCheckboxes.filter(
                        function(checkbox) {

                            return checkbox.checked;
                        }
                    );
                }


                function updateSelection() {
                    const selected =
                        selectedStudents();


                    studentCheckboxes.forEach(
                        function(checkbox) {

                            const row =
                                checkbox.closest(
                                    'tr'
                                );


                            if (row) {

                                row.classList.toggle(
                                    'bg-red-50/60',
                                    checkbox.checked
                                );
                            }
                        }
                    );


                    if (selectAll) {

                        selectAll.checked =
                            studentCheckboxes.length > 0 &&
                            selected.length === studentCheckboxes.length;

                        selectAll.indeterminate =
                            selected.length > 0 &&
                            selected.length < studentCheckboxes.length;
                    }


                    if (bulkDeleteButton) {

                        bulkDeleteButton.disabled =
                            selected.length === 0;

                        bulkDeleteButton.classList.toggle(
                            'hidden',
                            selected.length === 0
                        );

                        bulkDeleteButton.classList.toggle(
                            'inline-flex',
                            selected.length > 0
                        );
                    }


                    if (bulkDeleteCount) {

                        bulkDeleteCount.textContent =
                            selected.length;
                    }
                }


                selectAll?.addEventListener(
                    'change',
                    function() {

                        studentCheckboxes.forEach(
                            function(checkbox) {

                                checkbox.checked =
                                    selectAll.checked;
                            }
                        );


                        updateSelection();
                    }
                );


                studentCheckboxes.forEach(
                    function(checkbox) {

                        checkbox.addEventListener(
                            'change',
                            updateSelection
                        );
                    }
                );


                bulkDeleteButton?.addEventListener(
                    'click',
                    function() {

                        const selected =
                            selectedStudents();


                        if (selected.length === 0) {

                            return;
                        }


                        const studentNames =
                            selected
                            .map(
                                function(checkbox) {

                                    return checkbox.dataset.studentName;
                                }
                            )
                            .join(', ');


                        const confirmed =
                            window.confirm(
                                'Permanently delete ' +
                                selected.length +
                                (
                                    selected.length === 1 ?
                                    ' selected student?\n\n' :
                                    ' selected students?\n\n'
                                ) +
                                studentNames +
                                '\n\nThis action cannot be undone.'
                            );


                        if (!confirmed) {

                            return;
                        }


                        if (!bulkDeleteForm) {

                            alert(
                                'The bulk delete form is unavailable. Refresh the page and try again.'
                            );

                            return;
                        }


                        bulkDeleteForm
                            .querySelectorAll(
                                'input[name="student_ids[]"]'
                            )
                            .forEach(
                                function(input) {

                                    input.remove();
                                }
                            );


                        selected.forEach(
                            function(checkbox) {

                                const input =
                                    document.createElement(
                                        'input'
                                    );

                                input.type = 'hidden';
                                input.name = 'student_ids[]';
                                input.value = checkbox.value;

                                bulkDeleteForm.appendChild(
                                    input
                                );
                            }
                        );


                        bulkDeleteButton.disabled = true;
                        bulkDeleteButton.textContent =
                            'Deleting selected students...';

                        bulkDeleteForm.submit();
                    }
                );


                updateSelection();
            }
        );
    </script>
@endpush
