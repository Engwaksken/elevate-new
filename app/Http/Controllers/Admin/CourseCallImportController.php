<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseCall;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CourseCallImportController extends Controller
{
    public function template(CourseCall $courseCall)
    {
        return response()->streamDownload(function () use ($courseCall): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_merge(['name', 'email', 'phone', 'submitted_at'],
                $courseCall->questions->map(fn ($question) => 'question_'.$question->id)->all()), ',', '"', '');
            fclose($handle);
        }, 'application-call-'.$courseCall->id.'-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request, CourseCall $courseCall)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $questions = $courseCall->questions;
        $allowed = array_merge(['name', 'email', 'phone', 'submitted_at'],
            $questions->map(fn ($question) => 'question_'.$question->id)->all());
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $rows = [];

        try {
            $headers = fgetcsv($handle, 0, ',', '"', '');
            if ($headers === false) {
                $this->invalid('The CSV is empty. Download and use this call’s template.');
            }
            $headers = array_map(fn ($header) => trim((string) $header), $headers);
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            if (count(array_unique($headers)) !== count($headers)
                || array_diff(['name', 'email'], $headers)
                || array_diff($headers, $allowed)) {
                $this->invalid('Use this call’s template headers, including name and email. Unknown or duplicate columns are not allowed.');
            }

            $line = 1;
            while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $line++;
                if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }
                if (count($values) !== count($headers)) {
                    $this->invalid("Row {$line}: the number of values does not match the headers.");
                }
                if (count($rows) >= 1000) {
                    $this->invalid('Upload at most 1,000 applicants per file.');
                }
                $row = array_combine($headers, array_map(fn ($value) => trim((string) $value), $values));
                $row['email'] = Str::lower($row['email']);
                $rules = [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255'],
                    'phone' => ['nullable', 'string', 'max:30'],
                    'submitted_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
                ];
                foreach ($questions as $question) {
                    $key = 'question_'.$question->id;
                    $rules[$key] = [$question->is_required ? 'required' : 'nullable', 'string', 'max:10000'];
                    if ($question->question_type === 'multiple_choice' && ! empty($row[$key])) {
                        $row[$key] = array_map('trim', explode('|', $row[$key]));
                        $rules[$key] = [$question->is_required ? 'required' : 'nullable', 'array'];
                        $rules[$key.'.*'] = ['string', Rule::in($question->options ?? [])];
                    } elseif ($question->question_type === 'single_choice') {
                        $rules[$key][] = Rule::in($question->options ?? []);
                    } elseif ($question->question_type === 'yes_no') {
                        $rules[$key][] = Rule::in(['Yes', 'No']);
                    } elseif ($question->question_type === 'number') {
                        $rules[$key][] = 'numeric';
                    } elseif ($question->question_type === 'date') {
                        $rules[$key][] = 'date_format:Y-m-d';
                    }
                }
                $validator = Validator::make($row, $rules);
                if ($validator->fails()) {
                    $this->invalid("Row {$line}: ".$validator->errors()->first());
                }
                $row['_line'] = $line;
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }

        if ($rows === []) {
            $this->invalid('Add at least one applicant below the template headers.');
        }

        [$created, $skipped] = DB::transaction(function () use ($rows, $courseCall, $questions): array {
            // Serialize imports for this call; never overwrite an existing application.
            CourseCall::whereKey($courseCall->id)->lockForUpdate()->firstOrFail();
            $created = $skipped = 0;
            foreach ($rows as $row) {
                $user = User::whereRaw('LOWER(email) = ?', [$row['email']])->first();
                if ($user && (! $user->isParticipant() || ! $user->isActive())) {
                    $this->invalid('Row '.$row['_line'].': this email belongs to a non-participant or inactive account.');
                }
                if (! $user) {
                    $user = User::create([
                        'name' => $row['name'], 'email' => $row['email'],
                        'phone' => $row['phone'] ?? null, 'password' => Str::random(64),
                        'user_type' => 'participant', 'status' => 'active',
                    ]);
                    if ($role = Role::where('slug', 'participant')->first()) {
                        $user->roles()->attach($role);
                    }
                }
                if ($courseCall->applications()->where('user_id', $user->id)->exists()) {
                    $skipped++;

                    continue;
                }
                $application = $courseCall->applications()->create([
                    'user_id' => $user->id, 'status' => 'submitted',
                    'submitted_at' => ($row['submitted_at'] ?? '') ?: now(),
                ]);
                foreach ($questions as $question) {
                    $answer = $row['question_'.$question->id] ?? null;
                    $application->answers()->create([
                        'course_call_question_id' => $question->id,
                        'answer_text' => is_array($answer) ? null : $answer,
                        'answer_json' => is_array($answer) ? $answer : null,
                    ]);
                }
                $created++;
            }

            return [$created, $skipped];
        });

        return back()->with('success', "{$created} application(s) imported for review. {$skipped} existing application(s) skipped.");
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
