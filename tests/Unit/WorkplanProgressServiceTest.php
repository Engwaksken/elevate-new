<?php

namespace Tests\Unit;

use App\Models\Milestone;
use App\Models\Workplan;
use App\Services\WorkplanProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkplanProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_weighted_progress_is_calculated(): void
    {
        $workplan=Workplan::create([
            'title'=>'Test',
            'period_type'=>'annual',
        ]);

        Milestone::create([
            'workplan_id'=>$workplan->id,
            'title'=>'A',
            'weight'=>1,
            'progress_percent'=>100,
        ]);

        Milestone::create([
            'workplan_id'=>$workplan->id,
            'title'=>'B',
            'weight'=>1,
            'progress_percent'=>0,
        ]);

        $result=app(WorkplanProgressService::class)->recalculate($workplan);

        $this->assertSame('50.00',(string)$result->progress_percent);
    }
}
