<?php

namespace Tests\Feature;

use App\Models\Lesson\QuestionTitle;
use App\Models\Lesson\ValueCategory;
use App\Models\MasterData\Category;
use App\Models\User;
use Dompdf\Dompdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use ZipArchive;

class QuestionDocumentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_and_commit_docx_questions_atomically(): void
    {
        [$admin, $questionTitle] = $this->context();
        $document = <<<'TEXT'
BAGIAN: 1
KATEGORI: TWK
SOAL 1: Dasar negara Indonesia adalah ...
A. Pancasila
B. UUD sementara
C. Peraturan daerah
D. Keputusan menteri
KUNCI: A
PEMBAHASAN: Pancasila merupakan dasar negara Indonesia.

BAGIAN: 2
KATEGORI: TKP
SOAL 2: Rekan kerja meminta bantuan saat tugas utama Anda hampir selesai. Sikap terbaik adalah ...
A. Menolak tanpa penjelasan
B. Membantu setelah memastikan prioritas tugas utama
C. Meninggalkan seluruh tugas utama
D. Mengabaikan permintaan
KUNCI: B
POIN A: 1
POIN B: 5
POIN C: 2
POIN D: 1
PEMBAHASAN: Menjaga prioritas sambil tetap kolaboratif adalah respons paling efektif.
TEXT;

        $response = $this->actingAs($admin)->post(
            "/admin/question-titles/{$questionTitle->id}/questions/import/preview",
            ['document' => $this->docx($document)],
        );

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Lesson/Question/Import')
            ->where('preview.valid', true)
            ->where('preview.total', 2)
            ->where('preview.questions.0.value_category', 'TWK')
            ->where('preview.questions.1.points.B', 5)
        );

        $token = $response->viewData('page')['props']['preview']['token'];

        $this->actingAs($admin)
            ->post("/admin/question-titles/{$questionTitle->id}/questions/import", ['token' => $token])
            ->assertRedirect("/admin/question-titles/{$questionTitle->id}/questions")
            ->assertSessionHas('success', '2 soal berhasil diimpor.');

        $this->assertDatabaseCount('questions', 2);
        $this->assertDatabaseHas('questions', [
            'question_title_id' => $questionTitle->id,
            'section' => 2,
            'option_has_point' => 1,
            'option_2_point' => 5,
            'answer' => 2,
            'discussion' => '<p>Menjaga prioritas sambil tetap kolaboratif adalah respons paling efektif.</p>',
        ]);

        $this->actingAs($admin)
            ->post("/admin/question-titles/{$questionTitle->id}/questions/import", ['token' => $token])
            ->assertSessionHasErrors(['import']);
        $this->assertDatabaseCount('questions', 2);
    }

    public function test_admin_can_preview_and_commit_a_text_pdf(): void
    {
        [$admin, $questionTitle] = $this->context();
        $document = <<<'TEXT'
BAGIAN: 1
KATEGORI: TWK
SOAL: Semboyan negara Indonesia adalah ...
A. Tut Wuri Handayani
B. Bhinneka Tunggal Ika
C. Ing Ngarsa Sung Tulada
D. Jalesveva Jayamahe
KUNCI: B
PEMBAHASAN: Bhinneka Tunggal Ika adalah semboyan negara Indonesia.
TEXT;

        $response = $this->actingAs($admin)->post(
            "/admin/question-titles/{$questionTitle->id}/questions/import/preview",
            ['document' => $this->pdf($document)],
        );

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Lesson/Question/Import')
            ->where('preview.valid', true)
            ->where('preview.total', 1)
            ->where('preview.questions.0.answer', 'B')
        );

        $token = $response->viewData('page')['props']['preview']['token'];
        $this->actingAs($admin)
            ->post("/admin/question-titles/{$questionTitle->id}/questions/import", ['token' => $token])
            ->assertRedirect("/admin/question-titles/{$questionTitle->id}/questions");

        $this->assertDatabaseHas('questions', [
            'question_title_id' => $questionTitle->id,
            'section' => 1,
            'answer' => 2,
            'discussion' => '<p>Bhinneka Tunggal Ika adalah semboyan negara Indonesia.</p>',
        ]);
    }

    public function test_invalid_document_is_previewed_without_writing_to_database(): void
    {
        [$admin, $questionTitle] = $this->context();
        $document = <<<'TEXT'
BAGIAN: 1
KATEGORI: Tidak Ada
SOAL: Soal tanpa pembahasan
A. Satu
B. Dua
C. Tiga
D. Empat
KUNCI: A
TEXT;

        $this->actingAs($admin)
            ->post(
                "/admin/question-titles/{$questionTitle->id}/questions/import/preview",
                ['document' => $this->docx($document)],
            )
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('preview.valid', false)
                ->where('preview.token', null)
                ->where('preview.questions.0.errors', fn ($errors) => collect($errors)->contains(
                    fn (string $error) => str_contains($error, 'tidak ditemukan')
                ) && collect($errors)->contains('PEMBAHASAN wajib diisi agar koreksi jawaban tidak kosong.'))
            );

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_non_admin_cannot_access_question_document_import(): void
    {
        [, $questionTitle] = $this->context();
        $member = $this->user('member-import@example.test', 2);

        $this->actingAs($member)
            ->get("/admin/question-titles/{$questionTitle->id}/questions/import")
            ->assertRedirect(route('user.user.dashboard'));
    }

    public function test_preview_token_is_bound_to_the_admin_who_uploaded_the_document(): void
    {
        [$admin, $questionTitle] = $this->context();
        $otherAdmin = $this->user('other-question-import-admin@example.test', 1);
        $document = <<<'TEXT'
BAGIAN: 1
KATEGORI: TWK
SOAL: Keputusan bersama sebaiknya dilaksanakan dengan ...
A. Tanggung jawab
B. Paksaan
C. Keraguan
D. Kepentingan pribadi
KUNCI: A
PEMBAHASAN: Keputusan bersama mengikat peserta untuk melaksanakannya secara bertanggung jawab.
TEXT;

        $preview = $this->actingAs($admin)->post(
            "/admin/question-titles/{$questionTitle->id}/questions/import/preview",
            ['document' => $this->docx($document)],
        );
        $token = $preview->viewData('page')['props']['preview']['token'];

        $this->actingAs($otherAdmin)
            ->post("/admin/question-titles/{$questionTitle->id}/questions/import", ['token' => $token])
            ->assertSessionHasErrors(['import']);
        $this->assertDatabaseCount('questions', 0);

        $this->actingAs($admin)
            ->post("/admin/question-titles/{$questionTitle->id}/questions/import", ['token' => $token])
            ->assertRedirect("/admin/question-titles/{$questionTitle->id}/questions");
        $this->assertDatabaseCount('questions', 1);
    }

    public function test_admin_can_download_docx_template_for_question_title(): void
    {
        [$admin, $questionTitle] = $this->context();

        $response = $this->actingAs($admin)
            ->get("/admin/question-titles/{$questionTitle->id}/questions/import/template");

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertStringContainsString('format-soal-', (string) $response->headers->get('content-disposition'));
    }

    public function test_admin_can_preview_and_import_tka_format_questions(): void
    {
        $admin = $this->user('tka-admin@example.test', 1);
        $category = Category::create(['name' => 'TKA']);
        $catIndonesia = ValueCategory::create([
            'category_id' => $category->id,
            'name' => 'Bahasa Indonesia',
            'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE,
            'section' => 1,
        ]);
        $questionTitle = QuestionTitle::create([
            'category_id' => $category->id,
            'name' => 'Bank Soal TKA Soshum',
            'total_choices' => 5,
            'total_section' => 5,
            'add_value_category' => 1,
            'assessment_type' => QuestionTitle::ASSESSMENT_PER_CATEGORY,
            'show_answer' => 1,
            'minimum_grade' => 0,
        ]);

        $document = <<<'TEXT'
BAGIAN: 1
KATEGORI: Bahasa Indonesia
SOAL 1: Tuliskan pertanyaan di sini...
A. Pilihan pertama
B. Pilihan kedua
C. Pilihan ketiga
D. Pilihan keempat
E. Pilihan kelima
KUNCI: A
PEMBAHASAN: Penjelasan jawaban.
TEXT;

        $response = $this->actingAs($admin)->post(
            "/admin/question-titles/{$questionTitle->id}/questions/import/preview",
            ['document' => $this->docx($document)],
        );

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Lesson/Question/Import')
            ->where('preview.valid', true)
            ->where('preview.total', 1)
            ->where('preview.questions.0.section', 1)
            ->where('preview.questions.0.value_category', 'Bahasa Indonesia')
            ->where('preview.questions.0.answer', 'A')
        );

        $token = $response->viewData('page')['props']['preview']['token'];
        $this->actingAs($admin)
            ->post("/admin/question-titles/{$questionTitle->id}/questions/import", ['token' => $token])
            ->assertRedirect("/admin/question-titles/{$questionTitle->id}/questions");

        $this->assertDatabaseHas('questions', [
            'question_title_id' => $questionTitle->id,
            'value_category_id' => $catIndonesia->id,
            'section' => 1,
            'answer' => 1,
            'option_5' => '<p>Pilihan kelima</p>',
            'discussion' => '<p>Penjelasan jawaban.</p>',
        ]);
    }

    public function test_admin_can_preview_and_import_utbk_toefl_and_toeic_formats(): void
    {
        $admin = $this->user('multi-admin@example.test', 1);

        // UTBK
        $utbkCategory = Category::create(['name' => 'UTBK']);
        $utbkVal = ValueCategory::create([
            'category_id' => $utbkCategory->id,
            'name' => 'Penalaran Umum',
            'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE,
            'section' => 1,
        ]);
        $utbkTitle = QuestionTitle::create([
            'category_id' => $utbkCategory->id,
            'name' => 'Bank Soal UTBK SNBT',
            'total_choices' => 5,
            'total_section' => 7,
            'add_value_category' => 1,
            'assessment_type' => QuestionTitle::ASSESSMENT_PER_CATEGORY,
            'show_answer' => 1,
            'minimum_grade' => 0,
        ]);

        $utbkDoc = <<<'TEXT'
BAGIAN: 1
KATEGORI: Penalaran Umum
SOAL 1: Tuliskan pertanyaan penalaran umum di sini...
A. Pilihan pertama
B. Pilihan kedua
C. Pilihan ketiga
D. Pilihan keempat
E. Pilihan kelima
KUNCI: C
PEMBAHASAN: Penjelasan pembahasan UTBK.
TEXT;

        $resUtbk = $this->actingAs($admin)->post(
            "/admin/question-titles/{$utbkTitle->id}/questions/import/preview",
            ['document' => $this->docx($utbkDoc)],
        );
        $resUtbk->assertOk()->assertInertia(fn (Assert $page) => $page->where('preview.valid', true));
        $this->actingAs($admin)->post("/admin/question-titles/{$utbkTitle->id}/questions/import", [
            'token' => $resUtbk->viewData('page')['props']['preview']['token'],
        ]);
        $this->assertDatabaseHas('questions', [
            'question_title_id' => $utbkTitle->id,
            'value_category_id' => $utbkVal->id,
            'answer' => 3,
        ]);

        // TOEFL with English labels
        $toeflCategory = Category::create(['name' => 'TOEFL']);
        $toeflVal = ValueCategory::create([
            'category_id' => $toeflCategory->id,
            'name' => 'Listening Comprehension',
            'assessment_type' => ValueCategory::ASSESSMENT_CORRECT_COUNT,
            'section' => 1,
        ]);
        $toeflTitle = QuestionTitle::create([
            'category_id' => $toeflCategory->id,
            'name' => 'Bank Soal TOEFL Prediction',
            'total_choices' => 4,
            'total_section' => 3,
            'add_value_category' => 1,
            'assessment_type' => QuestionTitle::ASSESSMENT_PER_CATEGORY,
            'show_answer' => 1,
            'minimum_grade' => 0,
        ]);

        $toeflDoc = <<<'TEXT'
SECTION: 1
CATEGORY: Listening Comprehension
QUESTION 1: What does the woman imply?
A. First option
B. Second option
C. Third option
D. Fourth option
KEY: B
EXPLANATION: The woman implies the second option.
TEXT;

        $resToefl = $this->actingAs($admin)->post(
            "/admin/question-titles/{$toeflTitle->id}/questions/import/preview",
            ['document' => $this->docx($toeflDoc)],
        );
        $resToefl->assertOk()->assertInertia(fn (Assert $page) => $page->where('preview.valid', true));
        $this->actingAs($admin)->post("/admin/question-titles/{$toeflTitle->id}/questions/import", [
            'token' => $resToefl->viewData('page')['props']['preview']['token'],
        ]);
        $this->assertDatabaseHas('questions', [
            'question_title_id' => $toeflTitle->id,
            'value_category_id' => $toeflVal->id,
            'answer' => 2,
        ]);
    }

    public function test_admin_can_preview_and_import_generated_template_directly(): void
    {
        [$admin, $questionTitle] = $this->context();
        $templateResponse = $this->actingAs($admin)->get(
            "/admin/question-titles/{$questionTitle->id}/questions/import/template"
        );
        $templateResponse->assertOk();

        ob_start();
        $templateResponse->sendContent();
        $binary = ob_get_clean();

        $path = tempnam(sys_get_temp_dir(), 'test-template-').'.docx';
        file_put_contents($path, $binary);
        $file = new UploadedFile($path, 'template.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $previewResponse = $this->actingAs($admin)->post(
            "/admin/question-titles/{$questionTitle->id}/questions/import/preview",
            ['document' => $file]
        );

        $previewResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('preview.valid', true)
            ->where('preview.total', 3)
        );

        $token = $previewResponse->viewData('page')['props']['preview']['token'];
        $commitResponse = $this->actingAs($admin)->post(
            "/admin/question-titles/{$questionTitle->id}/questions/import",
            ['token' => $token]
        );

        $commitResponse->assertRedirect("/admin/question-titles/{$questionTitle->id}/questions");
        $this->assertEquals(3, \App\Models\Lesson\Question::where('question_title_id', $questionTitle->id)->count());
    }

    private function context(): array
    {
        $admin = $this->user('question-import-admin@example.test', 1);
        $category = Category::create(['name' => 'IMPORT CPNS']);
        ValueCategory::create([
            'category_id' => $category->id,
            'name' => 'TWK',
            'assessment_type' => ValueCategory::ASSESSMENT_CORRECT_COUNT,
            'section' => 1,
        ]);
        ValueCategory::create([
            'category_id' => $category->id,
            'name' => 'TKP',
            'assessment_type' => ValueCategory::ASSESSMENT_OPTION_POINTS,
            'section' => 2,
        ]);
        $questionTitle = QuestionTitle::create([
            'category_id' => $category->id,
            'name' => 'Bank Impor Dokumen',
            'total_choices' => 4,
            'total_section' => 2,
            'add_value_category' => 1,
            'assessment_type' => QuestionTitle::ASSESSMENT_PER_CATEGORY,
            'show_answer' => 1,
            'minimum_grade' => 0,
        ]);

        return [$admin, $questionTitle];
    }

    private function user(string $email, int $level): User
    {
        return User::create([
            'name' => 'Document Import Tester',
            'email' => $email,
            'password' => Hash::make('password'),
            'level' => $level,
            'is_active' => 1,
        ]);
    }

    private function docx(string $text): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'question-import-docx-').'.docx';
        $archive = new ZipArchive;
        $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $archive->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
XML);
        $archive->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML);
        $paragraphs = collect(preg_split('/\n/', $text))->map(
            fn (string $line) => '<w:p><w:r><w:t xml:space="preserve">'.htmlspecialchars($line, ENT_XML1).'</w:t></w:r></w:p>'
        )->implode('');
        $archive->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$paragraphs.'<w:sectPr/></w:body></w:document>');
        $archive->close();

        return new UploadedFile(
            $path,
            'soal.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true,
        );
    }

    private function pdf(string $text): UploadedFile
    {
        $dompdf = new Dompdf;
        $dompdf->loadHtml('<pre>'.htmlspecialchars($text).'</pre>');
        $dompdf->render();
        $path = tempnam(sys_get_temp_dir(), 'question-import-pdf-').'.pdf';
        file_put_contents($path, $dompdf->output());

        return new UploadedFile($path, 'soal.pdf', 'application/pdf', null, true);
    }
}
