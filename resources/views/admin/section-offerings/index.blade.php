@extends('layouts.admin')

@section('title', 'Class Offering')

@section('page-title', 'Class Offering')


@php

    $breadcrumbs = [
        [
            'label' => 'Class Setup',
            'url' => null,
        ],
        [
            'label' => 'Class Offering',
            'url' => null,
        ],
    ];

    /*
     * Interactive uses one shared capacity across its
     * English and Math sub-sections.
     */
    $interactiveSubSections = $interactiveSubSections ?? ($interactiveSection?->subSections ?? collect());

    $interactiveCapacity = (int) (($interactiveRows ?? collect())->max('maximum') ?? 5);

    $getStatus = function ($allocated, $maximum) {
        $allocated = (int) $allocated;

        $maximum = (int) $maximum;

        if ($maximum <= 0) {
            return [
                'label' => 'Not configured',

                'class' => 'bg-slate-100 text-slate-600',

                'bar' => 'bg-slate-300',
            ];
        }

        $filledPercentage = ($allocated / $maximum) * 100;

        if ($allocated >= $maximum) {
            return [
                'label' => 'Full',

                'class' => 'bg-red-100 text-red-700',

                'bar' => 'bg-red-500',
            ];
        }

        if ($filledPercentage >= 80) {
            return [
                'label' => 'Nearly Full',

                'class' => 'bg-amber-100 text-amber-700',

                'bar' => 'bg-amber-500',
            ];
        }

        return [
            'label' => 'Available',

            'class' => 'bg-green-100 text-green-700',

            'bar' => 'bg-green-500',
        ];
    };
@endphp


