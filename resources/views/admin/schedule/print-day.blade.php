<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Daily Class List
    </title>


    <style>
        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            padding: 30px;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            font-size: 12px;
            color: #111827;

            background: #f8fafc;
        }


        /*
        |--------------------------------------------------------------------------
        | Top Print Button
        |--------------------------------------------------------------------------
        */

        .print-actions {
            max-width: 1100px;

            margin: 0 auto 20px auto;

            display: flex;
            justify-content: flex-end;
        }


        .print-button {
            border: 0;

            border-radius: 7px;

            background: #2563eb;

            padding: 10px 22px;

            color: white;

            font-size: 13px;
            font-weight: 600;

            cursor: pointer;
        }


        .print-button:hover {
            background: #1d4ed8;
        }



        /*
        |--------------------------------------------------------------------------
        | Individual Class Page
        |--------------------------------------------------------------------------
        */

        .class-page {
            max-width: 1100px;

            margin: 0 auto 30px auto;

            background: white;

            border: 1px solid #e2e8f0;

            padding: 25px;

            page-break-inside: avoid;

            break-inside: avoid;
        }


        /*
         * Every class except the final class
         * starts a new printed page afterwards.
         */
        .page-break {
            page-break-after: always;

            break-after: page;
        }



        /*
        |--------------------------------------------------------------------------
        | Page Header
        |--------------------------------------------------------------------------
        */

        .document-title {
            margin: 0;

            font-size: 24px;
            font-weight: 700;

            color: #111827;
        }


        .document-date {
            margin-top: 7px;
            margin-bottom: 0;

            color: #64748b;

            font-size: 13px;
        }



        /*
        |--------------------------------------------------------------------------
        | Class Information
        |--------------------------------------------------------------------------
        */

        .class-information {
            margin-top: 25px;
            margin-bottom: 18px;

            padding: 15px 18px;

            border: 1px solid #dbeafe;

            border-radius: 8px;

            background: #eff6ff;
        }


        .class-name {
            margin: 0;

            font-size: 19px;
            font-weight: 700;

            color: #111827;
        }


        .class-meta {
            margin-top: 7px;

            display: flex;
            flex-wrap: wrap;
            gap: 8px;

            color: #475569;

            font-size: 12px;
        }


        .separator {
            color: #cbd5e1;
        }



        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        .summary {
            display: flex;
            flex-wrap: wrap;

            gap: 30px;

            margin-bottom: 18px;

            padding-bottom: 15px;

            border-bottom: 1px solid #e2e8f0;
        }


        .summary-item {
            min-width: 130px;
        }


        .summary-label {
            margin-bottom: 4px;

            color: #64748b;

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: 0.04em;
        }


        .summary-value {
            color: #111827;

            font-size: 16px;
            font-weight: 700;
        }



        /*
        |--------------------------------------------------------------------------
        | Student Table
        |--------------------------------------------------------------------------
        */

        table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }


        thead {
            display: table-header-group;
        }


        tr {
            page-break-inside: avoid;

            break-inside: avoid;
        }


        th,
        td {
            border: 1px solid #d1d5db;

            padding: 9px 8px;

            vertical-align: middle;

            text-align: left;

            word-wrap: break-word;
        }


        th {
            background: #f1f5f9;

            color: #334155;

            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;
        }


        td {
            font-size: 12px;
        }


        .number-column {
            width: 45px;

            text-align: center;
        }


        .student-column {
            width: 22%;
        }


        .student-id-column {
            width: 15%;
        }


        .guardian-column {
            width: 22%;
        }


        .phone-column {
            width: 16%;
        }


        .status-column {
            width: 15%;
        }


        .empty-row {
            padding: 22px;

            text-align: center;

            color: #64748b;
        }



        /*
        |--------------------------------------------------------------------------
        | Footer
        |--------------------------------------------------------------------------
        */

        .page-footer {
            margin-top: 18px;

            padding-top: 10px;

            border-top: 1px solid #e2e8f0;

            color: #94a3b8;

            font-size: 10px;

            text-align: right;
        }



        /*
        |--------------------------------------------------------------------------
        | Empty Schedule
        |--------------------------------------------------------------------------
        */

        .empty-schedule {
            max-width: 1100px;

            margin: 50px auto;

            padding: 40px;

            border: 1px solid #e2e8f0;

            background: white;

            text-align: center;

            color: #64748b;
        }



        /*
        |--------------------------------------------------------------------------
        | Print
        |--------------------------------------------------------------------------
        */

        @page {
            size: A4 portrait;
            margin: 12mm;
        }


        @media print {

            body {
                margin: 0;
                padding: 0;

                background: white;
            }


            .no-print {
                display: none !important;
            }


            .class-page {
                max-width: none;

                margin: 0;

                padding: 0;

                border: none;

                box-shadow: none;
            }


            .page-break {
                page-break-after: always !important;

                break-after: page !important;
            }


            table {
                width: 100%;
            }


            thead {
                display: table-header-group;
            }


            tr,
            td,
            th {
                page-break-inside: avoid !important;

                break-inside: avoid !important;
            }

        }
    </style>

</head>


