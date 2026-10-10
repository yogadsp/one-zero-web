<?php

namespace App\Services\Lesson;

use App\Exceptions\QuestionImportException;
use App\Models\Lesson\Question;
use App\Models\Lesson\QuestionTitle;
use App\Models\Lesson\ValueCategory;
use App\Services\Content\DocumentTextExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuestionDocumentImportService
{
    private const MAX_QUESTIONS = 200;

    private const PREVIEW_TTL_MINUTES = 30;

    private const CATEGORY_ALIASES = [
        'b. indonesia' => 'bahasa indonesia',
        'b. inggris' => 'bahasa inggris',
        'matematika wajib' => 'matematika',
        'pu' => 'penalaran umum',
        'ppu' => 'pengetahuan dan pemahaman umum',
        'pbm' => 'pemahaman bacaan dan menulis',
        'pk' => 'pengetahuan kuantitatif',
        'lbi' => 'literasi bahasa indonesia',
        'lbe' => 'literasi bahasa inggris',
        'pm' => 'penalaran matematika',
        'listening' => 'listening comprehension',
        'structure' => 'structure and written expression',
        'reading' => 'reading comprehension',
    ];

    public function __construct(private readonly DocumentTextExtractor $extractor)
    {
    }

    public function preview(UploadedFile $file, QuestionTitle $questionTitle, string $userId): array
    {
        $text = $this->extractor->extract($file);
        $questions = $this->parse($text, $questionTitle);
        $valid = collect($questions)->every(fn (array $question) => $question['errors'] === []);
        $token = null;

        if ($valid) {
            $token = bin2hex(random_bytes(32));
            Cache::put($this->cacheKey($userId, $token), [
                'question_title_id' => $questionTitle->id,
                'questions' => array_map(fn (array $question) => $question['data'], $questions),
            ], now()->addMinutes(self::PREVIEW_TTL_MINUTES));
        }

        return [
            'file_name' => basename($file->getClientOriginalName()),
            'valid' => $valid,
            'token' => $token,
            'expires_in_minutes' => self::PREVIEW_TTL_MINUTES,
            'total' => count($questions),
            'questions' => array_map(fn (array $question) => [
                'number' => $question['number'],
                'line' => $question['line'],
                'section' => $question['data']['section'] ?? $question['raw']['section'],
                'value_category' => $question['data']['value_category_name'] ?? $question['raw']['category'],
                'question' => $question['raw']['question'],
                'options' => $question['raw']['options'],
                'answer' => $question['raw']['answer'],
                'points' => $question['raw']['points'],
                'discussion' => $question['raw']['discussion'],
                'errors' => $question['errors'],
            ], $questions),
        ];
    }

    public function commit(string $token, QuestionTitle $questionTitle, string $userId): int
    {
        $cacheKey = $this->cacheKey($userId, $token);
        $payload = Cache::pull($cacheKey);

        if (! is_array($payload) || ($payload['question_title_id'] ?? null) !== $questionTitle->id) {
            throw new QuestionImportException('Pratinjau impor sudah kedaluwarsa atau tidak valid. Unggah ulang dokumen.');
        }

        $questions = $payload['questions'] ?? [];
        if ($questions === []) {
            throw new QuestionImportException('Tidak ada soal valid yang dapat diimpor.');
        }

        DB::transaction(function () use ($questionTitle, $questions): void {
            QuestionTitle::query()->whereKey($questionTitle->id)->lockForUpdate()->firstOrFail();

            $categoryIds = ValueCategory::query()
                ->where('category_id', $questionTitle->category_id)
                ->whereIn('id', collect($questions)->pluck('value_category_id'))
                ->pluck('section', 'id');

            foreach ($questions as $question) {
                if (! $categoryIds->has($question['value_category_id'])
                    || (int) $categoryIds[$question['value_category_id']] !== (int) $question['section']) {
                    throw new QuestionImportException('Kategori penilaian berubah setelah pratinjau. Unggah ulang dokumen.');
                }
            }

            $existing = Question::query()
                ->where('question_title_id', $questionTitle->id)
                ->pluck('question')
                ->map(fn (?string $question) => $this->duplicateKey((string) $question))
                ->filter()
                ->flip();

            foreach ($questions as $question) {
                if ($existing->has($this->duplicateKey($question['question']))) {
                    throw new QuestionImportException('Dokumen memuat soal yang sudah ada di bank soal. Unggah ulang setelah memperbaiki duplikat.');
                }

                unset($question['value_category_name']);
                Question::create($question);
                $existing->put($this->duplicateKey($question['question']), true);
            }
        });

        Log::info('question_document_imported', [
            'actor_id' => $userId,
            'question_title_id' => $questionTitle->id,
            'question_count' => count($questions),
        ]);

        return count($questions);
    }

    private function parse(string $text, QuestionTitle $questionTitle): array
    {
        $blocks = $this->parseBlocks($text);

        if ($blocks === []) {
            throw new QuestionImportException('Tidak ditemukan penanda SOAL. Gunakan format template yang ditampilkan pada halaman impor.');
        }

        if (count($blocks) > self::MAX_QUESTIONS) {
            throw new QuestionImportException('Satu dokumen dapat memuat maksimal '.self::MAX_QUESTIONS.' soal.');
        }

        $valueCategories = ValueCategory::query()
            ->where('category_id', $questionTitle->category_id)
            ->orderBy('section')
            ->get();
        $categoriesByName = $valueCategories->groupBy(fn (ValueCategory $category) => $this->normalizedName($category->name));
        $existing = Question::query()
            ->where('question_title_id', $questionTitle->id)
            ->pluck('question')
            ->map(fn (?string $question) => $this->duplicateKey((string) $question))
            ->filter()
            ->flip();
        $seen = [];
        $result = [];

        foreach ($blocks as $index => $block) {
            $errors = [];
            $category = null;
            $categoryName = $this->normalizedName($block['category']);

            if ($categoryName === '' && $block['section'] !== null) {
                $categoriesForSection = $valueCategories->where('section', (int) $block['section']);
                if ($categoriesForSection->count() === 1) {
                    $category = $categoriesForSection->first();
                    $categoryName = $this->normalizedName($category->name);
                }
            }

            if ($category === null && $categoryName !== '' && ! $categoriesByName->has($categoryName)) {
                $alias = self::CATEGORY_ALIASES[$categoryName] ?? null;
                if ($alias && $categoriesByName->has($alias)) {
                    $categoryName = $alias;
                }
            }

            if ($categoryName === '') {
                $errors[] = 'KATEGORI wajib diisi.';
            } elseif (! $categoriesByName->has($categoryName)) {
                $errors[] = "Kategori penilaian '{$block['category']}' tidak ditemukan pada kategori bank soal ini.";
            } elseif ($categoriesByName[$categoryName]->count() > 1) {
                $errors[] = "Kategori penilaian '{$block['category']}' tidak unik. Perbaiki data kategori terlebih dahulu.";
            } else {
                $category = $categoriesByName[$categoryName]->first();
            }

            $section = $block['section'] !== null ? (int) $block['section'] : (int) ($category?->section ?? 0);
            if ($section < 1 || $section > (int) $questionTitle->total_section) {
                $errors[] = "BAGIAN harus antara 1 dan {$questionTitle->total_section}.";
            }
            if ($category && (int) $category->section !== $section) {
                $errors[] = "BAGIAN {$section} tidak sesuai dengan kategori {$category->name} yang berada pada bagian {$category->section}.";
            }

            if ($block['question'] === '') {
                $errors[] = 'Teks SOAL wajib diisi.';
            } elseif (mb_strlen($block['question']) > 20000) {
                $errors[] = 'Teks SOAL maksimal 20.000 karakter.';
            }

            $totalChoices = (int) $questionTitle->total_choices;
            foreach (range(0, $totalChoices - 1) as $choiceIndex) {
                $letter = chr(65 + $choiceIndex);
                if (($block['options'][$letter] ?? '') === '') {
                    $errors[] = "Pilihan {$letter} wajib diisi.";
                } elseif (mb_strlen($block['options'][$letter]) > 5000) {
                    $errors[] = "Pilihan {$letter} maksimal 5.000 karakter.";
                }
            }

            foreach (array_keys($block['options']) as $letter) {
                if ((ord($letter) - 64) > $totalChoices) {
                    $errors[] = "Pilihan {$letter} melebihi jumlah pilihan bank soal ({$totalChoices}).";
                }
            }

            $answerNumber = $this->letterToNumber($block['answer']);
            if ($answerNumber < 1 || $answerNumber > $totalChoices) {
                $errors[] = 'KUNCI harus menggunakan huruf A sampai '.chr(64 + $totalChoices).'.';
            }

            if ($block['discussion'] === '') {
                $errors[] = 'PEMBAHASAN wajib diisi agar koreksi jawaban tidak kosong.';
            } elseif (mb_strlen($block['discussion']) > 20000) {
                $errors[] = 'PEMBAHASAN maksimal 20.000 karakter.';
            }

            $requiresPoints = (int) $category?->assessment_type === ValueCategory::ASSESSMENT_OPTION_POINTS;
            $hasAnyPoint = $block['points'] !== [];
            if ($requiresPoints || $hasAnyPoint) {
                foreach (range(0, $totalChoices - 1) as $choiceIndex) {
                    $letter = chr(65 + $choiceIndex);
                    if (! array_key_exists($letter, $block['points'])) {
                        $errors[] = "POIN {$letter} wajib diisi untuk kategori berbobot opsi.";
                    } elseif ($block['points'][$letter] < 0 || $block['points'][$letter] > 1000) {
                        $errors[] = "POIN {$letter} harus antara 0 dan 1000.";
                    }
                }
            }

            $duplicateKey = $this->duplicateKey($block['question']);
            if ($duplicateKey !== '' && ($existing->has($duplicateKey) || isset($seen[$duplicateKey]))) {
                $errors[] = 'Soal duplikat dengan soal yang sudah ada atau soal lain di dokumen ini.';
            }
            $seen[$duplicateKey] = true;

            $data = [
                'question_title_id' => $questionTitle->id,
                'type' => 1,
                'direction' => null,
                'value_category_id' => $category?->id,
                'value_category_name' => $category?->name,
                'audio_input' => 0,
                'audio' => null,
                'audio_played' => 0,
                'audio_played_limit' => null,
                'audio_answer_time' => null,
                'question' => $this->toSafeHtml($block['question']),
                'answer' => $answerNumber,
                'discussion' => $this->toSafeHtml($block['discussion']),
                'section' => $section,
                'option_has_point' => $requiresPoints || $hasAnyPoint ? 1 : 0,
            ];

            foreach (range(1, 5) as $choiceNumber) {
                $letter = chr(64 + $choiceNumber);
                $data['option_'.$choiceNumber] = $choiceNumber <= $totalChoices
                    ? $this->toSafeHtml($block['options'][$letter] ?? '')
                    : null;
                $data['option_'.$choiceNumber.'_point'] = $choiceNumber <= $totalChoices
                    ? ($block['points'][$letter] ?? null)
                    : null;
            }

            $result[] = [
                'number' => $index + 1,
                'line' => $block['line'],
                'raw' => $block,
                'data' => $data,
                'errors' => array_values(array_unique($errors)),
            ];
        }

        return $result;
    }

    private function parseBlocks(string $text): array
    {
        $lines = preg_split('/\n/u', $text) ?: [];
        $context = ['section' => null, 'category' => ''];
        $blocks = [];
        $current = null;
        $activeField = null;

        $finish = function () use (&$blocks, &$current, &$activeField): void {
            if ($current !== null) {
                foreach (['question', 'answer', 'discussion', 'category'] as $field) {
                    $current[$field] = trim((string) $current[$field]);
                }
                $current['options'] = array_map('trim', $current['options']);
                $blocks[] = $current;
            }
            $current = null;
            $activeField = null;
        };

        foreach ($lines as $offset => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^(?:BAGIAN|SECTION)\s*[:\-]?\s*(\d+)\s*$/iu', $line, $match)) {
                if ($current !== null && empty($current['options']) && empty($current['answer'])) {
                    $context['section'] = (int) $match[1];
                    $current['section'] = $context['section'];
                } else {
                    $finish();
                    $context['section'] = (int) $match[1];
                }

                continue;
            }

            if (preg_match('/^(?:KATEGORI|CATEGORY)\s*[:\-]\s*(.+)$/iu', $line, $match)) {
                if ($current !== null && empty($current['options']) && empty($current['answer'])) {
                    $context['category'] = trim($match[1]);
                    $current['category'] = $context['category'];
                } else {
                    $finish();
                    $context['category'] = trim($match[1]);
                }

                continue;
            }

            if (preg_match('/^(?:SOAL|QUESTION)(?:\s*\.?\s*\d+)?\s*[:\.\-]\s*(.*)$/iu', $line, $match)) {
                $finish();
                $current = [
                    'line' => $offset + 1,
                    'section' => $context['section'],
                    'category' => $context['category'],
                    'question' => trim($match[1]),
                    'options' => [],
                    'answer' => '',
                    'points' => [],
                    'discussion' => '',
                ];
                $activeField = 'question';

                continue;
            }

            if ($current === null) {
                continue;
            }

            if (preg_match('/^([A-E])[\.\:\)]\s*(.*)$/iu', $line, $match)) {
                $letter = strtoupper($match[1]);
                $current['options'][$letter] = trim($match[2]);
                $activeField = 'option:'.$letter;

                continue;
            }

            if (preg_match('/^(?:KUNCI(?:\s+JAWABAN)?|KEY|ANSWER)\s*[:\-]\s*([A-E])\s*$/iu', $line, $match)) {
                $current['answer'] = strtoupper($match[1]);
                $activeField = null;

                continue;
            }

            if (preg_match('/^(?:POIN|BOBOT|POINT|POINTS)\s+([A-E])\s*[:\-]\s*(-?\d+)\s*$/iu', $line, $match)) {
                $current['points'][strtoupper($match[1])] = (int) $match[2];
                $activeField = null;

                continue;
            }

            if (preg_match('/^(?:PEMBAHASAN(?:\s+SOAL)?|DISCUSSION|EXPLANATION)\s*[:\-]\s*(.*)$/iu', $line, $match)) {
                $current['discussion'] = trim($match[1]);
                $activeField = 'discussion';

                continue;
            }

            if ($activeField === 'question' || $activeField === 'discussion') {
                $current[$activeField] = trim($current[$activeField]."\n".$line);
            } elseif (str_starts_with((string) $activeField, 'option:')) {
                $letter = substr($activeField, -1);
                $current['options'][$letter] = trim($current['options'][$letter]."\n".$line);
            }
        }

        $finish();

        return $blocks;
    }

    private function toSafeHtml(string $text): string
    {
        return '<p>'.nl2br(e(trim($text)), false).'</p>';
    }

    private function duplicateKey(string $question): string
    {
        $plain = html_entity_decode(strip_tags($question), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\s+/u', ' ', $plain) ?? $plain;

        return mb_strtolower(trim($plain));
    }

    private function normalizedName(?string $name): string
    {
        $name = str_replace('&', 'and', (string) $name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return mb_strtolower(trim($name));
    }

    private function letterToNumber(string $letter): int
    {
        $letter = strtoupper(trim($letter));

        return preg_match('/^[A-E]$/', $letter) ? ord($letter) - 64 : 0;
    }

    private function cacheKey(string $userId, string $token): string
    {
        return "question-import:{$userId}:{$token}";
    }
}
