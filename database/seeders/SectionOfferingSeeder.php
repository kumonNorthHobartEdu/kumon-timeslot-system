<?php

namespace Database\Seeders;

use App\Models\Admin\Day;
use App\Models\Admin\Section;
use App\Models\Admin\SectionOffering;
use App\Models\Admin\SubSection;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SectionOfferingSeeder extends Seeder
{
    public function run(): void
    {
        $english = Section::where(
            'section_name',
            'English'
        )->firstOrFail();

        $math = Section::where(
            'section_name',
            'Math'
        )->firstOrFail();

        $interactive = Section::where(
            'section_name',
            'Interactive'
        )->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Interactive sub-sections
        |--------------------------------------------------------------------------
        */

        $interactiveSubSectionIds = collect([
            'English',
            'Math',
        ])->map(function (string $name) use ($interactive) {
            return SubSection::updateOrCreate(
                [
                    'section_id' => $interactive->id,
                    'sub_section_name' => $name,
                ],
                [
                    'description' => 'Interactive ' . $name,
                    'is_active' => true,
                ]
            )->id;
        });

        /*
        |--------------------------------------------------------------------------
        | Regular English and Math timetable
        |--------------------------------------------------------------------------
        */

        $regularTimetable = [
            'Monday' => [
                '15:45',
                '16:30',
                '17:15',
                '18:00',
            ],

            'Tuesday' => [
                '15:45',
            ],

            'Thursday' => [
                '15:45',
                '16:30',
                '17:15',
                '18:00',
            ],

            'Friday' => [
                '15:45',
            ],
        ];

        foreach ($regularTimetable as $dayName => $times) {
            $day = Day::where(
                'day_name',
                $dayName
            )->firstOrFail();

            foreach ($times as $time) {
                /*
                 * English: 28 seats
                 */
                $this->createOffering(
                    $day->id,
                    $english->id,
                    $time,
                    45,
                    28
                );

                /*
                 * Math: 41 shared seats
                 */
                $mathOffering = $this->createOffering(
                    $day->id,
                    $math->id,
                    $time,
                    45,
                    41
                );

                $mathSubSectionIds = $math
                    ->subSections()
                    ->where(
                        'is_active',
                        true
                    )
                    ->pluck(
                        'sub_sections.id'
                    )
                    ->all();

                $mathOffering
                    ->subSections()
                    ->sync($mathSubSectionIds);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Final Interactive timetable
        |--------------------------------------------------------------------------
        |
        | Monday and Thursday:
        |
        | 3:45 pm
        | 4:15 pm
        | 4:35 pm
        | 5:00 pm
        | 5:25 pm
        | 5:50 pm
        | 6:15 pm
        | 6:40 pm
        |
        | Tuesday and Friday remain:
        |
        | 3:45 pm
        | 4:15 pm
        |--------------------------------------------------------------------------
        */

        $interactiveTimetable = [
            'Monday' => [
                '15:45',
                '16:15',
                '16:35',
                '17:00',
                '17:25',
                '17:50',
                '18:15',
                '18:40',
            ],

            'Tuesday' => [
                '15:45',
                '16:15',
            ],

            'Thursday' => [
                '15:45',
                '16:15',
                '16:35',
                '17:00',
                '17:25',
                '17:50',
                '18:15',
                '18:40',
            ],

            'Friday' => [
                '15:45',
                '16:15',
            ],
        ];

        foreach (
            $interactiveTimetable as $dayName => $times
        ) {
            $day = Day::where(
                'day_name',
                $dayName
            )->firstOrFail();

            if (in_array(
                $dayName,
                ['Monday', 'Thursday'],
                true
            )) {
                $offerings = $this->syncChangedInteractiveOfferings(
                    $day->id,
                    $interactive->id,
                    $times
                );
            } else {
                $offerings = collect();

                foreach ($times as $time) {
                    $offerings->push(
                        $this->createOffering(
                            $day->id,
                            $interactive->id,
                            $time,
                            20,
                            5
                        )
                    );
                }
            }

            foreach ($offerings as $offering) {
                $offering
                    ->subSections()
                    ->sync(
                        $interactiveSubSectionIds->all()
                    );
            }
        }
    }

    private function syncChangedInteractiveOfferings(
        int $dayId,
        int $sectionId,
        array $newTimes
    ) {
        /*
        |--------------------------------------------------------------------------
        | Preserve existing offering IDs
        |--------------------------------------------------------------------------
        |
        | Existing student enrolments point to section_offering_id.
        | Updating the existing records keeps those enrolments connected.
        |--------------------------------------------------------------------------
        */

        $existingOfferings = SectionOffering::where(
            'day_id',
            $dayId
        )
            ->where(
                'section_id',
                $sectionId
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy(
                'start_time'
            )
            ->get();

        $updatedOfferings = collect();

        foreach ($newTimes as $index => $newTime) {
            $existingOffering = $existingOfferings->get(
                $index
            );

            if ($existingOffering) {
                $start = Carbon::createFromFormat(
                    'H:i',
                    $newTime
                );

                $end = $start
                    ->copy()
                    ->addMinutes(20);

                $existingOffering->update([
                    'start_time' => $start->format(
                        'H:i:s'
                    ),

                    'end_time' => $end->format(
                        'H:i:s'
                    ),

                    'duration_minutes' => 20,

                    'max_seats' => 5,

                    'is_active' => true,
                ]);

                $updatedOfferings->push(
                    $existingOffering->fresh()
                );
            } else {
                $updatedOfferings->push(
                    $this->createOffering(
                        $dayId,
                        $sectionId,
                        $newTime,
                        20,
                        5
                    )
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Deactivate extra old Interactive offerings
        |--------------------------------------------------------------------------
        |
        | Do not delete them because existing enrolments may still reference
        | their IDs. They will no longer appear as active offerings.
        |--------------------------------------------------------------------------
        */

        $usedIds = $updatedOfferings
            ->pluck('id')
            ->all();

        SectionOffering::where(
            'day_id',
            $dayId
        )
            ->where(
                'section_id',
                $sectionId
            )
            ->whereNotIn(
                'id',
                $usedIds
            )
            ->update([
                'is_active' => false,
            ]);

        return $updatedOfferings;
    }

    private function createOffering(
        int $dayId,
        int $sectionId,
        string $startTime,
        int $duration,
        int $maxSeats
    ): SectionOffering {
        $start = Carbon::createFromFormat(
            'H:i',
            $startTime
        );

        $end = $start
            ->copy()
            ->addMinutes($duration);

        return SectionOffering::updateOrCreate(
            [
                'day_id' => $dayId,

                'section_id' => $sectionId,

                'start_time' => $start->format(
                    'H:i:s'
                ),
            ],
            [
                'duration_minutes' => $duration,

                'end_time' => $end->format(
                    'H:i:s'
                ),

                'max_seats' => $maxSeats,

                'is_active' => true,
            ]
        );
    }
}
