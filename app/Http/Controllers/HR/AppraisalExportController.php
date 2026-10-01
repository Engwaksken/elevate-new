<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Services\Files\FilePreviewService;
use App\Services\HR\AppraisalWorkbookExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AppraisalExportController extends Controller
{
    public function excel(Request $request, Appraisal $appraisal, AppraisalWorkbookExportService $service, FilePreviewService $previews)
    {
        $this->authorise($appraisal);

        $path=$service->export($appraisal);

        if($request->query('preview')==='1'){
            try {
                return $previews->respondForPath($request,$path,basename($path));
            } finally {
                File::delete($path);
            }
        }

        return response()
            ->download($path,basename($path))
            ->deleteFileAfterSend(true);
    }

    public function print(Appraisal $appraisal)
    {
        $this->authorise($appraisal);

        $appraisal->load([
            'employee.user',
            'cycle',
            'manager',
            'finalisedBy',
            'kpiTemplate.items',
            'kpiScores',
        ]);

        return view('hr.appraisals.print',[
            'appraisal'=>$appraisal,
            'items'=>$appraisal->kpiTemplate?->items ?? collect(),
            'scores'=>$appraisal->kpiScores->keyBy('hr_kpi_template_item_id'),
        ]);
    }

    private function authorise(Appraisal $appraisal): void
    {
        $user=auth()->user();

        abort_unless(
            (int)$appraisal->employee?->user_id===(int)$user->id
            || (int)$appraisal->manager_user_id===(int)$user->id
            || ($user->user_type==='staff' && $user->hasPermission('appraisals.view')),
            403
        );
    }
}
