<?php

namespace Tests\Feature;

use App\Models\LearningFile;
use App\Models\ResumeUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class FilePreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->staff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
    }

    private function storeFile(string $name, string $contents): LearningFile
    {
        Storage::disk('local')->put('learning/'.$name, $contents);

        return LearningFile::create([
            'original_name' => $name,
            'stored_name' => $name,
            'disk' => 'local',
            'path' => 'learning/'.$name,
            'size_bytes' => strlen($contents),
        ]);
    }

    private function previewUrl(LearningFile $file, string $mode = '1'): string
    {
        return route('learning.files.download', [$file, 'preview' => $mode]);
    }

    public function test_spreadsheet_renders_every_sheet_as_escaped_html(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Scores')->fromArray([
            ['Name', 'Score'],
            ['<script>alert(1)</script>', 42],
        ]);
        $spreadsheet->createSheet()->setTitle('Notes')->setCellValue('A1', 'Second sheet');

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($spreadsheet))->save($path);
        $file = $this->storeFile('scores.xlsx', file_get_contents($path));
        @unlink($path);

        $this->actingAs($this->staff)->get($this->previewUrl($file))
            ->assertOk()
            ->assertSee('Scores')
            ->assertSee('Notes')
            ->assertSee('Second sheet')
            ->assertSee('42')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_csv_renders_as_table(): void
    {
        $file = $this->storeFile('people.csv', "name,town\nAmina,Gulu\n");

        $this->actingAs($this->staff)->get($this->previewUrl($file))
            ->assertOk()
            ->assertSee('Amina')
            ->assertSee('Gulu');
    }

    public function test_word_document_is_converted_to_pdf_with_dompdf(): void
    {
        $word = new PhpWord();
        $word->addSection()->addText('Hello from the appraisal handbook');
        $path = tempnam(sys_get_temp_dir(), 'docx');
        WordIOFactory::createWriter($word, 'Word2007')->save($path);
        $file = $this->storeFile('handbook.docx', file_get_contents($path));
        @unlink($path);

        $this->actingAs($this->staff)->get($this->previewUrl($file))
            ->assertOk()
            ->assertSee('<iframe', false);

        $raw = $this->actingAs($this->staff)->get($this->previewUrl($file, 'raw'));
        $raw->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', $raw->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', file_get_contents($raw->baseResponse->getFile()->getPathname()));
    }

    public function test_pdf_and_image_are_served_inline(): void
    {
        $pdf = $this->storeFile('guide.pdf', "%PDF-1.4\n%%EOF");
        $raw = $this->actingAs($this->staff)->get($this->previewUrl($pdf, 'raw'));
        $raw->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline', $raw->headers->get('Content-Disposition'));

        $png = $this->storeFile('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
        $this->actingAs($this->staff)->get($this->previewUrl($png))->assertOk()->assertSee('<img', false);
        $this->actingAs($this->staff)->get($this->previewUrl($png, 'raw'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_text_is_escaped_and_unsupported_types_offer_download(): void
    {
        $text = $this->storeFile('notes.txt', '<b>bold</b> notes');
        $this->actingAs($this->staff)->get($this->previewUrl($text))
            ->assertOk()
            ->assertSee('&lt;b&gt;bold&lt;/b&gt; notes', false);

        $zip = $this->storeFile('bundle.zip', 'PK');
        $this->actingAs($this->staff)->get($this->previewUrl($zip))
            ->assertOk()
            ->assertSee("Preview isn't available", false);
        $this->actingAs($this->staff)->get($this->previewUrl($zip, 'raw'))->assertNotFound();
    }

    public function test_embedded_preview_hides_its_own_header(): void
    {
        $file = $this->storeFile('people.csv', "name\nAmina\n");

        $this->actingAs($this->staff)->get($this->previewUrl($file))
            ->assertOk()
            ->assertSee('<header>', false);

        $embedded = $this->actingAs($this->staff)
            ->get(route('learning.files.download', [$file, 'preview' => 1, 'embed' => 1]))
            ->assertOk()
            ->assertSee('class="embedded"', false)
            ->assertDontSee('<header>', false)
            ->assertSee('Amina');

        $this->assertStringNotContainsString('embed=1', $embedded->viewData('downloadUrl'));
        $this->assertStringNotContainsString('preview=', $embedded->viewData('downloadUrl'));
    }

    public function test_download_without_preview_is_still_an_attachment(): void
    {
        $file = $this->storeFile('people.csv', "name\nAmina\n");

        $response = $this->actingAs($this->staff)->get(route('learning.files.download', $file));
        $response->assertOk();
        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_preview_keeps_the_download_authorisation(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        Storage::disk('local')->put('resumes/cv.txt', 'My CV');

        $upload = ResumeUpload::create([
            'user_id' => $owner->id,
            'original_name' => 'cv.txt',
            'stored_name' => 'cv.txt',
            'mime_type' => 'text/plain',
            'file_size' => 5,
            'path' => 'resumes/cv.txt',
        ]);

        $url = route('career.resume.upload.original', [$upload, 'preview' => 1]);

        $this->actingAs($other)->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertOk()->assertSee('My CV');
    }
}
