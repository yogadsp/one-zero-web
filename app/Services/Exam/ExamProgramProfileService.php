<?php

namespace App\Services\Exam;

use App\Models\MasterData\Category;
use Illuminate\Support\Collection;

class ExamProgramProfileService
{
    public function profilesFor(Collection $categories): array
    {
        return $categories
            ->mapWithKeys(fn (Category $category) => [
                $category->id => $this->profileForName($category->name),
            ])
            ->all();
    }

    public function profileForCategoryId(?string $categoryId): array
    {
        if (! $categoryId) {
            return $this->defaultProfile();
        }

        $category = Category::query()->find($categoryId);

        return $this->profileForName($category?->name);
    }

    public function profileForName(?string $categoryName): array
    {
        $name = mb_strtoupper(trim((string) $categoryName));
        $profile = $this->defaultProfile();

        if ($name === 'CPNS') {
            return array_replace($profile, [
                'program' => 'CPNS',
                'section_label' => 'Subtes CPNS',
                'section_help' => 'Section bersifat fleksibel. Anda dapat membuat hanya subtes yang diperlukan, misalnya TWK saja, TIU saja, atau TKP saja.',
                'value_assessment_options' => [
                    ['value' => 3, 'label' => 'Bobot per opsi CPNS', 'description' => 'Jumlahkan poin opsi terpilih; cocok untuk TWK/TIU 5 atau 0 dan TKP 1 sampai 5.'],
                ],
                'question_assessment_options' => [
                    ['value' => 1, 'label' => 'Skor per subtes CPNS', 'description' => 'Nilai TWK, TIU, dan TKP dihitung dari konfigurasi masing-masing subtes.'],
                ],
                'duration_options' => [
                    ['value' => 1, 'label' => 'Satu waktu ujian CPNS', 'description' => 'Timer berjalan untuk keseluruhan simulasi.'],
                    ['value' => 2, 'label' => 'Waktu latihan per subtes CPNS', 'description' => 'Timer direset ketika berpindah TWK, TIU, atau TKP.'],
                ],
            ]);
        }

        if ($name === 'UTBK') {
            return array_replace($profile, [
                'program' => 'UTBK',
                'section_label' => 'Subtes UTBK',
                'section_help' => 'Section bersifat fleksibel. Anda dapat membuat satu atau beberapa subtes UTBK sesuai kebutuhan. Nilai di aplikasi adalah skor latihan, bukan skor terstandar resmi SNPMB.',
                'value_assessment_options' => [
                    ['value' => 1, 'label' => 'Persentase benar per subtes (simulasi)', 'description' => 'Jawaban benar dibagi seluruh soal pada subtes.'],
                    ['value' => 2, 'label' => 'Jumlah benar per subtes (raw score)', 'description' => 'Menampilkan jumlah jawaban benar tanpa mengklaim skor resmi UTBK.'],
                ],
                'question_assessment_options' => [
                    ['value' => 1, 'label' => 'Skor latihan per subtes UTBK', 'description' => 'Menampilkan rincian hasil pada setiap subtes.'],
                    ['value' => 2, 'label' => 'Persentase keseluruhan (simulasi)', 'description' => 'Persentase benar dari seluruh soal latihan.'],
                ],
                'duration_options' => [
                    ['value' => 1, 'label' => 'Satu waktu simulasi UTBK', 'description' => 'Timer berjalan untuk keseluruhan paket.'],
                    ['value' => 2, 'label' => 'Waktu per subtes UTBK', 'description' => 'Timer mengikuti durasi tiap subtes.'],
                ],
            ]);
        }

        if ($name === 'TKA') {
            return array_replace($profile, [
                'program' => 'TKA',
                'section_label' => 'Mata uji TKA',
                'section_help' => 'Section bersifat fleksibel. Anda dapat membuat hanya Matematika, Bahasa Inggris, atau mata uji lain sesuai kebutuhan. Nilai yang dihitung adalah skor simulasi latihan.',
                'value_assessment_options' => [
                    ['value' => 1, 'label' => 'Persentase benar per mata uji (simulasi)', 'description' => 'Jawaban benar dibagi seluruh soal pada mata uji.'],
                    ['value' => 2, 'label' => 'Jumlah benar per mata uji (raw score)', 'description' => 'Menampilkan jumlah jawaban benar pada mata uji.'],
                ],
                'question_assessment_options' => [
                    ['value' => 1, 'label' => 'Skor latihan per mata uji TKA', 'description' => 'Menampilkan hasil untuk setiap mata uji.'],
                    ['value' => 2, 'label' => 'Persentase keseluruhan (simulasi)', 'description' => 'Persentase benar dari seluruh soal latihan.'],
                ],
                'duration_options' => [
                    ['value' => 1, 'label' => 'Satu waktu paket TKA', 'description' => 'Timer berjalan untuk keseluruhan paket latihan.'],
                    ['value' => 2, 'label' => 'Waktu per mata uji TKA', 'description' => 'Timer diatur untuk setiap mata uji.'],
                ],
            ]);
        }

        return $profile;
    }

    public function allowedValueAssessmentTypes(?string $categoryId): array
    {
        return array_column($this->profileForCategoryId($categoryId)['value_assessment_options'], 'value');
    }

    public function allowedQuestionAssessmentTypes(?string $categoryId): array
    {
        return array_column($this->profileForCategoryId($categoryId)['question_assessment_options'], 'value');
    }

    public function allowedDurationTypes(?string $categoryId): array
    {
        return array_column($this->profileForCategoryId($categoryId)['duration_options'], 'value');
    }

    private function defaultProfile(): array
    {
        return [
            'program' => 'UMUM',
            'section_label' => 'Section',
            'section_help' => 'Section menentukan urutan soal, rincian nilai, dan timer per bagian bila digunakan.',
            'value_assessment_options' => [
                ['value' => 1, 'label' => 'Persentase jawaban benar', 'description' => 'Jawaban benar dibagi seluruh soal pada section.'],
                ['value' => 2, 'label' => 'Jumlah jawaban benar', 'description' => 'Nilai sama dengan jumlah jawaban benar.'],
                ['value' => 3, 'label' => 'Jumlah poin per opsi', 'description' => 'Nilai dijumlahkan dari poin opsi yang dipilih.'],
            ],
            'question_assessment_options' => [
                ['value' => 1, 'label' => 'Dihitung per kategori penilaian', 'description' => 'Hasil dirinci berdasarkan section/kategori penilaian.'],
                ['value' => 2, 'label' => 'Persentase seluruh soal', 'description' => 'Jawaban benar dibagi seluruh soal.'],
                ['value' => 3, 'label' => 'Jumlah poin jawaban benar', 'description' => 'Poin hanya dijumlahkan untuk jawaban benar.'],
                ['value' => 4, 'label' => 'Jumlah bobot opsi terpilih', 'description' => 'Poin setiap opsi yang dipilih dijumlahkan.'],
            ],
            'duration_options' => [
                ['value' => 1, 'label' => 'Satu waktu', 'description' => 'Satu timer untuk seluruh ujian.'],
                ['value' => 2, 'label' => 'Waktu per section', 'description' => 'Timer berbeda untuk setiap section.'],
            ],
        ];
    }
}
