@extends('layouts.admin')

@section('title', 'Add Class')

@section('page-title', 'Add Class')

@section('content')

    <div class="space-y-6">

        <div>
            <a href="{{ route('admin.students.show', $student) }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-800">
                ← Back to Student Profile
            </a>
        </div>


        <section
            class="overflow-hidden rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-600 to-cyan-500 p-6 text-white shadow-sm">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-100">
                        Student schedule
                    </p>

                    <h1 class="mt-1 text-2xl font-bold">
                        Add Classes
                    </h1>

                    <p class="mt-1 text-sm text-blue-50">
                        {{ $student->first_name }} {{ $student->last_name }}
                        · Student ID: {{ $student->external_id ?: 'Not added yet' }}
                    </p>

                </div>

                <div class="rounded-xl border border-white/30 bg-white/15 px-4 py-3 backdrop-blur-sm">
                    <p class="text-xs font-medium text-blue-50">How to add a class</p>
                    <p class="mt-1 text-sm font-semibold">Filter → choose subject → select class</p>
                </div>

            </div>

        </section>


        @if ($errors->any())

            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">

                <p class="font-semibold text-red-700">
                    Please check the information below.
                </p>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        @endif


        <form method="POST" action="{{ route('admin.student-enrolments.store', $student) }}" id="addClassesForm">

            @csrf


            @php

                $selectedOfferingIds = collect(old('section_offering_ids', []))->map(fn($id) => (string) $id)->all();

                $oldSubSections = old('sub_section_ids', []);

                $filterSections = $offerings
                    ->map(fn($offering) => $offering->section?->section_name)
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                $filterDays = $offerings->map(fn($offering) => $offering->day?->day_name)->filter()->unique()->values();

                $filterTimes = $offerings
                    ->map(
                        fn($offering) => [
                            'value' => \Carbon\Carbon::parse($offering->start_time)->format('H:i'),

                            'label' => \Carbon\Carbon::parse($offering->start_time)->format('g:i A'),
                        ],
                    )
                    ->unique('value')
                    ->sortBy('value')
                    ->values();
            @endphp


            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div
                    class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <h2 class="text-lg font-bold text-slate-900">
                            Available Classes
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Select one or more classes. If a class has sub-sections,
                            choose the sub-section before selecting the class.
                        </p>

                        <p class="mt-2 text-sm font-medium text-blue-700">
                            Interactive requires either English or Math. Both subjects
                            share the same maximum capacity of 5 students per offering.
                        </p>

                    </div>


                    <div class="flex items-center gap-2">

                        <div id="visibleClassCount"
                            class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">
                            {{ $offerings->count() }} classes
                        </div>

                        <div id="selectedClassCount"
                            class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700">
                            0 selected
                        </div>

                    </div>

                </div>


                <div class="border-b border-slate-200 bg-slate-50/80 px-6 py-5">

                    <div class="flex flex-col gap-3 xl:flex-row xl:items-end">

                        <div class="grid flex-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">

                            <div>
                                <label for="sectionFilter"
                                    class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">
                                    Class
                                </label>
                                <select id="sectionFilter"
                                    class="filter-control h-11 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All classes</option>
                                    @foreach ($filterSections as $sectionName)
                                        <option value="{{ strtolower($sectionName) }}">{{ $sectionName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="dayFilter"
                                    class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">
                                    Day
                                </label>
                                <select id="dayFilter"
                                    class="filter-control h-11 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All days</option>
                                    @foreach ($filterDays as $dayName)
                                        <option value="{{ strtolower($dayName) }}">{{ $dayName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="timeFilter"
                                    class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">
                                    Start time
                                </label>
                                <select id="timeFilter"
                                    class="filter-control h-11 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All start times</option>
                                    @foreach ($filterTimes as $time)
                                        <option value="{{ $time['value'] }}">{{ $time['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="availabilityFilter"
                                    class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">
                                    Availability
                                </label>
                                <select id="availabilityFilter"
                                    class="filter-control h-11 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All availability</option>
                                    <option value="available">Seats available</option>
                                    <option value="full">Full classes</option>
                                </select>
                            </div>

                        </div>

                        <button type="button" id="resetFilters"
                            class="h-11 whitespace-nowrap rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-400 hover:bg-slate-100">
                            Reset filters
                        </button>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead class="sticky top-0 z-10 bg-slate-100">

                            <tr>

                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">
                                    Select
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">
                                    Section
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">
                                    Subject / Sub-section
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">
                                    Day
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">
                                    Time
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">
                                    Seats
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">
                                    Availability
                                </th>

                            </tr>

                        </thead>


                        <tbody id="offeringTableBody" class="divide-y divide-slate-100">

                            @forelse ($offerings as $offering)

                                @php

                                    $availableSeats = max(0, $offering->max_seats - $offering->allocated_seats);

                                    $isFull = $availableSeats <= 0;

                                    $isSelected = in_array((string) $offering->id, $selectedOfferingIds, true);

                                    $oldSubSectionId = $oldSubSections[$offering->id] ?? null;
                                @endphp


                                <tr data-section="{{ strtolower($offering->section?->section_name ?? '') }}"
                                    data-day="{{ strtolower($offering->day?->day_name ?? '') }}"
                                    data-time="{{ \Carbon\Carbon::parse($offering->start_time)->format('H:i') }}"
                                    data-availability="{{ $isFull ? 'full' : 'available' }}"
                                    class="offering-row transition hover:bg-blue-50/50 {{ $isSelected ? 'bg-blue-50/70' : '' }}">

                                    <td class="px-6 py-4">

                                        <input type="checkbox" name="section_offering_ids[]" value="{{ $offering->id }}"
                                            @checked($isSelected) data-offering-id="{{ $offering->id }}"
                                            data-has-sub-sections="{{ $offering->subSections->isNotEmpty() ? '1' : '0' }}"
                                            data-section-id="{{ $offering->section_id }}"
                                            data-day-id="{{ $offering->day_id }}"
                                            data-section-name="{{ $offering->section?->section_name ?? 'Class' }}"
                                            data-day-name="{{ $offering->day?->day_name ?? 'selected day' }}"
                                            class="class-checkbox h-5 w-5 rounded border-2 border-slate-700 text-blue-600 shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-40">

                                    </td>


                                    <td class="px-6 py-4 text-sm font-semibold text-slate-900">

                                        <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-slate-800">
                                            {{ $offering->section?->section_name ?? '—' }}
                                        </span>

                                        @if (in_array(strtolower($offering->section?->section_name ?? ''), ['math', 'interactive'], true))
                                            <p class="mt-1 text-xs font-medium text-blue-600">
                                                Shared capacity:
                                                {{ $offering->max_seats }}
                                            </p>
                                        @endif

                                    </td>


                                    <td class="min-w-44 px-6 py-4">

                                        @if ($offering->subSections->isNotEmpty())
                                            <select name="sub_section_ids[{{ $offering->id }}]"
                                                data-sub-section-for="{{ $offering->id }}"
                                                class="sub-section-select h-10 w-full rounded-lg border-2 border-slate-500 bg-white text-sm focus:border-blue-600 focus:ring-blue-500">

                                                <option value="">
                                                    {{ strtolower($offering->section?->section_name ?? '') === 'interactive' ? 'Select subject...' : 'Select...' }}
                                                </option>


                                                @foreach ($offering->subSections as $subSection)
                                                    <option value="{{ $subSection->id }}" @selected((int) $oldSubSectionId === (int) $subSection->id)>
                                                        {{ $subSection->sub_section_name }}
                                                    </option>
                                                @endforeach

                                            </select>
                                        @else
                                            <span class="text-sm text-slate-400">
                                                —
                                            </span>
                                        @endif

                                    </td>


                                    <td class="px-6 py-4 text-sm font-medium text-slate-700">

                                        {{ $offering->day?->day_name ?? '—' }}

                                    </td>


                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">

                                        {{ \Carbon\Carbon::parse($offering->start_time)->format('g:i A') }}
                                        –
                                        {{ \Carbon\Carbon::parse($offering->end_time)->format('g:i A') }}

                                    </td>


                                    <td class="px-6 py-4 text-sm text-slate-700">

                                        {{ $offering->allocated_seats }}
                                        /
                                        {{ $offering->max_seats }}

                                    </td>


                                    <td class="px-6 py-4">

                                        @if ($isFull)
                                            <span
                                                class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                                Full
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                {{ $availableSeats }} available
                                            </span>
                                        @endif

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td colspan="7" class="px-6 py-14 text-center text-sm text-slate-500">
                                        No additional classes are currently available.
                                    </td>

                                </tr>
                            @endforelse

                            <tr id="filterEmptyRow" class="hidden">
                                <td colspan="7" class="px-6 py-14 text-center">
                                    <div
                                        class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-400">
                                        ⌕</div>
                                    <p class="mt-3 text-sm font-semibold text-slate-700">No classes match these filters.
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">Change a filter or reset them to see all
                                        classes.</p>
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </section>


            @if ($offerings->count() > 0)
                <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h2 class="text-lg font-bold text-slate-900">
                        Enrolment Type
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        This choice applies to all selected classes.
                    </p>


                    <div class="mt-5 grid gap-4 md:grid-cols-2">

                        <label
                            class="cursor-pointer rounded-xl border-2 border-slate-500 p-5 transition hover:border-blue-600 hover:bg-blue-50">

                            <div class="flex items-start gap-3">

                                <input type="radio" name="enrolment_type" value="confirmed"
                                    class="mt-1 h-5 w-5 border-2 border-slate-700 text-blue-600 focus:ring-2 focus:ring-blue-500"
                                    @checked(old('enrolment_type', 'confirmed') === 'confirmed') required>

                                <div>

                                    <p class="font-semibold text-slate-900">
                                        Enrol Student
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        All selected classes receive confirmed seats.
                                    </p>

                                </div>

                            </div>

                        </label>


                        <label
                            class="cursor-pointer rounded-xl border-2 border-slate-500 p-5 transition hover:border-purple-600 hover:bg-purple-50">

                            <div class="flex items-start gap-3">

                                <input type="radio" name="enrolment_type" value="wishlist"
                                    class="mt-1 h-5 w-5 border-2 border-slate-700 text-purple-600 focus:ring-2 focus:ring-purple-500"
                                    @checked(old('enrolment_type') === 'wishlist') required>

                                <div>

                                    <p class="font-semibold text-purple-800">
                                        Add to Waitlist
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        All selected classes are added as waitlist requests.
                                    </p>

                                </div>

                            </div>

                        </label>

                    </div>

                </section>


                <div
                    class="sticky bottom-0 z-20 mt-6 flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">

                    <p class="hidden text-sm text-slate-500 sm:block">
                        Select one or more classes before continuing.
                    </p>

                    <div class="ml-auto flex gap-3">

                        <a href="{{ route('admin.students.show', $student) }}"
                            class="rounded-xl border-2 border-slate-400 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </a>

                        <button type="submit" id="submitClassesButton"
                            class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                            Add Selected Classes
                        </button>

                    </div>

                </div>
            @endif

        </form>

    </div>

@endsection


@push('scripts')
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {

                const form =
                    document.getElementById(
                        'addClassesForm'
                    );


                const checkboxes =
                    document.querySelectorAll(
                        '.class-checkbox'
                    );


                const counter =
                    document.getElementById(
                        'selectedClassCount'
                    );


                const visibleCounter =
                    document.getElementById(
                        'visibleClassCount'
                    );


                const submitButton =
                    document.getElementById(
                        'submitClassesButton'
                    );


                const filterControls =
                    document.querySelectorAll(
                        '.filter-control'
                    );


                const offeringRows =
                    document.querySelectorAll(
                        '.offering-row'
                    );


                const emptyFilterRow =
                    document.getElementById(
                        'filterEmptyRow'
                    );


                function getSubSectionSelect(
                    offeringId
                ) {

                    return document.querySelector(
                        '[data-sub-section-for="' +
                        offeringId +
                        '"]'
                    );
                }


                function isConfirmedEnrolment() {
                    return (
                        document.querySelector(
                            'input[name="enrolment_type"]:checked'
                        )?.value ??
                        'confirmed'
                    ) === 'confirmed';
                }


                function getSelectedSubSectionId(
                    checkbox
                ) {
                    return getSubSectionSelect(
                        checkbox.dataset.offeringId
                    )?.value ?? '';
                }


                function getClassLabel(checkbox) {
                    const select = getSubSectionSelect(
                        checkbox.dataset.offeringId
                    );

                    const subSectionName =
                        select?.selectedOptions[0]?.textContent?.trim();

                    return checkbox.dataset.sectionName +
                        (
                            subSectionName && select.value ?
                            ' - ' + subSectionName :
                            ''
                        );
                }


                function findSameClassDaySubSection(
                    checkbox
                ) {
                    if (
                        !checkbox.checked ||
                        !isConfirmedEnrolment()
                    ) {

                        return null;
                    }


                    return Array
                        .from(
                            checkboxes
                        )
                        .find(
                            function(otherCheckbox) {

                                return
                                otherCheckbox !== checkbox &&
                                    otherCheckbox.checked &&
                                    otherCheckbox.dataset.sectionId ===
                                    checkbox.dataset.sectionId &&
                                    otherCheckbox.dataset.dayId ===
                                    checkbox.dataset.dayId &&
                                    getSelectedSubSectionId(otherCheckbox) ===
                                    getSelectedSubSectionId(checkbox);
                            }
                        ) ??
                        null;
                }

                function updateSelectedCount() {
                    if (!counter) {

                        return;
                    }


                    const selectedCount =
                        document.querySelectorAll(
                            '.class-checkbox:checked'
                        ).length;


                    counter.textContent =
                        selectedCount +
                        ' selected';


                    if (submitButton) {

                        submitButton.disabled =
                            selectedCount ===
                            0;
                    }


                    checkboxes.forEach(
                        function(checkbox) {

                            const row =
                                checkbox.closest(
                                    '.offering-row'
                                );


                            if (row) {

                                row.classList.toggle(
                                    'bg-blue-50/70',
                                    checkbox.checked
                                );
                            }
                        }
                    );
                }


                function applyFilters() {
                    const section =
                        document.getElementById(
                            'sectionFilter'
                        )?.value ??
                        '';

                    const day =
                        document.getElementById(
                            'dayFilter'
                        )?.value ??
                        '';

                    const time =
                        document.getElementById(
                            'timeFilter'
                        )?.value ??
                        '';

                    const availability =
                        document.getElementById(
                            'availabilityFilter'
                        )?.value ??
                        '';

                    let visibleCount = 0;


                    offeringRows.forEach(
                        function(row) {

                            const matches =
                                (!section || row.dataset.section === section) &&
                                (!day || row.dataset.day === day) &&
                                (!time || row.dataset.time === time) &&
                                (
                                    !availability ||
                                    row.dataset.availability === availability
                                );


                            row.classList.toggle(
                                'hidden',
                                !matches
                            );


                            if (matches) {

                                visibleCount++;
                            }
                        }
                    );


                    if (visibleCounter) {

                        visibleCounter.textContent =
                            visibleCount +
                            (
                                visibleCount === 1 ?
                                ' class' :
                                ' classes'
                            );
                    }


                    if (emptyFilterRow) {

                        emptyFilterRow.classList.toggle(
                            'hidden',
                            visibleCount !== 0
                        );
                    }
                }


                checkboxes.forEach(
                    function(checkbox) {

                        checkbox.addEventListener(
                            'change',
                            function() {

                                if (
                                    checkbox.checked &&
                                    checkbox.dataset.hasSubSections ===
                                    '1'
                                ) {

                                    const select =
                                        getSubSectionSelect(
                                            checkbox.dataset.offeringId
                                        );


                                    if (
                                        select &&
                                        !select.value
                                    ) {

                                        checkbox.checked =
                                            false;


                                        alert(
                                            'Please select a sub-section before selecting this class.'
                                        );


                                        select.focus();
                                    }
                                }


                                const sameClassDaySubSection =
                                    findSameClassDaySubSection(
                                        checkbox
                                    );


                                if (sameClassDaySubSection) {

                                    checkbox.checked = false;

                                    alert(
                                        getClassLabel(checkbox) +
                                        ' can only be selected once on ' +
                                        checkbox.dataset.dayName +
                                        '. Choose another day, time, or sub-section.'
                                    );
                                }


                                updateSelectedCount();
                            }
                        );
                    }
                );


                filterControls.forEach(
                    function(control) {

                        control.addEventListener(
                            'change',
                            applyFilters
                        );
                    }
                );


                document
                    .getElementById(
                        'resetFilters'
                    )
                    ?.addEventListener(
                        'click',
                        function() {

                            filterControls.forEach(
                                function(control) {

                                    control.value = '';
                                }
                            );


                            applyFilters();
                        }
                    );


                if (form) {

                    form.addEventListener(
                        'submit',
                        function(event) {

                            const selected =
                                Array.from(
                                    document.querySelectorAll(
                                        '.class-checkbox:checked'
                                    )
                                );


                            if (
                                selected.length ===
                                0
                            ) {

                                event.preventDefault();

                                alert(
                                    'Please select at least one class.'
                                );

                                return;
                            }


                            for (
                                const checkbox of selected
                            ) {

                                if (
                                    checkbox.dataset.hasSubSections ===
                                    '1'
                                ) {

                                    const select =
                                        getSubSectionSelect(
                                            checkbox.dataset.offeringId
                                        );


                                    if (
                                        !select ||
                                        !select.value
                                    ) {

                                        event.preventDefault();

                                        alert(
                                            'Please select a sub-section for every selected class that requires one.'
                                        );


                                        if (select) {

                                            select.focus();
                                        }


                                        return;
                                    }
                                }
                            }


                            if (isConfirmedEnrolment()) {

                                const selectedClassDays =
                                    new Set();


                                for (
                                    const checkbox of selected
                                ) {

                                    const classDayKey =
                                        checkbox.dataset.sectionId +
                                        ':' +
                                        checkbox.dataset.dayId +
                                        ':' +
                                        getSelectedSubSectionId(checkbox);


                                    if (
                                        selectedClassDays.has(
                                            classDayKey
                                        )
                                    ) {

                                        event.preventDefault();

                                        alert(
                                            getClassLabel(checkbox) +
                                            ' can only be selected once on ' +
                                            checkbox.dataset.dayName +
                                            '. Choose another day, time, or sub-section.'
                                        );

                                        checkbox.focus();

                                        return;
                                    }


                                    selectedClassDays.add(
                                        classDayKey
                                    );
                                }
                            }
                        }
                    );
                }


                updateSelectedCount();
                applyFilters();
            }
        );
    </script>
@endpush
