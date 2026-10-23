<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        Class Student List
    </title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            color: #111827;
            font-size: 12px;
        }

        h1 {
            margin-bottom: 5px;
        }

        .details {
            margin-bottom: 25px;
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 9px;
            text-align: left;
        }

        th {
            background: #f3f4f6;
        }

        .print-button {
            margin-bottom: 20px;
            padding: 9px 18px;
            border: none;
            border-radius: 6px;
            background: #2563eb;
            color: white;
            cursor: pointer;
        }

        @media print {

            .no-print {
                display: none;
            }

            body {
                margin: 10px;
            }

        }
    </style>

</head>


<body>


    <button type="button" onclick="window.print()" class="no-print print-button">
        Print
    </button>


    <h1>
        {{ $sectionOffering->section?->section_name ?? 'Class' }}
        Student List
    </h1>


    <div class="details">

        {{ $sectionOffering->day?->day_name }}

        ·

        {{ \Carbon\Carbon::parse($sectionOffering->start_time)->format('g:i A') }}

        –

        {{ \Carbon\Carbon::parse($sectionOffering->end_time)->format('g:i A') }}

        ·

        {{ $sectionOffering->enrolments->count() }}
        students

    </div>


    <table>

        <thead>

            <tr>

                <th style="width:40px;">
                    #
                </th>

                <th>
                    Student
                </th>

                <th>
                    Student ID
                </th>

                <th>
                    Guardian
                </th>

                <th>
                    Phone
                </th>

                <th>
                    Status
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse ($sectionOffering->enrolments as $enrolment)
                @php

                    $student = $enrolment->student;

                    $guardian = $student?->guardians?->first(function ($guardian) {
                        return $guardian->pivot->is_primary;
                    });

                    if (!$guardian && $student) {
                        $guardian = $student->guardians->first();
                    }

                @endphp


                <tr>

                    <td>
                        {{ $loop->iteration }}
                    </td>

                    <td>
                        {{ $student?->first_name }}
                        {{ $student?->last_name }}
                        @if ($student?->nickname)
                            ({{ $student->nickname }})
                        @endif
                    </td>

                    <td>
                        {{ $student?->external_id ?? '—' }}
                    </td>

                    <td>

                        @if ($guardian)
                            {{ $guardian->first_name }}
                            {{ $guardian->last_name }}
                        @else
                            —
                        @endif

                    </td>

                    <td>
                        {{ $guardian?->phone ?? '—' }}
                    </td>

                    <td>
                        {{ $student?->studentStatus?->status_name ?? '—' }}
                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="6" style="text-align:center;">
                        No students allocated.
                    </td>

                </tr>
            @endforelse

        </tbody>

    </table>


</body>

</html>