@section('content')

    <style>
        @media print {

            .no-print {
                display: none !important;
            }

            .print-container {
                border: none !important;
                box-shadow: none !important;
            }

        }
    </style>


    <div class="space-y-7">


        {{-- Messages --}}

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



        {{-- Header --}}

        <div
            class="flex
               flex-col
               gap-5
               lg:flex-row
               lg:items-end
               lg:justify-between">

            <h1
                class="text-4xl
                   font-bold
                   tracking-tight
                   text-slate-900">
                Class Offering
            </h1>


            <div class="no-print
                   flex
                   flex-wrap
                   gap-3">

                @if (auth()->user()->hasPermission('section_offerings.create'))
                    <a href="{{ route('admin.section-offerings.create') }}"
                        class="inline-flex
                           h-12
                           items-center
                           rounded-xl
                           border
                           border-blue-300
                           bg-white
                           px-6
                           text-sm
                           font-semibold
                           text-blue-700
                           hover:bg-blue-50">
                        + Add Class Offering
                    </a>
                @endif


                {{-- <button
                type="button"
                onclick="window.print()"
                class="inline-flex
                       h-12
                       items-center
                       rounded-xl
                       bg-blue-600
                       px-7
                       text-sm
                       font-semibold
                       text-white
                       hover:bg-blue-700"
            >
                Print daily list
            </button> --}}

            </div>

        </div>



        @if ($days->isEmpty())

            <div
                class="rounded-2xl
                   border border-slate-200
                   bg-white
                   px-6 py-16
                   text-center
                   shadow-sm">

                <p class="font-semibold
                       text-slate-800">
                    No active class offerings found.
                </p>


                <p class="mt-2
                       text-sm
                       text-slate-500">
                    Create a class offering to begin.
                </p>

            </div>
        @else
            {{-- Day Tabs --}}

            <div class="no-print
                   flex
                   flex-wrap
                   gap-3">

                @foreach ($days as $day)
                    <a href="{{ route('admin.section-offerings.index', [
                        'day_id' => $day->id,

                        'subject' => $selectedSubject,
                    ]) }}"
                        class="inline-flex
                           min-w-32
                           items-center
                           justify-center
                           rounded-xl
                           border
                           px-6 py-3
                           text-sm
                           font-semibold
                           {{ (int) $selectedDayId === (int) $day->id
                               ? 'border-blue-600 bg-blue-600 text-white'
                               : 'border-slate-200 bg-white text-blue-700 hover:bg-blue-50' }}">
                        {{ $day->day_name }}
                    </a>
                @endforeach

            </div>



            {{-- Subject Tabs --}}

            <div
                class="no-print
                   grid
                   max-w-3xl
                   grid-cols-3
                   overflow-hidden
                   rounded-xl
                   border
                   border-slate-200
                   bg-white">

                <a href="{{ route('admin.section-offerings.index', [
                    'day_id' => $selectedDayId,

                    'subject' => 'english',
                ]) }}"
                    class="flex
                       h-16
                       items-center
                       justify-center
                       border-r
                       font-semibold
                       {{ $selectedSubject === 'english' ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-blue-50' }}">
                    English
                </a>


                <a href="{{ route('admin.section-offerings.index', [
                    'day_id' => $selectedDayId,

                    'subject' => 'math',
                ]) }}"
                    class="flex
                       h-16
                       items-center
                       justify-center
                       border-r
                       font-semibold
                       {{ $selectedSubject === 'math' ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-blue-50' }}">
                    Math
                </a>


                <a href="{{ route('admin.section-offerings.index', [
                    'day_id' => $selectedDayId,

                    'subject' => 'interactive',
                ]) }}"
                    class="flex
                       h-16
                       items-center
                       justify-center
                       font-semibold
                       {{ $selectedSubject === 'interactive' ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-blue-50' }}">
                    Interactive
                </a>

            </div>



            {{-- Information --}}

            <div
                class="rounded-2xl
                   border
                   border-blue-100
                   bg-blue-50/50
                   px-6 py-5
                   text-sm
                   text-slate-600">

                @if ($selectedSubject === 'english')

                    <strong>
                        English
                    </strong>

                    maximum
                    {{ $englishCapacity ?: '—' }}
                    seats per timeslot.
                @elseif ($selectedSubject === 'math')
                    <strong>
                        Math
                    </strong>

                    shared maximum
                    {{ $mathCapacity ?: '—' }}
                    students

                    @if ($mathSubSections->isNotEmpty())
                        across

                        {{ $mathSubSections->pluck('sub_section_name')->implode(', ') }}.
                    @endif
                @else
                    <strong>
                        Interactive
                    </strong>

                    has one shared maximum of
                    {{ $interactiveCapacity ?: 5 }}
                    students across

                    @if ($interactiveSubSections->isNotEmpty())
                        {{ $interactiveSubSections->pluck('sub_section_name')->implode(' and ') }}.
                    @else
                        English and Math.
                    @endif

                @endif

            </div>



            <div>

                <p
                    class="text-xs
                       font-semibold
                       uppercase
                       text-slate-400">
                    Selected Day
                </p>

                <p
                    class="mt-2
                       text-xl
                       font-bold
                       text-slate-900">
                    {{ $selectedDay?->day_name ?? '—' }}
                </p>

            </div>



            {{-- English --}}

            @if ($selectedSubject === 'english')

                <div
                    class="print-container
                       overflow-hidden
                       rounded-2xl
                       border
                       border-slate-200
                       bg-white
                       shadow-sm">

                    <table class="min-w-full">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-7 py-5 text-left">
                                    Time
                                </th>


                                <th class="px-7 py-5 text-center">
                                    Enrolled / Capacity
                                </th>

                                <th class="px-7 py-5 text-center">
                                    Availability
                                </th>

                                <th class="px-7 py-5 text-center">
                                    Status
                                </th>

                                <th class="no-print px-7 py-5 text-center">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y">

                            @forelse ($englishRows
                                    as $row)
                                @php

                                    $allocated = (int) $row['allocated'];

                                    $maximum = (int) $row['maximum'];

                                    $available = max(0, $maximum - $allocated);

                                    $status = $getStatus($allocated, $maximum);
                                @endphp


                                <tr>

                                    <td class="px-7 py-7">

                                        <strong class="text-lg">
                                            {{ $row['time'] }}
                                        </strong>

                                        <div class="text-xs
                                               text-slate-400">
                                            to
                                            {{ $row['end_time'] }}
                                        </div>

                                    </td>


                                    <td class="px-7 py-7
                                           text-center">
                                        <strong>
                                            {{ $allocated }}
                                            /
                                            {{ $maximum }}
                                        </strong>
                                    </td>


                                    <td class="px-7 py-7
                                           text-center">
                                        {{ $available }}
                                        seats available
                                    </td>


                                    <td class="px-7 py-7
                                           text-center">

                                        <span
                                            class="rounded-xl
                                               px-4 py-2
                                               text-xs
                                               font-semibold
                                               {{ $status['class'] }}">
                                            {{ $status['label'] }}
                                        </span>

                                    </td>


                                    <td class="no-print
                                           px-7 py-7">

                                        <div
                                            class="flex
                                               justify-center
                                               gap-2">

                                            @if (auth()->user()->hasPermission('section_offerings.edit'))
                                                <a href="{{ route('admin.section-offerings.edit', $row['id']) }}"
                                                    class="rounded-lg
                                                       border
                                                       border-blue-300
                                                       px-4 py-2
                                                       text-sm
                                                       font-semibold
                                                       text-blue-700
                                                       hover:bg-blue-50">
                                                    View
                                                </a>
                                            @endif


                                            @if (auth()->user()->hasPermission('section_offerings.delete'))
                                                <a href="{{ route('admin.section-offerings.confirm-delete', $row['id']) }}"
                                                    class="rounded-lg
               border
               border-red-300
               px-4 py-2
               text-sm
               font-semibold
               text-red-600
               hover:bg-red-50">
                                                    Delete
                                                </a>
                                            @endif

                                        </div>

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td colspan="5"
                                        class="px-6 py-16
                                           text-center
                                           text-slate-500">
                                        No English classes
                                        on this day.
                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            @endif



            {{-- Math --}}

            @if ($selectedSubject === 'math')

                <div
                    class="print-container
                       overflow-hidden
                       rounded-2xl
                       border
                       border-slate-200
                       bg-white
                       shadow-sm">

                    <div class="overflow-x-auto">

                        <table class="min-w-full">

                            <thead class="bg-slate-50">

                                <tr>

                                    <th class="px-6 py-5 text-left">
                                        Time
                                    </th>


                                    @foreach ($mathSubSections as $subSection)
                                        <th class="px-5 py-5
                                               text-center">
                                            {{ $subSection->sub_section_name }}
                                        </th>
                                    @endforeach


                                    <th class="px-6 py-5 text-center">
                                        Math Total / Capacity
                                    </th>

                                    <th class="px-6 py-5 text-center">
                                        Availability
                                    </th>

                                    <th class="px-6 py-5 text-center">
                                        Status
                                    </th>

                                    <th class="no-print px-6 py-5 text-center">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y">

                                @forelse ($mathRows
                                        as $row)
                                    @php

                                        $allocated = (int) $row['math_total'];

                                        $maximum = (int) $row['math_maximum'];

                                        $available = max(0, $maximum - $allocated);

                                        $status = $getStatus($allocated, $maximum);
                                    @endphp


                                    <tr>

                                        <td
                                            class="px-6 py-7
                                               whitespace-nowrap">

                                            <strong class="text-lg">
                                                {{ $row['time'] }}
                                            </strong>

                                            <div
                                                class="text-xs
                                                   text-slate-400">
                                                to
                                                {{ $row['end_time'] }}
                                            </div>

                                        </td>


                                        @foreach ($mathSubSections as $subSection)
                                            <td
                                                class="px-5 py-7
                                                   text-center">

                                                @if (in_array($subSection->id, $row['available_sub_sections']))
                                                    <strong>
                                                        {{ $row['math_allocations'][$subSection->id] ?? 0 }}
                                                    </strong>
                                                @else
                                                    <span class="text-slate-300">
                                                        —
                                                    </span>
                                                @endif

                                            </td>
                                        @endforeach


                                        <td class="px-6 py-7
                                               text-center">

                                            <strong class="text-lg">
                                                {{ $allocated }}
                                                /
                                                {{ $maximum }}
                                            </strong>

                                            <div
                                                class="text-xs
                                                   text-slate-400">
                                                shared seats
                                            </div>

                                        </td>


                                        <td class="px-6 py-7
                                               text-center">
                                            {{ $available }}
                                            seats available
                                        </td>


                                        <td class="px-6 py-7
                                               text-center">

                                            <span
                                                class="rounded-xl
                                                   px-4 py-2
                                                   text-xs
                                                   font-semibold
                                                   {{ $status['class'] }}">
                                                {{ $status['label'] }}
                                            </span>

                                        </td>


                                        <td class="no-print
                                               px-6 py-7">

                                            <div
                                                class="flex
                                                   justify-center
                                                   gap-2">

                                                @if (auth()->user()->hasPermission('section_offerings.edit'))
                                                    <a href="{{ route('admin.section-offerings.edit', $row['id']) }}"
                                                        class="rounded-lg
                                                           border
                                                           border-blue-300
                                                           px-4 py-2
                                                           text-sm
                                                           font-semibold
                                                           text-blue-700
                                                           hover:bg-blue-50">
                                                        View
                                                    </a>
                                                @endif


                                                @if (auth()->user()->hasPermission('section_offerings.delete'))
                                                    <form method="POST"
                                                        action="{{ route('admin.section-offerings.destroy', $row['id']) }}"
                                                        onsubmit="return confirm('Delete this Math offering?');">

                                                        @csrf
                                                        @method('DELETE')


                                                        <button type="submit"
                                                            class="rounded-lg
                                                               border
                                                               border-red-300
                                                               px-4 py-2
                                                               text-sm
                                                               font-semibold
                                                               text-red-600
                                                               hover:bg-red-50">
                                                            Delete
                                                        </button>

                                                    </form>
                                                @endif

                                            </div>

                                        </td>

                                    </tr>


                                @empty

                                    <tr>

                                        <td colspan="{{ 5 + $mathSubSections->count() }}"
                                            class="px-6 py-16
                                               text-center
                                               text-slate-500">
                                            No Math classes
                                            on this day.
                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            @endif



            {{-- Interactive --}}

            @if ($selectedSubject === 'interactive')

                <div
                    class="print-container
                       overflow-hidden
                       rounded-2xl
                       border
                       border-slate-200
                       bg-white
                       shadow-sm">

                    <table class="min-w-full">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-7 py-5 text-left">
                                    Time
                                </th>


                                @foreach ($interactiveSubSections as $subSection)
                                    <th class="px-6 py-5 text-center">
                                        {{ $subSection->sub_section_name }}
                                    </th>
                                @endforeach

                                <th class="px-7 py-5 text-center">
                                    Interactive Total / Capacity
                                </th>

                                <th class="px-7 py-5 text-center">
                                    Availability
                                </th>

                                <th class="px-7 py-5 text-center">
                                    Status
                                </th>

                                <th class="no-print px-7 py-5 text-center">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y">

                            @forelse ($interactiveRows
                                    as $row)
                                @php

                                    $allocated = (int) $row['allocated'];

                                    $maximum = (int) $row['maximum'];

                                    $available = max(0, $maximum - $allocated);

                                    $status = $getStatus($allocated, $maximum);

                                    $interactiveAllocations = $row['interactive_allocations'] ?? [];
                                @endphp


                                <tr>

                                    <td class="px-7 py-7">

                                        <strong class="text-lg">
                                            {{ $row['time'] }}
                                        </strong>

                                        <div class="text-xs
                                               text-slate-400">
                                            to
                                            {{ $row['end_time'] }}
                                        </div>

                                    </td>


                                    @foreach ($interactiveSubSections as $subSection)
                                        <td
                                            class="px-6 py-7
                                               text-center
                                               font-semibold">
                                            {{ (int) ($interactiveAllocations[$subSection->id] ?? 0) }}
                                        </td>
                                    @endforeach


                                    <td class="px-7 py-7
                                           text-center">
                                        <strong>
                                            {{ $allocated }}
                                            /
                                            {{ $maximum }}
                                        </strong>
                                    </td>


                                    <td class="px-7 py-7
                                           text-center">
                                        {{ $available }}
                                        seats available
                                    </td>


                                    <td class="px-7 py-7
                                           text-center">

                                        <span
                                            class="rounded-xl
                                               px-4 py-2
                                               text-xs
                                               font-semibold
                                               {{ $status['class'] }}">
                                            {{ $status['label'] }}
                                        </span>

                                    </td>


                                    <td class="no-print
                                           px-7 py-7">

                                        <div
                                            class="flex
                                               justify-center
                                               gap-2">

                                            @if (auth()->user()->hasPermission('section_offerings.edit'))
                                                <a href="{{ route('admin.section-offerings.edit', $row['id']) }}"
                                                    class="rounded-lg
                                                       border
                                                       border-blue-300
                                                       px-4 py-2
                                                       text-sm
                                                       font-semibold
                                                       text-blue-700
                                                       hover:bg-blue-50">
                                                    View
                                                </a>
                                            @endif


                                            @if (auth()->user()->hasPermission('section_offerings.delete'))
                                                <form method="POST"
                                                    action="{{ route('admin.section-offerings.destroy', $row['id']) }}"
                                                    onsubmit="return confirm('Delete this Interactive offering?');">

                                                    @csrf
                                                    @method('DELETE')


                                                    <button type="submit"
                                                        class="rounded-lg
                                                           border
                                                           border-red-300
                                                           px-4 py-2
                                                           text-sm
                                                           font-semibold
                                                           text-red-600
                                                           hover:bg-red-50">
                                                        Delete
                                                    </button>

                                                </form>
                                            @endif

                                        </div>

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td colspan="{{ 5 + $interactiveSubSections->count() }}"
                                        class="px-6 py-16
                                           text-center
                                           text-slate-500">
                                        No Interactive classes
                                        on this day.
                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            @endif



            {{-- Bottom information --}}

            <div
                class="no-print
                   grid
                   gap-4
                   rounded-2xl
                   border
                   border-blue-100
                   bg-white
                   p-5
                   md:grid-cols-3">

                <div class="px-4 py-2">

                    <strong>
                        Capacity
                    </strong>


                    <p class="mt-1
                           text-sm
                           text-slate-500">

                        @if ($selectedSubject === 'english')
                            Max
                            {{ $englishCapacity ?: '—' }}
                            students per timeslot
                        @elseif ($selectedSubject === 'math')
                            Shared max
                            {{ $mathCapacity ?: '—' }}
                            students
                        @else
                            Shared max
                            {{ $interactiveCapacity ?: 5 }}
                            students across Interactive English and Math
                        @endif

                    </p>

                </div>


                <div class="px-4 py-2">

                    <strong>
                        Selected Class
                    </strong>

                    <p
                        class="mt-1
                           text-sm
                           capitalize
                           text-slate-500">
                        {{ $selectedSubject }}
                    </p>

                </div>


                <div class="px-4 py-2">

                    <strong>
                        Selected Day
                    </strong>

                    <p class="mt-1
                           text-sm
                           text-slate-500">
                        {{ $selectedDay?->day_name ?? '—' }}
                    </p>

                </div>

            </div>

        @endif

    </div>

@endsection
