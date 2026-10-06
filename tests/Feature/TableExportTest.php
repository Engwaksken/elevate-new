<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Support\Export\TableExport;
use App\Support\Export\TableExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableExportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']));

        return $user;
    }

    public function test_csv_export_streams_bom_headings_and_rows(): void
    {
        $export = TableExport::make('People list')
            ->columns([
                'Name' => 'name',
                'Nested' => 'meta.city',
                'Active' => fn ($row) => $row['active'],
            ])
            ->source(collect([
                ['name' => 'Ada', 'meta' => ['city' => 'Kampala'], 'active' => true],
                ['name' => '=cmd()', 'meta' => ['city' => null], 'active' => false],
            ]));

        $response = app(TableExporter::class)->csv($export);
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Name,Nested,Active', $csv);
        $this->assertStringContainsString('Ada,Kampala,Yes', $csv);
        $this->assertStringContainsString("'=cmd()", $csv, 'Formula-like cells must be neutralised.');
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('people-list-', $response->headers->get('Content-Disposition'));
    }

    public function test_csv_safe_leaves_negative_numbers_alone(): void
    {
        $this->assertSame('-12.5', TableExporter::csvSafe('-12.5'));
        $this->assertSame("'-x", TableExporter::csvSafe('-x'));
        $this->assertSame("'@SUM(A1)", TableExporter::csvSafe('@SUM(A1)'));
    }

    public function test_query_sources_are_chunked_and_preserve_order(): void
    {
        foreach (range(1, 7) as $i) {
            Branch::create(['name' => sprintf('Branch %02d', $i), 'country' => 'Uganda', 'is_active' => true]);
        }

        $export = TableExport::make('Branches')
            ->columns(['Name' => 'name'])
            ->source(Branch::query()->orderByDesc('name'))
            ->chunkSize(3);

        $names = [];
        foreach ($export->rows() as $row) {
            $names[] = $export->mapRow($row)[0];
        }

        $this->assertCount(7, $names);
        $this->assertSame('Branch 07', $names[0]);
        $this->assertSame('Branch 01', $names[6]);
    }

    public function test_pdf_export_is_capped_and_returns_pdf(): void
    {
        $export = TableExport::make('Big list')
            ->columns(['N' => fn ($row) => $row])
            ->source(range(1, 30))
            ->pdfLimit(10)
            ->filters(['Status' => 'active', 'Empty' => '']);

        $this->assertSame(['Status' => 'active'], $export->appliedFilters());

        $response = app(TableExporter::class)->pdf($export);

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_format_helper_handles_common_types(): void
    {
        $this->assertSame('', TableExport::format(null));
        $this->assertSame('No', TableExport::format(false));
        $this->assertSame('2026-01-02', TableExport::format(\Carbon\Carbon::parse('2026-01-02')));
        $this->assertSame('2026-01-02 10:30', TableExport::format(\Carbon\Carbon::parse('2026-01-02 10:30')));
        $this->assertSame('a, b', TableExport::format(['a', 'b']));
    }

    public function test_export_component_preserves_the_current_query_string(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('admin.branches.index', ['search' => 'Kam', 'page' => 2]))
            ->assertOk()
            ->assertSee('export=csv', false)
            ->assertSee('search=Kam', false)
            ->assertDontSee('page=2&amp;export', false);
    }

    public function test_branches_page_exports_only_filtered_rows_as_csv(): void
    {
        Branch::create(['name' => 'Kampala Hub', 'country' => 'Uganda', 'is_active' => true]);
        Branch::create(['name' => 'Gulu Hub', 'country' => 'Uganda', 'is_active' => false]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.branches.index', ['status' => 'active', 'export' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Kampala Hub', $csv);
        $this->assertStringNotContainsString('Gulu Hub', $csv);
    }

    public function test_branches_page_exports_pdf(): void
    {
        Branch::create(['name' => 'Kampala Hub', 'country' => 'Uganda', 'is_active' => true]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.branches.index', ['export' => 'pdf']));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_unknown_export_format_falls_back_to_the_html_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.branches.index', ['export' => 'xlsx']))
            ->assertOk()
            ->assertSee('Branches');
    }

    public function test_export_respects_route_authorization(): void
    {
        $this->get(route('admin.branches.index', ['export' => 'csv']))->assertRedirect();

        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $response = $this->actingAs($participant)->get(route('admin.branches.index', ['export' => 'csv']));
        $this->assertNotSame(200, $response->getStatusCode());
    }
    public function test_filter_labels_resolve_ids_to_names(): void
    {
        $branch = Branch::create(['name' => 'Kampala Hub', 'country' => 'Uganda', 'is_active' => true]);
        $user = User::factory()->create(['name' => 'Jane Assignee']);

        $this->assertSame('Branch', \App\Support\Export\ExportFilterLabels::label('branch_id'));
        $this->assertSame('Assignee', \App\Support\Export\ExportFilterLabels::label('assignee_id'));
        $this->assertSame('Manager', \App\Support\Export\ExportFilterLabels::label('manager_user_id'));
        $this->assertSame('Kampala Hub', \App\Support\Export\ExportFilterLabels::value('branch_id', (string) $branch->id));
        $this->assertSame('Jane Assignee', \App\Support\Export\ExportFilterLabels::value('assignee_id', $user->id));
        $this->assertSame('users', \App\Support\Export\ExportFilterLabels::value('module', 'users'));
        $this->assertSame('999', \App\Support\Export\ExportFilterLabels::value('branch_id', '999'));
        $this->assertSame('pending', \App\Support\Export\ExportFilterLabels::value('status', 'pending'));
    }

    public function test_assessments_page_renders(): void
    {
        $course = \App\Models\Course::create(['title' => 'Foundation', 'status' => 'published', 'pass_mark' => 60]);
        $assessment = \App\Models\Assessment::create(['course_id' => $course->id, 'title' => 'Quiz 1', 'type' => 'quiz', 'max_attempts' => 3, 'is_published' => true, 'pass_mark' => 60]);
        \App\Models\AssessmentQuestion::create(['assessment_id' => $assessment->id, 'question_type' => 'multiple_choice', 'question_text' => 'Q?', 'marks' => 1, 'position' => 1]);

        $this->actingAs($this->admin())
            ->get(route('admin.elearning.assessments.index', $course))
            ->assertOk()
            ->assertSee('Quiz 1');
    }
}