<body>


    {{-- =========================================================
        PRINT BUTTON
    ========================================================== --}}

    <div class="print-actions no-print">

        <button type="button" onclick="window.print()" class="print-button">
            Print Whole Day
        </button>

    </div>



    {{-- =========================================================
        CLASSES
    ========================================================== --}}

    @forelse ($offerings as $offering)

        <section class="class-page {{ !$loop->last ? 'page-break' : '' }}">


            {{-- =================================================
                DOCUMENT HEADER
            ================================================== --}}

            <header>

                <h1 class="document-title">
                    Daily Class List
                </h1>


                <p class="document-date">

                    @if ($selectedDay)
                        {{ $selectedDay->day_name }}
                    @endif


                    @if ($selectedDate)
                        @if ($selectedDay)
                            ·
                        @endif

                        {{ $selectedDate->format('F j, Y') }}
                    @endif

                </p>

            </header>



            {{-- =================================================
                CLASS INFORMATION
            ================================================== --}}

            <div class="class-information">

                <h2 class="class-name">

                    {{ $offering->section?->section_name ?? 'Class' }}

                </h2>


                <div class="class-meta">

                    <span>

                        {{ $offering->day?->day_name ?? ($selectedDay?->day_name ?? '—') }}

                    </span>


                    <span class="separator">
                        •
                    </span>


                    <span>

                        {{ \Carbon\Carbon::parse($offering->start_time)->format('g:i A') }}

                        –

                        {{ \Carbon\Carbon::parse($offering->end_time)->format('g:i A') }}

                    </span>


                    <span class="separator">
                        •
                    </span>


                    <span>

                        {{ $offering->duration_minutes }}
                        minutes

                    </span>

                </div>

            </div>



            {{-- =================================================
                CLASS SUMMARY
            ================================================== --}}

            @php

                $studentCount = $offering->enrolments->count();

                $maximumSeats = (int) $offering->max_seats;

                $availableSeats = max(0, $maximumSeats - $studentCount);
            @endphp


            <div class="summary">


                {{-- Students --}}
                <div class="summary-item">

                    <div class="summary-label">
                        Students
                    </div>

                    <div class="summary-value">
                        {{ $studentCount }}
                    </div>

                </div>



                {{-- Maximum Seats --}}
                <div class="summary-item">

                    <div class="summary-label">
                        Maximum Seats
                    </div>

                    <div class="summary-value">
                        {{ $maximumSeats }}
                    </div>

                </div>



                {{-- Available Seats --}}
                <div class="summary-item">

                    <div class="summary-label">
                        Available Seats
                    </div>

                    <div class="summary-value">
                        {{ $availableSeats }}
                    </div>

                </div>

            </div>



            {{-- =================================================
                STUDENT LIST
            ================================================== --}}

            <table>

                <thead>

                    <tr>

                        <th class="number-column">
                            #
                        </th>

                        <th class="student-column">
                            Student
                        </th>

                        <th class="student-id-column">
                            Student ID
                        </th>

                        <th class="guardian-column">
                            Guardian
                        </th>

                        <th class="phone-column">
                            Phone
                        </th>

                        <th class="status-column">
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse ($offering->enrolments as $enrolment)
                        @php

                            $student = $enrolment->student;

                            /*
                             * Find primary guardian.
                             */
                            $guardian = $student?->guardians?->first(function ($guardian) {
                                return $guardian->pivot->is_primary;
                            });

                            /*
                             * If there is no primary
                             * guardian, use the first
                             * guardian connected to
                             * the student.
                             */
                            if (!$guardian && $student) {
                                $guardian = $student->guardians->first();
                            }

                        @endphp


                        <tr>


                            {{-- Number --}}
                            <td class="number-column">

                                {{ $loop->iteration }}

                            </td>



                            {{-- Student --}}
                            <td>

                                @if ($student)
                                    {{ $student->first_name }}

                                    {{ $student->last_name }}
                                    @if ($student->nickname)
                                        ({{ $student->nickname }})
                                    @endif
                                @else
                                    —
                                @endif

                            </td>



                            {{-- Student ID --}}
                            <td>

                                {{ $student?->external_id ?? '—' }}

                            </td>



                            {{-- Guardian --}}
                            <td>

                                @if ($guardian)
                                    {{ $guardian->first_name }}

                                    {{ $guardian->last_name }}
                                @else
                                    —
                                @endif

                            </td>



                            {{-- Phone --}}
                            <td>

                                {{ $guardian?->phone ?? '—' }}

                            </td>



                            {{-- Status --}}
                            <td>

                                {{ $student?->studentStatus?->status_name ?? '—' }}

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td colspan="6" class="empty-row">
                                No students allocated
                                to this class.
                            </td>

                        </tr>
                    @endforelse

                </tbody>

            </table>



            {{-- =================================================
                PAGE FOOTER
            ================================================== --}}

            <div class="page-footer">

                {{ $offering->section?->section_name ?? 'Class' }}

                ·

                {{ \Carbon\Carbon::parse($offering->start_time)->format('g:i A') }}

            </div>


        </section>


    @empty


        {{-- =====================================================
            NO CLASSES
        ====================================================== --}}

        <div class="empty-schedule">

            <h2>
                No Classes Scheduled
            </h2>


            <p>

                There are no active class offerings for

                @if ($selectedDay)
                    {{ $selectedDay->day_name }}
                @else
                    this day
                @endif

                .

            </p>

        </div>
    @endforelse



</body>

</html>
