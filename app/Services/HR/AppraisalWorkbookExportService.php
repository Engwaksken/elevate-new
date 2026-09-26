<?php

namespace App\Services\HR;

use App\Models\Appraisal;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AppraisalWorkbookExportService
{
    private const PERFORMANCE_SHEETS=[
        'Q1'=>'Performance Appraisal- Q1',
        'Q2'=>' Performance Appraisal- Q2',
        'Q3'=>'Performance Appraisal- Q3',
        'Q4'=>'Performance Appraisal- Q4',
    ];

    private const OKR_SHEETS=[
        'Q1'=>'OKR Scorecard Q1 2025.',
        'Q2'=>'OKR Scorecard Q2 2025',
        'Q3'=>'OKR Scorecard Q3 2025.',
        'Q4'=>'OKR Scorecard Q4 2025 ',
    ];

    private const PERFORMANCE_BLOCKS=[
        ['header'=>19,'rating'=>20,'kpis'=>[21,22,23,24,25],'employee_comment'=>26,'manager_comment'=>27],
        ['header'=>29,'rating'=>30,'kpis'=>[31,32,33,34,35,36],'employee_comment'=>37,'manager_comment'=>38],
        ['header'=>40,'rating'=>41,'kpis'=>[42,43,44],'employee_comment'=>45,'manager_comment'=>46],
        ['header'=>48,'rating'=>49,'kpis'=>[50,51,52],'employee_comment'=>53,'manager_comment'=>54],
        ['header'=>56,'rating'=>57,'kpis'=>[58,59,60],'employee_comment'=>61,'manager_comment'=>62],
        ['header'=>64,'rating'=>65,'kpis'=>[66,67,68],'employee_comment'=>69,'manager_comment'=>70],
        ['header'=>72,'rating'=>73,'kpis'=>[74,75,76],'employee_comment'=>77,'manager_comment'=>78],
        ['header'=>80,'rating'=>81,'kpis'=>[82,83,84],'employee_comment'=>85,'manager_comment'=>86],
        ['header'=>88,'rating'=>89,'kpis'=>[90,91,92],'employee_comment'=>93,'manager_comment'=>94],
        ['header'=>96,'rating'=>97,'kpis'=>[98,99,100],'employee_comment'=>101,'manager_comment'=>102],
        ['header'=>104,'rating'=>105,'kpis'=>[106,107,108],'employee_comment'=>109,'manager_comment'=>110],
        ['header'=>112,'rating'=>113,'kpis'=>[114,115,116],'employee_comment'=>117,'manager_comment'=>118],
    ];

    public function export(Appraisal $appraisal): string
    {
        $appraisal->loadMissing([
            'employee.user',
            'cycle',
            'manager',
            'finalisedBy',
            'kpiTemplate.items',
            'kpiScores',
            'kpiWeeklyUpdates',
        ]);

        $templatePath=storage_path('app/appraisal_templates/WITU_KPI_Template.xlsx');

        abort_unless(
            File::exists($templatePath),
            500,
            'The WITU appraisal workbook template is not installed.'
        );

        $spreadsheet=IOFactory::load($templatePath);

        $type=$appraisal->kpiTemplate?->template_type ?? 'performance_appraisal';

        if($type==='okr_scorecard'){
            $this->populateOkr($spreadsheet,$appraisal);
        } elseif($type==='behavioral'){
            $this->populateBehavioral($spreadsheet,$appraisal);
        } else {
            $this->populatePerformanceAppraisal($spreadsheet,$appraisal);
        }

        $this->populateOverallSummary($spreadsheet,$appraisal);

        $dir=storage_path('app/private/appraisal_exports');
        File::ensureDirectoryExists($dir);

        $filename=sprintf(
            'Appraisal_%s_%s.xlsx',
            Str::slug($appraisal->employee?->user?->name ?: 'Staff','_'),
            now()->format('Ymd_His')
        );

        $path=$dir.DIRECTORY_SEPARATOR.$filename;

        IOFactory::createWriter($spreadsheet,'Xlsx')->save($path);

        return $path;
    }

    private function populatePerformanceAppraisal(Spreadsheet $spreadsheet,Appraisal $appraisal): void
    {
        $quarter=$this->quarter($appraisal);
        $sheetName=self::PERFORMANCE_SHEETS[$quarter] ?? self::PERFORMANCE_SHEETS['Q1'];
        $sheet=$spreadsheet->getSheetByName($sheetName)
            ?? $spreadsheet->getSheetByName(trim($sheetName));

        abort_unless($sheet,500,'Performance appraisal sheet was not found in the uploaded WITU workbook.');

        $this->fillAppraisalIdentity($sheet,$appraisal);

        $items=$appraisal->kpiTemplate?->items ?? collect();
        $scores=$appraisal->kpiScores->keyBy('hr_kpi_template_item_id');

        $kras=$items->where('item_type','kra')->values();

        foreach($kras as $kraIndex=>$kra){
            $block=self::PERFORMANCE_BLOCKS[$kraIndex] ?? null;
            if(!$block) break;

            $children=$items
                ->where('item_type','kpi')
                ->where('section',$kra->title)
                ->values();

            $sheet->setCellValue("A{$block['header']}",$kra->title);
            $sheet->setCellValue("C{$block['rating']}",$kra->weight !== null ? (float)$kra->weight/100 : null);

            $kraAgreed=$children
                ->map(fn($item)=>$scores->get($item->id)?->agreed_rating)
                ->filter(fn($value)=>$value!==null);

            if($kraAgreed->isNotEmpty()){
                $sheet->setCellValue("D{$block['rating']}",round($kraAgreed->avg(),2));
            }

            $employeeComments=[];
            $managerComments=[];

            foreach($children as $itemIndex=>$item){
                $row=$block['kpis'][$itemIndex] ?? null;
                if(!$row) break;

                $score=$scores->get($item->id);

                $sheet->setCellValue("A{$row}",$item->title);

                if($score){
                    $this->markRating($sheet,$row,5,$score->employee_rating,5);
                    $this->markRating($sheet,$row,10,$score->manager_rating,5);
                    $this->markRating($sheet,$row,15,$score->agreed_rating,5);

                    if(filled($score->employee_comment)){
                        $employeeComments[]=$item->title.': '.$score->employee_comment;
                    }

                    if(filled($score->manager_comment)){
                        $managerComments[]=$item->title.': '.$score->manager_comment;
                    }
                }
            }

            $sheet->setCellValue(
                "B{$block['employee_comment']}",
                $employeeComments ? implode("\n",$employeeComments) : ''
            );

            $sheet->setCellValue(
                "B{$block['manager_comment']}",
                $managerComments ? implode("\n",$managerComments) : ''
            );

            $sheet->getStyle("B{$block['employee_comment']}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("B{$block['manager_comment']}")->getAlignment()->setWrapText(true);
        }

        $sheet->setCellValue(
            'E14',
            sprintf(
                'Performance: %s%% — %s',
                $appraisal->performance_percent !== null
                    ? number_format((float)$appraisal->performance_percent,1)
                    : '—',
                $this->ratingLabel(
                    $appraisal->performance_percent !== null
                        ? (float)$appraisal->performance_percent
                        : null
                )
            )
        );

        $sheet->setSelectedCell('A1');
    }

    private function populateBehavioral(Spreadsheet $spreadsheet,Appraisal $appraisal): void
    {
        $sheet=$spreadsheet->getSheetByName('Behavioral Xteristics');

        abort_unless($sheet,500,'Behavioural appraisal sheet was not found in the uploaded WITU workbook.');

        $items=$appraisal->kpiTemplate?->items
            ?->where('item_type','behavioral')
            ->reject(fn($item)=>data_get($item->meta,'group'))
            ->values() ?? collect();

        $scores=$appraisal->kpiScores->keyBy('hr_kpi_template_item_id');

        for($row=11;$row<=50;$row++){
            $label=trim((string)$sheet->getCell("B{$row}")->getValue());
            if($label==='') continue;

            $item=$items->first(function($candidate) use($label){
                return $this->normalise($candidate->title)===$this->normalise($label);
            });

            if(!$item) continue;

            $score=$scores->get($item->id);
            if(!$score) continue;

            $this->markBehavioral($sheet,$row,7,$score->employee_rating);
            $this->markBehavioral($sheet,$row,10,$score->manager_rating);
        }

        $sheet->setCellValue('B54',$appraisal->manager_comments);
        $sheet->setCellValue('B57',$appraisal->employee_comments);
        $sheet->setSelectedCell('A1');
    }

    private function populateOkr(Spreadsheet $spreadsheet,Appraisal $appraisal): void
    {
        $quarter=$this->quarter($appraisal);
        $sheetName=self::OKR_SHEETS[$quarter] ?? self::OKR_SHEETS['Q1'];
        $sheet=$spreadsheet->getSheetByName($sheetName)
            ?? $spreadsheet->getSheetByName(trim($sheetName));

        abort_unless($sheet,500,'OKR scorecard sheet was not found in the uploaded WITU workbook.');

        $sheet->setCellValue('B2',$appraisal->employee?->user?->name ?: '');
        $sheet->setCellValue('B3',$appraisal->manager?->name ?: '');

        $items=$appraisal->kpiTemplate?->items
            ?->where('item_type','okr')
            ->values() ?? collect();

        $scores=$appraisal->kpiScores->keyBy('hr_kpi_template_item_id');
        $weekly=$appraisal->kpiWeeklyUpdates
            ->groupBy('hr_kpi_template_item_id')
            ->map->keyBy('week_number');

        $candidateRows=[];
        for($row=7;$row<=$sheet->getHighestRow();$row++){
            if(trim((string)$sheet->getCell("B{$row}")->getValue())!==''){
                $candidateRows[]=$row;
            }
        }

        foreach($items as $index=>$item){
            $row=$candidateRows[$index] ?? null;
            if(!$row) break;

            $sheet->setCellValue("B{$row}",data_get($item->meta,'objective') ?: $item->section ?: $item->title);
            $sheet->setCellValue("C{$row}",data_get($item->meta,'activity'));
            $sheet->setCellValue("D{$row}",data_get($item->meta,'key_result') ?: $item->title);
            $sheet->setCellValue("E{$row}",$item->weight);

            $itemWeekly=$weekly->get($item->id,collect());

            for($week=1;$week<=13;$week++){
                $actualCol=6+(($week-1)*2);
                $commentCol=$actualCol+1;

                if($actualCol>$sheet->getHighestColumnIndex()){
                    break;
                }

                $update=$itemWeekly->get($week);

                $sheet->setCellValueByColumnAndRow(
                    $actualCol,$row,$update?->actual_target
                );

                $sheet->setCellValueByColumnAndRow(
                    $commentCol,$row,$update?->comment
                );
            }

            $score=$scores->get($item->id);
            $last=$sheet->getHighestColumnIndex();

            if($score && $last>=3){
                $sheet->setCellValueByColumnAndRow($last-2,$row,$score->okr_percent);
                $sheet->setCellValueByColumnAndRow($last-1,$row,$score->manager_comment);
                $sheet->setCellValueByColumnAndRow($last,$row,$score->employee_comment);
            }
        }

        $sheet->setSelectedCell('A1');
    }

    private function populateOverallSummary(Spreadsheet $spreadsheet,Appraisal $appraisal): void
    {
        $sheet=$spreadsheet->getSheetByName('OVER ALL SUMMARY');

        if(!$sheet){
            return;
        }

        $sheet->setCellValue('B24',$appraisal->manager_comments);
        $sheet->setCellValue('B26',$appraisal->employee_comments);

        if($appraisal->performance_percent !== null){
            $sheet->setCellValue('G21',round((float)$appraisal->performance_percent/20,2));
        }

        if($appraisal->employee_acknowledgement_name){
            $sheet->setCellValue('B35',$appraisal->employee_acknowledgement_name);
            $sheet->setCellValue('E35',optional($appraisal->employee_acknowledged_at)->format('d M Y'));
        }

        if($appraisal->manager_acknowledgement_name){
            $sheet->setCellValue('B36',$appraisal->manager_acknowledgement_name);
            $sheet->setCellValue('E36',optional($appraisal->manager_acknowledged_at)->format('d M Y'));
        }

        if($appraisal->finalisedBy){
            $sheet->setCellValue('B37',$appraisal->finalisedBy->name);
            $sheet->setCellValue('E37',optional($appraisal->hr_finalised_at)->format('d M Y'));
        }

        $sheet->setCellValue(
            'A28',
            'Overall Performance Rating: '.$this->ratingLabel(
                $appraisal->performance_percent !== null
                    ? (float)$appraisal->performance_percent
                    : null
            )
        );
    }

    private function fillAppraisalIdentity(Worksheet $sheet,Appraisal $appraisal): void
    {
        $sheet->setCellValue('E9',$appraisal->employee?->user?->name ?: '');
        $sheet->setCellValue('C11',$appraisal->employee?->employee_number ?: '');
        $sheet->setCellValue('G12',optional($appraisal->cycle?->start_date)->format('d M Y'));
        $sheet->setCellValue('K12',optional($appraisal->cycle?->end_date)->format('d M Y'));
        $sheet->setCellValue('C13',$appraisal->manager?->name ?: '');
        $sheet->setCellValue('O13',optional($appraisal->manager_submitted_at)->format('d M Y'));
    }

    private function markRating(
        Worksheet $sheet,
        int $row,
        int $startColumn,
        mixed $rating,
        int $max
    ): void {
        if($rating===null) return;

        $value=(int)round((float)$rating);

        if($value<1 || $value>$max) return;

        $sheet->setCellValueByColumnAndRow($startColumn+$value-1,$row,'X');
    }

    private function markBehavioral(
        Worksheet $sheet,
        int $row,
        int $startColumn,
        mixed $rating
    ): void {
        if($rating===null) return;

        $value=(int)round((float)$rating);

        $column=match($value){
            3=>$startColumn,
            2=>$startColumn+1,
            1=>$startColumn+2,
            default=>null,
        };

        if($column){
            $sheet->setCellValueByColumnAndRow($column,$row,'X');
        }
    }

    private function quarter(Appraisal $appraisal): string
    {
        $text=Str::upper(
            trim(($appraisal->kpiTemplate?->quarter ?? '').' '.($appraisal->cycle?->name ?? ''))
        );

        foreach(['Q1','Q2','Q3','Q4'] as $quarter){
            if(Str::contains($text,$quarter)){
                return $quarter;
            }
        }

        $month=$appraisal->cycle?->start_date?->month ?? now()->month;

        return match(true){
            $month<=3=>'Q1',
            $month<=6=>'Q2',
            $month<=9=>'Q3',
            default=>'Q4',
        };
    }

    private function ratingLabel(?float $percent): string
    {
        if($percent===null) return 'Not yet rated';

        return match(true){
            $percent>=90=>'Outstanding',
            $percent>=80=>'Exceeds Expectations',
            $percent>=60=>'Meets Expectations',
            $percent>=40=>'Partly Met Expectations',
            default=>'Unacceptable',
        };
    }

    private function normalise(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/',' ')
            ->squish()
            ->toString();
    }
}
