<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\DataImport;
use App\Services\Import\CsvTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DataImportCentreController extends Controller
{
    use ExportsTables;

    private const MODULES = ['appraisal_kras', 'appraisal_kpis', 'employees', 'mentorship', 'jobs_tracking', 'enrolments'];

    public function index(Request $request)
    {
        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'Data Imports', DataImport::latest(), [
                'File' => 'original_filename',
                'Module' => fn ($i) => \Illuminate\Support\Str::headline((string) $i->module),
                'Rows' => 'total_rows',
                'Status' => fn ($i) => ucfirst((string) $i->status),
                'Uploaded' => 'created_at',
            ]);
        }

        return view('admin.imports.index', [
            'imports' => DataImport::latest()->paginate(30),
            'modules' => self::MODULES,
        ]);
    }

    public function template(string $m, CsvTemplateService $s)
    {
        abort_unless(in_array($m, self::MODULES, true), 404);

        // A UTF-8 BOM lets Excel detect the encoding when the CSV is opened directly.
        return response()->streamDownload(
            fn () => print("\xEF\xBB\xBF".$s->csv($m)),
            $m.'_template.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function upload(Request $r)
    {
        $d = $r->validate([
            'module' => 'required|in:'.implode(',', self::MODULES),
            'file' => 'required|file|mimes:csv,txt,xls,xlsx|max:10240',
        ]);

        $p = $r->file('file')->store('imports', 'local');
        $rows = Excel::toArray([], $r->file('file'))[0] ?? [];
        $header = array_map(fn ($v) => trim((string) $v), array_shift($rows) ?? []);

        $i = DataImport::create([
            'module' => $d['module'],
            'original_filename' => $r->file('file')->getClientOriginalName(),
            'stored_path' => $p,
            'column_mapping' => array_combine($header, $header) ?: [],
            'total_rows' => count($rows),
            'invalid_rows' => count($rows),
            'status' => 'preview',
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('admin.import-centre.preview', $i);
    }

    public function preview(DataImport $dataImport)
    {
        $disk = Storage::disk('local');
        abort_unless($dataImport->stored_path && $disk->exists($dataImport->stored_path), 404, 'The uploaded file is no longer available.');

        $rows = Excel::toArray([], $disk->path($dataImport->stored_path))[0] ?? [];
        $headers = array_map(fn ($v) => trim((string) $v), array_shift($rows) ?? []);

        return view('admin.imports.preview', [
            'import' => $dataImport,
            'headers' => $headers,
            'rows' => array_slice($rows, 0, 25),
        ]);
    }
}
