@extends('layouts.admin')

@section('content')

<div class="max-w-4xl mx-auto p-6">

    <div class="bg-red-50 border border-red-300 rounded-lg p-6">

        <h1 class="text-2xl font-bold text-red-700 mb-4">
            Confirm Class Deletion
        </h1>

        <p class="text-gray-800 mb-4">
            This class has related data. If you continue,
            the related class data will be permanently deleted.
        </p>

        <div class="bg-white rounded-lg border p-4 mb-5">

            <h2 class="font-bold text-lg mb-3">
                Class information
            </h2>

            <p>
                <strong>Class ID:</strong>
                {{ $sectionOffering->id }}
            </p>

            <p>
                <strong>Start time:</strong>
                {{ $sectionOffering->start_time }}
            </p>

            <p>
                <strong>End time:</strong>
                {{ $sectionOffering->end_time }}
            </p>

            <p>
                <strong>Enrolment records:</strong>
                {{ $enrolments->count() }}
            </p>

            <p>
                <strong>Subsection links:</strong>
                {{ $subSections->count() }}
            </p>

        </div>

        @if ($enrolments->count() > 0)

            <div class="bg-yellow-50 border border-yellow-300
                        rounded-lg p-4 mb-5">

                <h2 class="font-bold text-yellow-800 mb-3">
                    Enrolment data that will be deleted
                </h2>

                @foreach ($enrolments as $enrolment)

                    <div class="border-b py-2">

                        <p>
                            <strong>Enrolment ID:</strong>
                            {{ $enrolment->id }}
                        </p>

                        <p>
                            <strong>Student ID:</strong>
                            {{ $enrolment->student_id }}
                        </p>

                        <p>
                            <strong>Subsection ID:</strong>
                            {{ $enrolment->sub_section_id }}
                        </p>

                        <p>
                            <strong>Status:</strong>
                            @if ($enrolment->is_active)
                                Active
                            @else
                                Inactive
                            @endif
                        </p>

                    </div>

                @endforeach

            </div>

        @else

            <p class="text-green-700 mb-5">
                No enrolment records are attached to this class.
            </p>

        @endif

        @if ($subSections->count() > 0)

            <div class="bg-blue-50 border border-blue-300
                        rounded-lg p-4 mb-5">

                <h2 class="font-bold text-blue-800 mb-3">
                    Subsection links that will be deleted
                </h2>

                @foreach ($subSections as $subSection)

                    <p class="border-b py-2">
                        {{ $subSection->sub_section_name
                            ?? 'Subsection ID: ' .
                            $subSection->sub_section_id }}
                    </p>

                @endforeach

            </div>

        @endif

        <p class="font-bold text-red-700 mb-5">
            The student record itself will not be deleted.
            Only this class and its related class data will be removed.
        </p>

        <form
            method="POST"
            action="{{ route(
                'admin.section-offerings.destroy',
                $sectionOffering
            ) }}"
        >

            @csrf
            @method('DELETE')

            <label class="flex items-center gap-2 mb-5">

                <input
                    type="checkbox"
                    name="confirm_delete"
                    value="1"
                    required
                >

                <span>
                    I understand and want to permanently delete
                    this class and its related data.
                </span>

            </label>

            <div class="flex gap-3">

                <button
                    type="submit"
                    class="bg-red-600 text-white px-5 py-2
                           rounded-lg hover:bg-red-700"
                >
                    Confirm Delete
                </button>

                <a
                    href="{{ url()->previous() }}"
                    class="bg-gray-500 text-white px-5 py-2
                           rounded-lg hover:bg-gray-600"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

@endsection
