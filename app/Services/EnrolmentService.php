<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrolment;
use App\Models\User;

class EnrolmentService
{
    /**
     * Enrol a user in a course and, in turn, that course's compulsory courses.
     * Recursion terminates because a course is only cascaded the first time its
     * enrolment is created.
     */
    public function enrol(User $user, Course $course, array $attributes = []): Enrolment
    {
        $enrolment = Enrolment::firstOrCreate(
            ['course_id' => $course->id, 'user_id' => $user->id],
            array_merge(['status' => 'enrolled', 'enrolled_at' => now()], $attributes)
        );

        if ($enrolment->wasRecentlyCreated) {
            foreach ($course->compulsoryCourses()->get() as $compulsory) {
                $this->enrol($user, $compulsory);
            }
        }

        return $enrolment;
    }
}
