@extends('layouts.admin')

@section('title', 'Class Student List')
@section('page-title', 'Class Student List')

@section('content')
    @php
        $vacationStudentIds = collect($vacationStudentIds ?? []);

        $isInteractive = strtolower($sectionOffering->section?->section_name ?? '') === 'interactive';

        $isMath = strtolower($sectionOffering->section?->section_name ?? '') === 'math';
    @endphp

    <div class="space-y-6">

        {{-- Back to schedule --}}
        <a href="{{ route('admin.schedule.index', [
            'day_id' => $sectionOffering->day_id,
            'time' => \Carbon\Carbon::parse($sectionOffering->start_time)->format('H:i:s'),
            'offering_id' => $sectionOffering->id,
        ]) }}"
            class="inline-flex items-center gap-2 text-sm
               font-semibold text-blue-600 hover:text-blue-800">
            ← Back to Schedule
        </a>

        {{-- Page header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">
                    {{ $sectionOffering->section?->section_name ?? 'Class' }}
                </h1>

                @if ($subSections->isNotEmpty())
                    <p class="mt-1 text-sm font-semibold text-blue-600">
                        Subsections:
                        {{ $subSections->pluck('sub_section_name')->implode(', ') }}
                    </p>
                @endif

                <p class="mt-2 text-sm text-slate-500">
                    {{ $sectionOffering->day?->day_name }}
                    ·
                    {{ \Carbon\Carbon::parse($sectionOffering->start_time)->format('g:i A') }}
                    –
                    {{ \Carbon\Carbon::parse($sectionOffering->end_time)->format('g:i A') }}
                </p>

                <p class="mt-1 text-xs text-slate-500">
                    Leave status checked for
                    {{ $classDate->format('d M Y') }}
                </p>
            </div>

            @if (auth()->user()->hasPermission('enrolments.print'))
                <a href="{{ route('admin.schedule.class-students.print', $sectionOffering) }}"
                    target="_blank" rel="noopener"
                    class="inline-flex items-center justify-center
                       rounded-xl bg-blue-600 px-5 py-3
                       text-sm font-semibold text-white
                       hover:bg-blue-700">
                    Print Class List
                </a>
            @endif
        </div>

        {{-- Class totals remain unchanged when filtering --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">
                    Confirmed Students
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    {{ $confirmedCount }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">
                    {{ $isMath ? 'Shared Math Capacity' : 'Maximum Seats' }}
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    {{ $sectionOffering->max_seats }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">
                    Waitlist
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    {{ $wishlistCount }}
                </p>
            </div>
        </div>

        {{-- Filter validation errors --}}
        @if ($errors->any())
            <div role="alert"
                class="rounded-xl border border-red-200
                   bg-red-50 px-5 py-4 text-sm text-red-700">
                <p class="font-semibold">
                    Please correct the following:
                </p>

                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Search and subsection filter --}}
        <form method="GET"
            action="{{ route('admin.schedule.class-students', $sectionOffering) }}"
            class="rounded-2xl border border-slate-200
               bg-white p-5 shadow-sm">
            <div class="grid items-end gap-4 md:grid-cols-12">

                <div class="md:col-span-6">
                    <label for="student-search" class="mb-2 block text-sm font-semibold text-slate-700">
                        Search Student
                    </label>

                    <input id="student-search" type="search" name="search" value="{{ old('search', $search) }}"
                        maxlength="150" placeholder="Enter student name or Student ID"
                        class="w-full rounded-xl border-slate-300
                           text-sm focus:border-blue-500
                           focus:ring-blue-500">
                </div>

                <div class="md:col-span-3">
                    <label for="sub-section-filter" class="mb-2 block text-sm font-semibold text-slate-700">
                        Subsection
                    </label>

                    <select id="sub-section-filter" name="sub_section_id"
                        class="w-full rounded-xl border-slate-300
                           text-sm focus:border-blue-500
                           focus:ring-blue-500">
                        <option value="">
                            All Subsections
                        </option>

                        @foreach ($subSections as $subSection)
                            <option value="{{ $subSection->id }}" @selected((string) old('sub_section_id', $selectedSubSectionId) === (string) $subSection->id)>
                                {{ $subSection->sub_section_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap gap-2 md:col-span-3">
                    <button type="submit"
                        class="inline-flex items-center justify-center
                           rounded-xl bg-blue-600 px-5 py-2.5
                           text-sm font-semibold text-white
                           hover:bg-blue-700">
                        Search
                    </button>

                    <a href="{{ route('admin.schedule.class-students', $sectionOffering) }}"
                        class="inline-flex items-center justify-center
                           rounded-xl border border-slate-300
                           bg-white px-5 py-2.5 text-sm
                           font-semibold text-slate-700
                           hover:bg-slate-50">
                        Clear
                    </a>
                </div>
            </div>
        </form>

        {{-- Filtered results count --}}
        <p class="text-sm text-slate-500" role="status">
            {{ $enrolments->total() }}
            matching
            {{ $enrolments->total() === 1 ? 'allocation' : 'allocations' }}
            out of {{ $confirmedCount }} confirmed in this class.
        </p>

        {{-- Student table --}}
        <div class="overflow-hidden rounded-2xl border
                border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th scope="col" class="px-5 py-4 text-left">
                                Student
                            </th>

                            <th scope="col" class="px-5 py-4 text-left">
                                Student ID
                            </th>

                            <th scope="col" class="px-5 py-4 text-left">
                                Subsection
                            </th>

                            <th scope="col" class="px-5 py-4 text-left">
                                Guardian
                            </th>

                            <th scope="col" class="px-5 py-4 text-left">
                                Phone
                            </th>

                            <th scope="col" class="px-5 py-4 text-left">
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($enrolments as $enrolment)
                            @php
                                $student = $enrolment->student;

                                $guardians = $student?->guardians ?? collect();

                                $guardian =
                                    $guardians->first(
                                        fn($guardian) => (bool) ($guardian->pivot?->is_primary ?? false),
                                    ) ?? $guardians->first();

                                $studentName = trim(($student?->first_name ?? '') . ' ' . ($student?->last_name ?? ''));

                                $studentNickname = trim((string) ($student?->nickname ?? ''));

                                if ($studentNickname !== '') {
                                    $studentName .= ' (' . $studentNickname . ')';
                                }

                                $isVacation = $student && $vacationStudentIds->contains((int) $student->id);

                                $displayStatus = $isVacation
                                    ? \App\Models\Admin\StudentLeave::VACATION_LABEL
                                    : $student?->studentStatus?->status_name ?? '—';

                                $statusColour = $isVacation
                                    ? \App\Models\Admin\StudentLeave::VACATION_COLOR
                                    : $student?->studentStatus?->color_code ?? '#64748b';

                                if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $statusColour)) {
                                    $statusColour = '#64748b';
                                }
                            @endphp

                            <tr class="{{ $isVacation ? 'bg-cyan-50' : 'hover:bg-slate-50' }}">

                                <td class="px-5 py-4 font-semibold" style="color: {{ $statusColour }};">
                                    {{ $studentName !== '' ? $studentName : 'Student unavailable' }}

                                    @if ($isInteractive && $enrolment->subSection)
                                        <span class="text-slate-500">
                                            —
                                            {{ $enrolment->subSection->sub_section_name }}
                                        </span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-slate-700">
                                    {{ $student?->external_id ?: '—' }}
                                </td>

                                <td class="px-5 py-4 text-slate-700">
                                    {{ $enrolment->subSection?->sub_section_name ?? '—' }}
                                </td>

                                <td class="px-5 py-4 text-slate-700">
                                    @if ($guardian)
                                        {{ $guardian->first_name }}
                                        {{ $guardian->last_name }}
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-slate-700">
                                    {{ $guardian?->phone ?: '—' }}
                                </td>

                                <td class="px-5 py-4">
                                    @if ($displayStatus !== '—')
                                        <span
                                            class="inline-flex items-center
                                               gap-2 whitespace-nowrap
                                               text-xs font-semibold"
                                            style="color: {{ $statusColour }};">
                                            <span aria-hidden="true" class="h-2 w-2 rounded-full"
                                                style="background-color: {{ $statusColour }};"></span>

                                            {{ $displayStatus }}
                                        </span>

                                        @if ($isVacation)
                                            <p class="mt-1 text-xs text-slate-500">
                                                On Leave
                                            </p>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <p class="font-semibold text-slate-700">
                                        No students matched your filters.
                                    </p>

                                    <p class="mt-2 text-sm text-slate-500">
                                        Try another name, Student ID or subsection.
                                    </p>

                                    <a href="{{ route('admin.schedule.class-students', $sectionOffering) }}"
                                        class="mt-4 inline-flex rounded-lg
                                           bg-blue-600 px-4 py-2
                                           text-sm font-semibold text-white
                                           hover:bg-blue-700">
                                        Clear Filters
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Preserve filters when changing pages --}}
            @if ($enrolments->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $enrolments->appends([
                            'search' => $search,
                            'sub_section_id' => $selectedSubSectionId,
                        ])->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
