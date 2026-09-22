<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workplan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkplanApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_workplan_can_be_approved(): void
    {
        $user=User::factory()->create(['user_type'=>'staff','status'=>'active']);
        $workplan=Workplan::create([
            'title'=>'Annual Workplan',
            'period_type'=>'annual',
            'status'=>'submitted',
            'created_by'=>$user->id,
        ]);

        $workplan->update([
            'status'=>'approved',
            'approved_by'=>$user->id,
            'approved_at'=>now(),
        ]);

        $this->assertSame('approved',$workplan->fresh()->status);
    }
}
