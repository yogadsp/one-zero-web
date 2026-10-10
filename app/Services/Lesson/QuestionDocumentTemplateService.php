<?php

namespace App\Services\Lesson;

use App\Models\Lesson\QuestionTitle;
use App\Models\Lesson\ValueCategory;
use ZipArchive;

class QuestionDocumentTemplateService
{
    public function generateDocx(QuestionTitle $questionTitle): string
    {
        return $this->createDocxBinary($this->getTemplateContent($questionTitle));
    }

    public function getTemplateContent(QuestionTitle $questionTitle): string
    {
        $totalChoices = (int) ($questionTitle->total_choices ?: 5);
        $valueCategories = ValueCategory::query()
            ->where('category_id', $questionTitle->category_id)
            ->orderBy('section')
            ->get();

        if ($valueCategories->isEmpty()) {
            return $this->fallbackTemplate($totalChoices);
        }

        $blocks = [];
        $choices = range(0, $totalChoices - 1);
        $optionsLines = collect($choices)->map(function ($i) {
            $letter = chr(65 + $i);
            $ordinal = match ($i) {
                0 => 'pertama',
                1 => 'kedua',
                2 => 'ketiga',
                3 => 'keempat',
                4 => 'kelima',
                default => (string) ($i + 1),
            };

            return "{$letter}. Pilihan {$ordinal}";
        })->implode("\n");

        $firstCat = $valueCategories->first();
        $firstRequiresPoints = (int) $firstCat->assessment_type === ValueCategory::ASSESSMENT_OPTION_POINTS;
        $firstPointsBlock = $firstRequiresPoints
            ? collect($choices)->map(fn ($i) => 'POIN '.chr(65 + $i).': '.($i + 1))->implode("\n")."\n"
            : '';

        // Soal 1
        $blocks[] = "BAGIAN: {$firstCat->section}\nKATEGORI: {$firstCat->name}\nSOAL 1: Tuliskan pertanyaan nomor 1 di sini...\n{$optionsLines}\nKUNCI: A\n{$firstPointsBlock}PEMBAHASAN: Penjelasan jawaban nomor 1.";

        // Soal 2 pada kategori yang sama untuk mencontohkan kontinuitas
        $blocks[] = "SOAL 2: Tuliskan pertanyaan nomor 2 di sini...\n{$optionsLines}\nKUNCI: B\n{$firstPointsBlock}PEMBAHASAN: Penjelasan jawaban nomor 2.";

        // Jika memiliki kategori/bagian lain di database, tampilkan contoh pergantian bagian
        $soalCounter = 2;
        foreach ($valueCategories->skip(1)->values()->take(2) as $cat) {
            $soalCounter++;
            $catRequiresPoints = (int) $cat->assessment_type === ValueCategory::ASSESSMENT_OPTION_POINTS;
            $pointsBlock = $catRequiresPoints
                ? collect($choices)->map(fn ($i) => 'POIN '.chr(65 + $i).': '.($i + 1))->implode("\n")."\n"
                : '';

            $blocks[] = "BAGIAN: {$cat->section}\nKATEGORI: {$cat->name}\nSOAL {$soalCounter}: Tuliskan pertanyaan nomor {$soalCounter} di sini...\n{$optionsLines}\nKUNCI: C\n{$pointsBlock}PEMBAHASAN: Penjelasan jawaban nomor {$soalCounter}.";
        }

        return implode("\n\n", $blocks);
    }

    private function fallbackTemplate(int $totalChoices): string
    {
        $choices = range(0, $totalChoices - 1);
        $optionsLines = collect($choices)->map(fn ($i) => chr(65 + $i).'. Pilihan '.($i + 1))->implode("\n");

        return "BAGIAN: 1\nKATEGORI: Umum\nSOAL 1: Tuliskan pertanyaan di sini...\n{$optionsLines}\nKUNCI: A\nPEMBAHASAN: Penjelasan jawaban.";
    }

    public function createDocxBinary(string $text): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docx_template_').'.docx';
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

        $paragraphs = collect(preg_split('/\r?\n/', $text))->map(function (string $line) {
            $clean = htmlspecialchars($line, ENT_XML1, 'UTF-8');

            return '<w:p><w:r><w:t xml:space="preserve">'.$clean.'</w:t></w:r></w:p>';
        })->implode('');

        $archive->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$paragraphs.'<w:sectPr/></w:body></w:document>');

        $archive->close();

        $content = file_get_contents($path) ?: '';
        @unlink($path);

        return $content;
    }
}
