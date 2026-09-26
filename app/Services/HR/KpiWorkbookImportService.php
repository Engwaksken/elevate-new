<?php

namespace App\Services\HR;

use App\Models\HrKpiTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class KpiWorkbookImportService
{
    public function import(UploadedFile $file, array $data): HrKpiTemplate
    {
        $workbook=IOFactory::load($file->getRealPath());
        $worksheets=$workbook->getAllSheets();
        $sheetIndex=$this->resolveSheetIndex($worksheets,$data['template_type']);
        $sheet=$worksheets[$sheetIndex] ?? $worksheets[0];
        $rows=$sheet->toArray(null,true,true,false);

        $template=HrKpiTemplate::create([
            'name'=>$data['name'],
            'source_file'=>$file->getClientOriginalName(),
            'source_sheet'=>$sheet->getTitle(),
            'template_type'=>$data['template_type'],
            'quarter'=>$data['quarter'] ?? null,
            'year'=>$data['year'] ?? null,
            'uploaded_by'=>auth()->id(),
            'is_active'=>true,
        ]);

        $items=match($data['template_type']){
            'okr_scorecard'=>$this->parseOkr($rows),
            'behavioral'=>$this->parseBehavioral($rows),
            default=>$this->parsePerformanceAppraisal($rows),
        };

        foreach($items as $position=>$item){
            $template->items()->create($item+['position'=>$position+1]);
        }

        return $template->load('items');
    }

    private function parsePerformanceAppraisal(array $rows): array
    {
        $items=[];
        $currentSection=null;
        $currentWeight=null;

        foreach($rows as $row){
            $a=trim((string)($row[0] ?? ''));
            $b=trim((string)($row[1] ?? ''));
            $c=$row[2] ?? null;
            $text=$a!=='' ? $a : $b;

            if($text==='') continue;
            $lower=Str::lower($text);

            if(
                Str::contains($lower,['kra ','key result','key result area'])
                && !Str::startsWith($lower,'kpi')
            ){
                $currentSection=preg_replace('/^\d+\.\s*/','',$text);
                $currentWeight=is_numeric($c) ? ((float)$c<=1 ? (float)$c*100 : (float)$c) : null;

                $items[]=[
                    'item_type'=>'kra',
                    'section'=>$currentSection,
                    'title'=>$currentSection,
                    'weight'=>$currentWeight,
                    'meta'=>['rating_scale'=>[1,2,3,4,5]],
                ];
                continue;
            }

            if(Str::startsWith(Str::upper($text),'KPI') || Str::startsWith(Str::upper($text),'KP1')){
                $title=trim(preg_replace('/^KP[I1]\s*\d*\s*:\s*/i','',$text) ?? $text);
                if($title!==''){
                    $items[]=[
                        'item_type'=>'kpi',
                        'section'=>$currentSection,
                        'title'=>$title,
                        'weight'=>null,
                        'meta'=>[
                            'rating_scale'=>[1,2,3,4,5],
                            'employee_rating'=>true,
                            'manager_rating'=>true,
                            'agreed_rating'=>true,
                        ],
                    ];
                }
            }
        }

        return $items;
    }

    private function parseOkr(array $rows): array
    {
        $items=[];

        foreach($rows as $row){
            $objective=trim((string)($row[1] ?? ''));
            $activity=trim((string)($row[2] ?? ''));
            $keyResult=trim((string)($row[3] ?? ''));
            $weight=$row[4] ?? null;

            if($objective==='' && $activity==='' && $keyResult==='') continue;

            $combined=trim(implode(' — ',array_filter([$objective,$activity,$keyResult])));
            $lower=Str::lower($combined);

            if(
                $combined==='' ||
                Str::contains($lower,['objective activities key result','weighting sumcheck','other core job responsibilities'])
            ) continue;

            $items[]=[
                'item_type'=>'okr',
                'section'=>$objective ?: null,
                'title'=>$combined,
                'weight'=>is_numeric($weight) ? (float)$weight : null,
                'meta'=>[
                    'objective'=>$objective ?: null,
                    'activity'=>$activity ?: null,
                    'key_result'=>$keyResult ?: null,
                    'weekly_tracking'=>true,
                ],
            ];
        }

        return $items;
    }

    private function parseBehavioral(array $rows): array
    {
        $items=[];
        $section=null;

        foreach($rows as $row){
            $a=trim((string)($row[0] ?? ''));
            $b=trim((string)($row[1] ?? ''));

            if($a!=='' && is_numeric($a) && $b!==''){
                $section=$b;
                $items[]=[
                    'item_type'=>'behavioral',
                    'section'=>$section,
                    'title'=>$section,
                    'weight'=>null,
                    'meta'=>['group'=>true,'rating_scale'=>['Always','Occasionally','Never']],
                ];
                continue;
            }

            if($b!=='' && $section){
                $items[]=[
                    'item_type'=>'behavioral',
                    'section'=>$section,
                    'title'=>$b,
                    'weight'=>null,
                    'meta'=>['rating_scale'=>['Always','Occasionally','Never']],
                ];
            }
        }

        return $items;
    }

    private function resolveSheetIndex(array $worksheets,string $type): int
    {
        $needles=match($type){
            'okr_scorecard'=>['okr scorecard'],
            'behavioral'=>['behavioral competencies'],
            default=>['assessment against key result areas'],
        };

        foreach($worksheets as $index=>$sheet){
            $sample=Str::lower(json_encode(array_slice($sheet->toArray(null,true,true,false),0,35)) ?: '');
            foreach($needles as $needle){
                if(Str::contains($sample,$needle)) return $index;
            }
        }

        return 0;
    }
}
