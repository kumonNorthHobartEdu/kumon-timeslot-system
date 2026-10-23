@extends('layouts.admin')

@section('title', 'Schedule')

@section('page-title', 'Schedule')

@section('content')

    <div class="space-y-6">

        {{-- Header --}}

        <div
            class="flex
               flex-col
               gap-4
               lg:flex-row
               lg:items-end
               lg:justify-between">

            <div>

                <h1 class="text-4xl
                       font-bold
                       text-slate-900">
                    Class Schedule
                </h1>

                <p class="mt-2
                       text-sm
                       text-slate-500">
                    View daily classes, Math sub-sections
                    and student lists.
                </p>

            </div>


            <div class="flex flex-wrap gap-3">

                @if (auth()->user()->hasPermission('section_offerings.view'))
                    <a href="{{ route('admin.section-offerings.index') }}"
                        class="inline-flex
                           h-11
                           items-center
                           rounded-xl
                           border
                           border-blue-300
                           px-5
                           font-semibold
                           text-blue-700">
                        Manage Class Offerings
                    </a>
                @endif


                @if ($selectedDay && auth()->user()->hasPermission('enrolments.print'))
                    <a href="{{ route('admin.schedule.print-day', [
                        'day_id' => $selectedDay->id,
                    ]) }}"
                        class="inline-flex
                           h-11
                           items-center
                           rounded-xl
                           bg-blue-600
                           px-5
                           font-semibold
                           text-white">
                        Print / Export
                    </a>
                @endif

            </div>

        </div>



        {{-- Day Tabs --}}

        @if ($days->isNotEmpty())

            <div
                class="flex
                   flex-wrap
                   gap-3
                   rounded-2xl
                   border
                   border-slate-200
                   bg-white
                   p-3">

                @foreach ($days as $day)
                    @php

                        $date = $dayDates[$day->id];

                    @endphp


                    <a href="{{ route('admin.schedule.index', [
                        'day_id' => $day->id,
                    ]) }}"
                        class="
                        min-w-32
                        rounded-xl
                        border
                        px-5 py-3
                        text-center

                        {{ (int) $selectedDayId === (int) $day->id
                            ? 'border-blue-600 bg-blue-600 text-white'
                            : 'border-slate-200 bg-white text-slate-700' }}
                    ">

                        <p class="font-bold">

                            {{ $date->isToday() ? 'Today' : $day->day_name }}

                        </p>

                        <p class="mt-1 text-xs">
                            {{ $date->format('M j') }}
                        </p>

                    </a>
                @endforeach

            </div>

        @endif



        @if (!$selectedDay)

            <div
                class="rounded-2xl
                   border
                   bg-white
                   p-12
                   text-center">
                No active class days configured.
            </div>
        @else
            {{-- Summary --}}

            <div class="grid
                   gap-4
                   md:grid-cols-2
                   xl:grid-cols-4">

                <div class="rounded-2xl border bg-white p-5">

                    <p class="text-xs uppercase text-slate-400">
                        Selected Day
                    </p>

                    <p class="mt-2 font-bold">
                        {{ $selectedDate?->format('l, M j, Y') }}
                    </p>

                </div>


                <div class="rounded-2xl border bg-white p-5">

                    <p class="text-xs uppercase text-slate-400">
                        Total Classes
                    </p>

                    <p class="mt-2 text-3xl font-bold">
                        {{ $totalClasses }}
                    </p>

                </div>


                <div class="rounded-2xl border bg-white p-5">

                    <p class="text-xs uppercase text-slate-400">
                        Students
                    </p>

                    <p class="mt-2 text-3xl font-bold">
                        {{ $totalStudents }}
                    </p>

                </div>


                <div class="rounded-2xl border bg-white p-5">

                    <p class="text-xs uppercase text-slate-400">
                        Wishlist
                    </p>

                    <p class="mt-2 text-3xl font-bold">
                        {{ $totalWishlist }}
                    </p>

                </div>

            </div>



            <div class="grid
                   gap-5
                   xl:grid-cols-[0.95fr_1.05fr]">

                {{-- Left --}}

                <div class="space-y-5">

                    <section
                        class="overflow-hidden
                           rounded-2xl
                           border
                           bg-white">

                        <div class="border-b
                               px-6 py-5">
                            <h2 class="text-lg font-bold">
                                Class Times
                            </h2>
                        </div>


                        <table class="min-w-full">

                            <thead class="bg-slate-50">

                                <tr>

                                    <th class="px-5 py-3 text-left">
                                        Time
                                    </th>

                                    <th class="px-5 py-3 text-center">
                                        Classes
                                    </th>

                                    <th class="px-5 py-3 text-center">
                                        Enrolments
                                    </th>

                                    <th class="px-5 py-3 text-center">
                                        Wishlist
                                    </th>

                                    <th class="px-5 py-3 text-center">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y">

                                @foreach ($scheduleRows as $row)
                                    <tr
                                        class="
                                        {{ $selectedTime === $row['raw_time'] ? 'bg-blue-50' : '' }}
                                    ">

                                        <td class="px-5 py-4 font-bold">
                                            {{ $row['time'] }}
                                        </td>

                                        <td class="px-5 py-4 text-center">
                                            {{ $row['class_count'] }}
                                        </td>

                                        <td class="px-5 py-4 text-center">
                                            {{ $row['enrolment_count'] }}
                                        </td>

                                        <td class="px-5 py-4 text-center">
                                            {{ $row['wishlist_count'] }}
                                        </td>

                                        <td class="px-5 py-4 text-center">

                                            <a href="{{ route('admin.schedule.index', [
                                                'day_id' => $selectedDay->id,

                                                'time' => $row['raw_time'],
                                            ]) }}"
                                                class="rounded-lg
                                                   border
                                                   border-blue-300
                                                   px-4 py-2
                                                   text-blue-700">
                                                View
                                            </a>

                                        </td>

                                    </tr>
                                @endforeach

                            </tbody>

                        </table>

                    </section>



                    @if ($selectedTime)

                        <section>

                            <h2
                                class="mb-3
                                   text-lg
                                   font-bold">
                                Classes at
                                {{ \Carbon\Carbon::parse($selectedTime)->format('g:i A') }}
                            </h2>


                            <div
                                class="grid
                                   gap-4
                                   md:grid-cols-2">

                                @foreach ($timeOfferings as $offering)
                                    <div
                                        class="
                                        rounded-2xl
                                        border
                                        bg-white
                                        p-5

                                        {{ $selectedOffering && (int) $selectedOffering->id === (int) $offering->id
                                            ? 'border-blue-500 ring-2 ring-blue-100'
                                            : 'border-slate-200' }}
                                    ">

                                        <a
                                            href="{{ route('admin.schedule.index', [
                                                'day_id' => $selectedDay->id,

                                                'time' => $selectedTime,

                                                'offering_id' => $offering->id,
                                            ]) }}">

                                            <p class="text-lg font-bold">
                                                {{ $offering->section?->section_name }}
                                            </p>


                                            @if ($offering->subSections->isNotEmpty())
                                                <p
                                                    class="mt-1
                                                       text-xs
                                                       font-semibold
                                                       text-blue-600">
                                                    {{ $offering->subSections->pluck('sub_section_name')->implode(' · ') }}
                                                </p>
                                            @endif


                                            <p
                                                class="mt-2
                                                   text-sm
                                                   text-slate-500">
                                                {{ $offering->allocated_seats }}
                                                /
                                                {{ $offering->max_seats }}
                                                students

                                                @if (strtolower($offering->section?->section_name ?? '') === 'math')
                                                    · Shared Math capacity
                                                @endif

                                            </p>

                                        </a>


                                        <a href="{{ route('admin.schedule.class-students', $offering) }}"
                                            class="mt-4
                                               inline-flex
                                               rounded-xl
                                               border
                                               border-blue-300
                                               px-4 py-2
                                               text-blue-700">
                                            Student List
                                        </a>

                                    </div>
                                @endforeach

                            </div>

                        </section>

                    @endif

                </div>



                {{-- Preview --}}

                <section
                    class="overflow-hidden
                       rounded-2xl
                       border
                       bg-white">

                    @if ($selectedOffering)

                        <div class="border-b
                               p-6">

                            <p class="text-xs uppercase text-slate-400">
                                Selected Class
                            </p>


                            <h2 class="mt-1 text-2xl font-bold">
                                {{ $selectedOffering->section?->section_name }}
                            </h2>


                            @if ($selectedOffering->subSections->isNotEmpty())
                                <p
                                    class="mt-1
                                       font-semibold
                                       text-blue-600">
                                    Sub-sections:
                                    {{ $selectedOffering->subSections->pluck('sub_section_name')->implode(', ') }}
                                </p>
                            @endif

                        </div>


                        <div class="overflow-x-auto">

                            <table class="min-w-full">

                                <thead class="bg-slate-50">

                                    <tr>

                                        <th class="px-5 py-3 text-left">
                                            Student
                                        </th>

                                        <th class="px-5 py-3 text-left">
                                            Sub-section
                                        </th>

                                        <th class="px-5 py-3 text-left">
                                            Guardian
                                        </th>

                                        <th class="px-5 py-3 text-left">
                                            Status
                                        </th>

                                    </tr>

                                </thead>


                                <tbody class="divide-y">

                                    @forelse ($previewEnrolments
                                        as $enrolment)
                                        @php

                                            $student = $enrolment->student;

                                            $guardian = $student?->guardians?->first(function ($guardian) {
                                                return (bool) $guardian->pivot->is_primary;
                                            });

                                            if (!$guardian && $student) {
                                                $guardian = $student->guardians->first();
                                            }

                                            /*
                                        |--------------------------------------------------------------------------
                                        | Vacation Override
                                        |--------------------------------------------------------------------------
                                        */

                                            $isVacation = $student && $vacationStudentIds->contains((int) $student->id);

                                            if ($isVacation) {
                                                $displayStatus = \App\Models\Admin\StudentLeave::VACATION_LABEL;

                                                $statusColour = \App\Models\Admin\StudentLeave::VACATION_COLOR;
                                            } else {
                                                $displayStatus = $student?->studentStatus?->status_name ?? '—';

                                                $statusColour = $student?->studentStatus?->color_code ?? '#64748b';
                                            }

                                            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $statusColour)) {
                                                $statusColour = '#64748b';
                                            }
                                        @endphp


                                        <tr
                                            class="
                                            {{ $isVacation ? 'bg-cyan-50/40' : '' }}
                                        ">

                                            <td class="px-5 py-4
                                                   font-semibold"
                                                style="
                                                color:
                                                {{ $statusColour }};
                                            ">
                                                {{ $student?->first_name }}

                                                {{ $student?->last_name }}
                                                @if ($student->nickname)
                                                    ({{ $student->nickname }})
                                                @endif
                                            </td>


                                            <td class="px-5 py-4">

                                                {{ $enrolment->subSection?->sub_section_name ?? '—' }}

                                            </td>


                                            <td class="px-5 py-4">

                                                @if ($guardian)
                                                    {{ $guardian->first_name }}

                                                    {{ $guardian->last_name }}
                                                @else
                                                    —
                                                @endif

                                            </td>


                                            <td class="px-5 py-4">

                                                <span
                                                    class="inline-flex
                                                       items-center
                                                       gap-2
                                                       text-xs
                                                       font-semibold"
                                                    style="
                                                    color:
                                                    {{ $statusColour }};
                                                ">

                                                    <span
                                                        class="h-2
                                                           w-2
                                                           rounded-full"
                                                        style="
                                                        background-color:
                                                        {{ $statusColour }};
                                                    "></span>


                                                    {{ $displayStatus }}

                                                </span>


                                                @if ($isVacation)
                                                    <p class="mt-1
                                                           text-[11px]"
                                                        style="
                                                        color:
                                                        {{ $statusColour }};
                                                    ">
                                                        On Leave
                                                    </p>
                                                @endif

                                            </td>

                                        </tr>


                                    @empty

                                        <tr>

                                            <td colspan="4"
                                                class="px-5 py-12
                                                   text-center">
                                                No students allocated.
                                            </td>

                                        </tr>
                                    @endforelse

                                </tbody>

                            </table>

                        </div>
                    @else
                        <div class="p-12 text-center">
                            Select a class to view students.
                        </div>

                    @endif

                </section>

            </div>

        @endif

    </div>

@endsection
