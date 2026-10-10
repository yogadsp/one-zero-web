<?php

namespace Database\Seeders;

use App\Models\Exam\Exam;
use App\Models\Lesson\DetailValueCategory;
use App\Models\Lesson\QuestionTitle;
use App\Models\Lesson\ValueCategory;
use App\Models\MasterData\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class ProgramTryoutSampleSeeder extends Seeder
{
    private const ACCESS_USER_EMAIL = 'usertesting3@mail.com';

    public function run(): void
    {
        DB::transaction(function (): void {
            $user = User::query()->where('email', self::ACCESS_USER_EMAIL)->first();

            foreach ($this->programs() as $program => $definition) {
                $category = $this->category($program);
                $valueCategories = $this->valueCategories($category, $definition['sections']);
                $questionTitle = $this->questionTitle($category, $program, $definition);

                $this->questions($questionTitle, $valueCategories, $program, $definition['questions']);

                if ($program === 'TOEFL') {
                    $this->ensureMiniToeflConversions($valueCategories);
                }

                if ($program === 'TOEIC') {
                    $this->ensureMiniToeicConversions($valueCategories);
                }

                $exam = $this->exam($category, $questionTitle, $program, $definition);

                if ($user) {
                    $this->grantAccess($user, $exam, $program);
                }
            }

            if (! $user) {
                $this->command?->warn('Paket sampel dibuat, tetapi akses tidak diberikan karena '.self::ACCESS_USER_EMAIL.' tidak ditemukan.');
            }
        });
    }

    private function category(string $program): Category
    {
        $category = Category::withTrashed()
            ->whereRaw('UPPER(name) = ?', [$program])
            ->first();

        if (! $category) {
            $category = Category::create([
                'id' => $this->stableId("category:{$program}"),
                'name' => $program,
            ]);
        } elseif ($category->trashed()) {
            $category->restore();
        }

        return $category;
    }

    private function valueCategories(Category $category, array $sections): array
    {
        $result = [];

        foreach ($sections as $section => $definition) {
            $valueCategory = ValueCategory::withTrashed()
                ->where('category_id', $category->id)
                ->where('section', $section)
                ->first();

            if (! $valueCategory) {
                $valueCategory = ValueCategory::create([
                    'id' => $this->stableId("value-category:{$category->name}:{$section}"),
                    'category_id' => $category->id,
                    'name' => $definition['name'],
                    'assessment_type' => $definition['assessment_type'],
                    'section' => $section,
                ]);
            } else {
                if ((int) $valueCategory->assessment_type !== (int) $definition['assessment_type']) {
                    throw new RuntimeException(
                        "Kategori penilaian {$category->name} section {$section} memakai tipe {$valueCategory->assessment_type}; "
                        ."sampel membutuhkan tipe {$definition['assessment_type']}."
                    );
                }

                if ($valueCategory->trashed()) {
                    $valueCategory->restore();
                }
            }

            $result[$section] = $valueCategory;
        }

        return $result;
    }

    private function questionTitle(Category $category, string $program, array $definition): QuestionTitle
    {
        $id = $this->stableId("question-title:{$program}");
        DB::table('question_titles')->updateOrInsert(
            ['id' => $id],
            [
                'category_id' => $category->id,
                'name' => "[SIMULASI] Bank Soal {$program}",
                'total_choices' => $definition['total_choices'],
                'total_section' => count($definition['sections']),
                'add_value_category' => 1,
                'assessment_type' => QuestionTitle::ASSESSMENT_PER_CATEGORY,
                'show_answer' => 1,
                'minimum_grade' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        );

        return QuestionTitle::query()->findOrFail($id);
    }

    private function questions(
        QuestionTitle $questionTitle,
        array $valueCategories,
        string $program,
        array $questions,
    ): void {
        foreach ($questions as $index => $definition) {
            $section = (int) $definition['section'];
            $points = $definition['points'] ?? [];
            $id = $this->stableId("question:{$program}:".($index + 1));
            DB::table('questions')->updateOrInsert(
                ['id' => $id],
                [
                    'question_title_id' => $questionTitle->id,
                    'type' => 1,
                    'direction' => null,
                    'value_category_id' => $valueCategories[$section]->id,
                    'audio_input' => 0,
                    'audio' => null,
                    'audio_played' => 0,
                    'audio_played_limit' => null,
                    'audio_answer_time' => null,
                    'question' => $definition['question'],
                    'option_1' => $definition['options'][0] ?? null,
                    'option_2' => $definition['options'][1] ?? null,
                    'option_3' => $definition['options'][2] ?? null,
                    'option_4' => $definition['options'][3] ?? null,
                    'option_5' => $definition['options'][4] ?? null,
                    'answer' => $definition['answer'],
                    'discussion' => $definition['discussion'],
                    'section' => $section,
                    'option_has_point' => $points !== [] ? 1 : 0,
                    'option_1_point' => $points[0] ?? null,
                    'option_2_point' => $points[1] ?? null,
                    'option_3_point' => $points[2] ?? null,
                    'option_4_point' => $points[3] ?? null,
                    'option_5_point' => $points[4] ?? null,
                    'created_at' => now()->startOfDay()->addSeconds($index + 1),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ],
            );
        }
    }

    private function exam(
        Category $category,
        QuestionTitle $questionTitle,
        string $program,
        array $definition,
    ): Exam {
        $id = $this->stableId("exam:{$program}");
        DB::table('exams')->updateOrInsert(
            ['id' => $id],
            [
                'category_id' => $category->id,
                'question_title_id' => $questionTitle->id,
                'title' => "[SIMULASI] Tryout Mini {$program}",
                'price' => 0,
                'duration_type' => 1,
                'duration' => $definition['duration'],
                'description' => $definition['description'],
                'random_question' => 0,
                'random_answer' => 0,
                'show_answer' => 1,
                'show_question_number_navigation' => 1,
                'show_question_number' => 1,
                'next_question_automatically' => 0,
                'show_prev_next_button' => 1,
                'type_option' => 1,
                'total_tolerance' => 3,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        );

        return Exam::query()->findOrFail($id);
    }

    private function grantAccess(User $user, Exam $exam, string $program): void
    {
        $id = $this->stableId("transaction:{$user->email}:{$program}");
        DB::table('transactions')->updateOrInsert(
            ['id' => $id],
            [
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'learning_material_id' => null,
                'code' => 'SIM-'.strtoupper($program),
                'voucher_activated' => 1,
                'voucher_used' => 1,
                'total_purchases' => 0,
                'maximum_payment_time' => now()->addYears(10),
                'transaction_status' => 'done',
                'voucher_token' => 'SIMULASI-'.strtoupper($program),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        );
    }

    private function ensureMiniToeflConversions(array $valueCategories): void
    {
        $conversions = [
            1 => [[0, 0, 24], [1, 1, 25], [2, 2, 26]],
            2 => [[0, 1, 20], [2, 2, 21]],
            3 => [[0, 0, 21], [1, 1, 22], [2, 2, 23]],
        ];

        foreach ($conversions as $section => $ranges) {
            foreach ($ranges as [$minimum, $maximum, $score]) {
                $coversRange = DetailValueCategory::query()
                    ->where('value_category_id', $valueCategories[$section]->id)
                    ->where('min_grade', '<=', $minimum)
                    ->where('max_grade', '>=', $maximum)
                    ->exists();

                if (! $coversRange) {
                    DetailValueCategory::create([
                        'id' => $this->stableId("toefl-conversion:{$section}:{$minimum}:{$maximum}"),
                        'value_category_id' => $valueCategories[$section]->id,
                        'min_grade' => $minimum,
                        'max_grade' => $maximum,
                        'final_grade' => $score,
                    ]);
                }
            }
        }
    }

    private function ensureMiniToeicConversions(array $valueCategories): void
    {
        foreach ([1, 2] as $section) {
            foreach ([[0, 0, 0], [1, 2, 5]] as [$minimum, $maximum, $score]) {
                $coversRange = DetailValueCategory::query()
                    ->where('value_category_id', $valueCategories[$section]->id)
                    ->where('min_grade', '<=', $minimum)
                    ->where('max_grade', '>=', $maximum)
                    ->exists();

                if (! $coversRange) {
                    DetailValueCategory::create([
                        'id' => $this->stableId("toeic-conversion:{$section}:{$minimum}:{$maximum}"),
                        'value_category_id' => $valueCategories[$section]->id,
                        'min_grade' => $minimum,
                        'max_grade' => $maximum,
                        'final_grade' => $score,
                    ]);
                }
            }
        }
    }

    private function stableId(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, "https://one-zero.local/sample-tryout/{$key}")->toString();
    }

    private function programs(): array
    {
        return [
            'CPNS' => [
                'total_choices' => 5,
                'duration' => 20,
                'description' => '<p>Mini simulasi SKD CPNS dengan TWK, TIU, dan TKP. Skor memakai bobot opsi: TWK/TIU 5 atau 0, sedangkan TKP 1 sampai 5. Paket latihan ini bukan naskah resmi BKN.</p>',
                'sections' => [
                    1 => ['name' => 'TWK', 'assessment_type' => ValueCategory::ASSESSMENT_OPTION_POINTS],
                    2 => ['name' => 'TIU', 'assessment_type' => ValueCategory::ASSESSMENT_OPTION_POINTS],
                    3 => ['name' => 'TKP', 'assessment_type' => ValueCategory::ASSESSMENT_OPTION_POINTS],
                ],
                'questions' => $this->cpnsQuestions(),
            ],
            'UTBK' => [
                'total_choices' => 5,
                'duration' => 25,
                'description' => '<p>Mini simulasi UTBK-SNBT yang mencakup tujuh subtes. Nilai adalah skor latihan internal, bukan skor terstandar resmi SNPMB.</p>',
                'sections' => [
                    1 => ['name' => 'Penalaran Umum', 'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE],
                    2 => ['name' => 'Pengetahuan dan Pemahaman Umum', 'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE],
                    3 => ['name' => 'Pemahaman Bacaan dan Menulis', 'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE],
                    4 => ['name' => 'Pengetahuan Kuantitatif', 'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE],
                    5 => ['name' => 'Literasi Bahasa Indonesia', 'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE],
                    6 => ['name' => 'Literasi Bahasa Inggris', 'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE],
                    7 => ['name' => 'Penalaran Matematika', 'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE],
                ],
                'questions' => $this->utbkQuestions(),
            ],
            'TKA' => [
                'total_choices' => 5,
                'duration' => 20,
                'description' => '<p>Mini simulasi TKA SMA: Bahasa Indonesia, Matematika, Bahasa Inggris, serta contoh pilihan Fisika dan Ekonomi. Nilai merupakan skor latihan.</p>',
                'sections' => [
                    1 => ['name' => 'Bahasa Indonesia', 'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE],
                    2 => ['name' => 'Matematika', 'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE],
                    3 => ['name' => 'Bahasa Inggris', 'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE],
                    4 => ['name' => 'Mata Pelajaran Pilihan 1', 'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE],
                    5 => ['name' => 'Mata Pelajaran Pilihan 2', 'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE],
                ],
                'questions' => $this->tkaQuestions(),
            ],
            'TOEFL' => [
                'total_choices' => 4,
                'duration' => 20,
                'description' => '<p>Mini simulasi TOEFL ITP: Listening Comprehension, Structure and Written Expression, dan Reading Comprehension. Bagian listening memakai transkrip agar sampel dapat dicoba tanpa berkas audio. Skor mini tidak setara hasil TOEFL ITP resmi.</p>',
                'sections' => [
                    1 => ['name' => 'Listening Comprehension', 'assessment_type' => ValueCategory::ASSESSMENT_CORRECT_COUNT],
                    2 => ['name' => 'Structure and Written Expression', 'assessment_type' => ValueCategory::ASSESSMENT_CORRECT_COUNT],
                    3 => ['name' => 'Reading Comprehension', 'assessment_type' => ValueCategory::ASSESSMENT_CORRECT_COUNT],
                ],
                'questions' => $this->toeflQuestions(),
            ],
            'TOEIC' => [
                'total_choices' => 4,
                'duration' => 15,
                'description' => '<p>Mini simulasi TOEIC Listening and Reading dalam konteks tempat kerja. Listening memakai transkrip. Nilai memakai tabel konversi latihan aplikasi dan bukan skor resmi TOEIC 10–990.</p>',
                'sections' => [
                    1 => ['name' => 'Listening Comprehension', 'assessment_type' => ValueCategory::ASSESSMENT_CORRECT_COUNT],
                    2 => ['name' => 'Reading Comprehension', 'assessment_type' => ValueCategory::ASSESSMENT_CORRECT_COUNT],
                ],
                'questions' => $this->toeicQuestions(),
            ],
        ];
    }

    private function cpnsQuestions(): array
    {
        return [
            $this->question(1, 'Makna semboyan <em>Bhinneka Tunggal Ika</em> dalam kehidupan berbangsa adalah ...', ['mengutamakan kepentingan daerah', 'menyeragamkan seluruh kebudayaan', 'berbeda-beda tetapi tetap bersatu', 'membatasi kerja sama antardaerah', 'memisahkan urusan sosial dan negara'], 3, 'Semboyan tersebut menegaskan persatuan bangsa tetap terpelihara di tengah perbedaan suku, agama, budaya, dan bahasa.', [0, 0, 5, 0, 0]),
            $this->question(1, 'Seorang pegawai menemukan kesalahan data yang dapat menguntungkan unitnya. Sikap yang paling mencerminkan integritas adalah ...', ['membiarkan karena menguntungkan unit', 'melaporkan dan memperbaiki data sesuai prosedur', 'menghapus bukti kesalahan', 'menunggu sampai ada pemeriksaan', 'menceritakannya hanya kepada rekan dekat'], 2, 'Integritas menuntut kejujuran, konsistensi, dan keberanian memperbaiki data berdasarkan prosedur meskipun tidak menguntungkan pihak sendiri.', [0, 5, 0, 0, 0]),
            $this->question(2, 'Tentukan angka berikutnya pada pola 2, 6, 12, 20, 30, ...', ['36', '40', '42', '44', '48'], 3, 'Selisihnya 4, 6, 8, dan 10. Selisih berikutnya 12, sehingga 30 + 12 = 42.', [0, 0, 5, 0, 0]),
            $this->question(2, 'KOMPAS berhubungan dengan ARAH sebagaimana TERMOMETER berhubungan dengan ...', ['cuaca', 'suhu', 'tekanan', 'waktu', 'jarak'], 2, 'Kompas digunakan untuk mengetahui arah, sedangkan termometer digunakan untuk mengetahui suhu.', [0, 5, 0, 0, 0]),
            $this->question(3, 'Antrean layanan memanjang karena sistem baru lambat. Apa tindakan Anda sebagai petugas?', ['menutup loket sampai sistem normal', 'meminta warga kembali besok tanpa penjelasan', 'tetap melayani, menjelaskan kendala, dan mengaktifkan prosedur layanan cadangan', 'mendahulukan warga yang dikenal', 'menunggu instruksi tanpa melakukan apa pun'], 3, 'Pilihan terbaik menjaga pelayanan publik, transparan kepada warga, dan menggunakan prosedur cadangan agar layanan tetap berjalan.', [2, 1, 5, 1, 3]),
            $this->question(3, 'Rekan satu tim salah memasukkan data menjelang batas waktu. Respons paling tepat adalah ...', ['memperbaiki diam-diam tanpa memberi tahu', 'menyalahkannya di grup umum', 'membiarkan agar ia belajar dari kegagalan', 'membantu memperbaiki, menjelaskan kesalahan, lalu membuat pemeriksaan ulang bersama', 'langsung melaporkannya sebagai pelanggaran berat'], 4, 'Tindakan terbaik menyelesaikan risiko saat ini sekaligus membangun perbaikan proses dan kompetensi tim.', [3, 1, 2, 5, 2]),
        ];
    }

    private function utbkQuestions(): array
    {
        return [
            $this->question(1, 'Semua peneliti membaca jurnal. Sebagian pembaca jurnal menulis artikel. Simpulan yang pasti benar adalah ...', ['semua peneliti menulis artikel', 'sebagian peneliti menulis artikel', 'semua penulis artikel adalah peneliti', 'peneliti termasuk kelompok pembaca jurnal', 'tidak ada peneliti yang menulis artikel'], 4, 'Premis pertama langsung menyatakan bahwa setiap peneliti merupakan pembaca jurnal. Hubungan peneliti dengan penulis artikel tidak dapat dipastikan.'),
            $this->question(2, 'Kata <strong>akurat</strong> pada kalimat “Data harus disajikan secara akurat” paling dekat maknanya dengan ...', ['cepat', 'tepat', 'ringkas', 'menarik', 'baru'], 2, 'Dalam konteks penyajian data, akurat berarti tepat atau sesuai dengan keadaan sebenarnya.'),
            $this->question(3, 'Kalimat yang paling efektif adalah ...', ['Para peserta-peserta diminta untuk masuk ke dalam ruangan.', 'Peserta diminta masuk ke ruangan.', 'Kepada peserta dimohon untuk masuk ke dalam ruangan.', 'Para peserta diminta agar supaya masuk.', 'Peserta-peserta dimohon masuk ke dalam.'], 2, 'Kalimat kedua hemat kata, memiliki subjek-predikat yang jelas, dan tidak mengandung bentuk jamak ganda.'),
            $this->question(4, 'Sebuah kelas berisi 12 siswa laki-laki dan 18 siswa perempuan. Persentase siswa perempuan adalah ...', ['40%', '50%', '60%', '66%', '75%'], 3, 'Jumlah siswa 30. Persentase siswa perempuan = 18/30 × 100% = 60%.'),
            $this->question(5, 'Sebuah kota memperbanyak ruang terbuka hijau untuk menurunkan suhu permukaan dan menyediakan area resapan. Pernyataan yang paling tepat adalah ...', ['ruang hijau hanya berfungsi sebagai tempat rekreasi', 'kebijakan tersebut memiliki manfaat lingkungan lebih dari satu', 'resapan air selalu menaikkan suhu kota', 'semua bangunan harus diganti taman', 'ruang hijau menghilangkan seluruh risiko banjir'], 2, 'Teks menyebut dua manfaat sekaligus: membantu menurunkan suhu permukaan dan menyediakan area resapan.'),
            $this->question(6, '<p><em>Remote work can reduce commuting time, but teams still need clear communication routines to avoid misunderstandings.</em></p><p>The sentence suggests that remote work ...</p>', ['eliminates every workplace problem', 'is useful only for small teams', 'offers a benefit but requires communication discipline', 'always reduces productivity', 'makes routines unnecessary'], 3, 'The connector “but” contrasts the benefit of less commuting with the continuing need for clear communication routines.'),
            $this->question(7, 'Harga sebuah buku setelah diskon 20% adalah Rp80.000. Harga sebelum diskon adalah ...', ['Rp90.000', 'Rp96.000', 'Rp100.000', 'Rp104.000', 'Rp120.000'], 3, 'Setelah diskon, harga menjadi 80% dari harga awal. Jadi harga awal = 80.000 / 0,8 = Rp100.000.'),
        ];
    }

    private function tkaQuestions(): array
    {
        return [
            $this->question(1, '<p>Penggunaan transportasi umum dapat mengurangi kemacetan dan emisi. Namun, masyarakat akan beralih jika layanan aman, nyaman, dan tepat waktu.</p><p>Gagasan utama paragraf tersebut adalah ...</p>', ['transportasi umum selalu lebih cepat', 'peralihan ke transportasi umum memerlukan layanan yang berkualitas', 'kendaraan pribadi harus dilarang', 'emisi hanya berasal dari kemacetan', 'masyarakat tidak membutuhkan transportasi umum'], 2, 'Paragraf menekankan manfaat transportasi umum sekaligus syarat agar masyarakat bersedia beralih, yaitu kualitas layanan.'),
            $this->question(2, 'Jika 3x − 5 = 16, nilai x adalah ...', ['5', '6', '7', '8', '9'], 3, '3x = 21 sehingga x = 7.'),
            $this->question(3, '<p><em>The school library extended its opening hours so students could study after class.</em></p><p>Why did the library extend its opening hours?</p>', ['To reduce the number of books', 'To allow students to study after class', 'To close during the morning', 'To replace classroom lessons', 'To limit student visits'], 2, 'The purpose is stated explicitly after “so”: students can study after class.'),
            $this->question(4, 'Sebuah benda bermassa 2 kg mengalami percepatan 3 m/s². Besar gaya resultan pada benda adalah ...', ['1,5 N', '5 N', '6 N', '8 N', '9 N'], 3, 'Menurut Hukum II Newton, F = m × a = 2 × 3 = 6 newton.'),
            $this->question(5, 'Seorang siswa memilih mengikuti kursus selama dua jam dan mengorbankan kesempatan bekerja paruh waktu. Dalam ekonomi, nilai kesempatan kerja yang dilepas disebut ...', ['biaya tetap', 'biaya peluang', 'harga keseimbangan', 'keuntungan marginal', 'modal kerja'], 2, 'Biaya peluang adalah nilai alternatif terbaik yang dikorbankan ketika memilih suatu tindakan.'),
        ];
    }

    private function toeflQuestions(): array
    {
        return [
            $this->question(1, '<p><strong>Transcript:</strong> Woman: “Is the seminar still in Room 204?” Man: “No, it has been moved to the main hall.”</p><p>Where will the seminar take place?</p>', ['In Room 204', 'In the main hall', 'In the library', 'In the cafeteria'], 2, 'The man directly states that the seminar has been moved to the main hall.'),
            $this->question(1, '<p><strong>Transcript:</strong> Man: “Did Maya submit the report?” Woman: “She said she would send it before noon.”</p><p>What does the woman imply?</p>', ['Maya has canceled the report', 'The report was sent yesterday', 'Maya plans to send the report before noon', 'The report is not required'], 3, 'The woman reports Maya’s intention to send the report before noon; she does not confirm that it has already been sent.'),
            $this->question(2, 'The collection of rare books _____ in a temperature-controlled room.', ['are kept', 'is kept', 'keep', 'have kept'], 2, 'The subject is the singular noun “collection,” so the correct passive verb is “is kept.”'),
            $this->question(2, '_____ in 1923, the museum now attracts thousands of visitors each year.', ['Founded', 'Founding', 'Was founded', 'It founded'], 1, '“Founded in 1923” is a reduced passive clause modifying “the museum.”'),
            $this->question(3, '<p>Mangrove forests grow along tropical coastlines. Their dense roots slow waves, trap sediment, and provide shelter for young fish. For this reason, restoring mangroves can support both coastal protection and marine biodiversity.</p><p>What is the main idea of the passage?</p>', ['Mangroves grow only in deep water', 'Mangrove restoration offers ecological and coastal benefits', 'Young fish damage tropical coastlines', 'Sediment prevents all waves'], 2, 'The passage explains several connected benefits of mangroves: reducing wave energy, trapping sediment, and supporting fish habitat.'),
            $this->question(3, '<p>Mangrove forests grow along tropical coastlines. Their dense roots slow waves, trap sediment, and provide shelter for young fish.</p><p>The word <em>their</em> refers to ...</p>', ['coastlines', 'waves', 'mangrove forests', 'young fish'], 3, 'The possessive pronoun “their” refers back to the plural noun phrase “mangrove forests.”'),
        ];
    }

    private function toeicQuestions(): array
    {
        return [
            $this->question(1, '<p><strong>Transcript:</strong> Woman: “The client meeting starts at ten, but the conference room is occupied until ten fifteen.” Man: “I’ll ask reception to prepare Room B instead.”</p><p>What will the man probably do?</p>', ['Cancel the client meeting', 'Reserve Room B', 'Move the meeting to tomorrow', 'Call the conference room'], 2, 'He says he will ask reception to prepare Room B, so he will arrange an alternative room.'),
            $this->question(1, '<p><strong>Transcript:</strong> “Passengers for Flight 718 should proceed to Gate 12. Boarding will begin in approximately ten minutes.”</p><p>What are passengers asked to do?</p>', ['Collect their baggage', 'Proceed to Gate 12', 'Wait outside the airport', 'Board immediately'], 2, 'The announcement explicitly directs passengers to Gate 12; boarding begins later.'),
            $this->question(2, 'All expense reports must be submitted _____ Friday afternoon.', ['at', 'by', 'from', 'during'], 2, '“By Friday afternoon” expresses the deadline for submission.'),
            $this->question(2, '<p><strong>Email:</strong> The maintenance team will inspect the elevators on Tuesday from 1:00 to 3:00 p.m. Employees should use the east stairwell during this period.</p><p>What should employees do during the inspection?</p>', ['Use the east stairwell', 'Leave the building', 'Call the maintenance team', 'Use only the elevators'], 1, 'The email directly instructs employees to use the east stairwell during the inspection.'),
        ];
    }

    private function question(
        int $section,
        string $stem,
        array $options,
        int $answer,
        string $discussion,
        array $points = [],
    ): array {
        return [
            'section' => $section,
            'question' => str_starts_with(ltrim($stem), '<') ? $stem : "<p>{$stem}</p>",
            'options' => array_map(fn (string $option) => "<p>{$option}</p>", $options),
            'answer' => $answer,
            'discussion' => "<p>{$discussion}</p>",
            'points' => $points,
        ];
    }
}
