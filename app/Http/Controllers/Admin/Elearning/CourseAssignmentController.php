<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class CourseAssignmentController extends Controller
{
    public function edit(Course $course)
    {
        $roleId = Role::where('slug', 'instructor')->value('id');

        return view('admin.elearning.assignments.edit', [
            'course' => $course->load(['instructors','cohorts']),
            'instructors' => User::where('user_type', 'staff')
                ->when($roleId, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('roles.id', $roleId)))
                ->orderBy('name')
                ->get(),
            'cohorts' => Cohort::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Course $course)
    {
        $data = $request->validate([
            'instructors' => ['nullable','array'],
            'instructors.*' => ['integer','exists:users,id'],
            'lead_instructor_id' => ['nullable','integer','exists:users,id'],
            'cohorts' => ['nullable','array'],
            'cohorts.*' => ['integer','exists:cohorts,id'],
        ]);

        $sync = [];

        foreach ($data['instructors'] ?? [] as $userId) {
            $sync[$userId] = [
                'is_lead' => (int) $userId === (int) ($data['lead_instructor_id'] ?? 0),
            ];
        }

        $course->instructors()->sync($sync);
        $course->cohorts()->sync($data['cohorts'] ?? []);

        return back()->with('success', 'Course assignments updated.');
    }
}
