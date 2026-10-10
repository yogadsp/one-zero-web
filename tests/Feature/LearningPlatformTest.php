<?php

namespace Tests\Feature;

use App\Models\Exam\Exam;
use App\Models\Exam\Grade;
use App\Models\Learning\LearningMaterial;
use App\Models\Lesson\Question;
use App\Models\Lesson\QuestionTitle;
use App\Models\Lesson\ValueCategory;
use App\Models\MasterData\Category;
use App\Models\MasterData\Student;
use App\Models\Transaction\Transaction;
use App\Models\User;
use App\Services\Transaction\TransactionStatusNotificationService;
use Database\Seeders\ProgramTryoutSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LearningPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_open_catalog_and_owned_packages(): void
    {
        [$user, $exam] = $this->createLearningContext();

        $this->actingAs($user)->get('/user/learning')->assertOk();
        $this->actingAs($user)->get('/user/my-learning-packages')->assertOk();

        Transaction::create(array_merge($this->transactionData($user, $exam, 'done'), [
            'voucher_activated' => 1,
            'voucher_used' => 1,
        ]));

        $response = $this->actingAs($user)->get('/user/my-learning-packages');
        $response->assertOk();
        $this->assertStringContainsString($exam->title, $response->getContent());
        $this->assertSame(1, app(\App\Services\Learning\LearningCatalogService::class)->ownedPackageCountFor($user));
    }

    public function test_member_can_load_voucher_activation_page(): void
    {
        $user = $this->createUser('voucher-page@example.test');

        $this->actingAs($user)
            ->get('/user/voucher-activations')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Transaction/VoucherActivation/Index')
                ->has('voucherActivations.data')
            );
    }

    public function test_learning_material_requires_its_own_activated_purchase(): void
    {
        [$user, $exam] = $this->createLearningContext();
        $material = LearningMaterial::create([
            'exam_id' => null,
            'category_id' => $exam->category_id,
            'type' => LearningMaterial::TYPE_VIDEO,
            'title' => 'Pembahasan TPS',
            'price' => 75000,
            'standalone_since' => now(),
            'url' => 'https://example.com/recording',
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->get("/user/learning-materials/{$material->id}/open")
            ->assertForbidden();

        $this->actingAs($user)
            ->get("/user/learning/video/{$material->id}")
            ->assertOk()
            ->assertDontSee('https://example.com/recording', false);

        $transaction = Transaction::create(array_merge(
            $this->materialTransactionData($user, $material, 'done'),
            ['voucher_token' => 'MATERIAL-ACCESS-01']
        ));

        $this->actingAs($user)
            ->get("/user/learning-materials/{$material->id}/open")
            ->assertForbidden();

        $this->actingAs($user)
            ->post('/user/voucher-activations', ['token' => $transaction->voucher_token])
            ->assertSessionHas('success');

        $this->actingAs($user)
            ->get("/user/learning-materials/{$material->id}/open")
            ->assertRedirect('https://example.com/recording');

        $this->actingAs($user)
            ->get("/user/learning/video/{$material->id}")
            ->assertOk()
            ->assertDontSee('https://example.com/recording', false);

        $this->assertSame([], $user->student->fresh()->member_access ?? []);
    }

    public function test_user_cannot_view_another_users_transaction(): void
    {
        [$owner, $exam] = $this->createLearningContext();
        $otherUser = $this->createUser('other@example.test');
        $transaction = Transaction::create($this->transactionData($owner, $exam, 'pending'));

        $this->actingAs($otherUser)
            ->get("/user/transactions/{$transaction->id}")
            ->assertNotFound();
    }

    public function test_voucher_activation_is_atomic_and_adds_category_access_once(): void
    {
        [$user, $exam] = $this->createLearningContext();
        $transaction = Transaction::create(array_merge(
            $this->transactionData($user, $exam, 'done'),
            ['voucher_token' => 'UTBK-ACCESS-01']
        ));

        $this->actingAs($user)
            ->post('/user/voucher-activations', ['token' => 'UTBK-ACCESS-01'])
            ->assertSessionHas('success');

        $transaction->refresh();
        $user->student->refresh();

        $this->assertTrue((bool) $transaction->voucher_activated);
        $this->assertTrue((bool) $transaction->voucher_used);
        $this->assertSame([['category_id' => $exam->category_id]], $user->student->member_access);

        $this->actingAs($user)
            ->post('/user/voucher-activations', ['token' => 'UTBK-ACCESS-01'])
            ->assertSessionHas('error');

        $user->student->refresh();
        $this->assertCount(1, $user->student->member_access);
    }

    public function test_purchase_endpoint_rejects_get_requests(): void
    {
        [$user, $exam] = $this->createLearningContext();

        $this->actingAs($user)
            ->get("/user/transactions/exams/{$exam->id}/buy")
            ->assertStatus(405);
    }

    public function test_user_can_purchase_and_activate_tryout_video_and_live_class_separately(): void
    {
        Notification::fake();
        [$user, $exam] = $this->createLearningContext();
        $admin = $this->createUser('flow-admin@example.test', 1);
        $video = $this->createStandaloneMaterial($exam, LearningMaterial::TYPE_VIDEO, 'Video TPS Mandiri', 65000);
        $liveClass = $this->createStandaloneMaterial($exam, LearningMaterial::TYPE_LIVE_CLASS, 'Kelas TPS Mandiri', 95000);

        $products = [
            ['url' => "/user/transactions/exams/{$exam->id}/buy", 'column' => 'exam_id', 'id' => $exam->id],
            ['url' => "/user/transactions/learning-materials/{$video->id}/buy", 'column' => 'learning_material_id', 'id' => $video->id],
            ['url' => "/user/transactions/learning-materials/{$liveClass->id}/buy", 'column' => 'learning_material_id', 'id' => $liveClass->id],
        ];

        foreach ($products as $product) {
            $response = $this->actingAs($user)->post($product['url']);
            $transaction = Transaction::query()->where($product['column'], $product['id'])->latest()->firstOrFail();

            $response->assertRedirect(route('user.transactions.show', $transaction->id));
            $this->assertSame('pending', $transaction->transaction_status);

            $this->actingAs($admin)
                ->put("/admin/transactions/{$transaction->id}", ['transaction_status' => 'done'])
                ->assertSessionHas('success');

            $this->actingAs($user)
                ->post('/user/voucher-activations', ['token' => $transaction->fresh()->voucher_token])
                ->assertSessionHas('success');

            $this->assertTrue((bool) $transaction->fresh()->voucher_activated);
        }

        $this->actingAs($user)->get("/user/learning-materials/{$video->id}/open")->assertRedirect($video->url);
        $this->actingAs($user)->get("/user/learning-materials/{$liveClass->id}/open")->assertRedirect($liveClass->url);
        $this->actingAs($user)->get('/user/my-learning-packages?type=tryout')->assertSee($exam->title);
        $this->actingAs($user)->get('/user/my-learning-packages?type=video')->assertSee($video->title);
        $this->actingAs($user)->get('/user/my-learning-packages?type=live_class')->assertSee($liveClass->title);
    }

    public function test_inertia_material_purchase_redirects_to_transaction_detail_without_blank_page(): void
    {
        Notification::fake();
        [$user, $exam] = $this->createLearningContext();
        $video = $this->createStandaloneMaterial($exam, LearningMaterial::TYPE_VIDEO, 'Video Redirect Aman', 50000);

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->post("/user/transactions/learning-materials/{$video->id}/buy");

        $transaction = Transaction::query()->where('learning_material_id', $video->id)->firstOrFail();

        $response->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('user.transactions.show', $transaction->id));
        $this->assertNull($transaction->exam_id);
        $this->assertSame($video->id, $transaction->learning_material_id);
    }

    public function test_grade_history_has_exam_user_created_at_index(): void
    {
        $indexes = collect(DB::select("PRAGMA index_list('grades')"))->pluck('name');

        $this->assertTrue($indexes->contains('grades_exam_user_history_index'));
    }

    public function test_transaction_must_reference_exactly_one_product(): void
    {
        [$user, $exam] = $this->createLearningContext();
        $material = $this->createStandaloneMaterial($exam, LearningMaterial::TYPE_VIDEO, 'Video Constraint', 40000);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Transaction::create(array_merge($this->transactionData($user, $exam, 'pending'), [
            'learning_material_id' => $material->id,
        ]));
    }

    public function test_admin_material_form_rejects_non_http_links(): void
    {
        [, $exam] = $this->createLearningContext();
        $admin = $this->createUser('admin@example.test', 1);

        $this->actingAs($admin)
            ->post('/admin/learning-materials', [
                'category_id' => $exam->category_id,
                'type' => LearningMaterial::TYPE_LIVE_CLASS,
                'title' => 'Kelas TPS Live',
                'price' => 85000,
                'url' => 'javascript:alert(1)',
                'is_published' => true,
                'position' => 0,
            ])
            ->assertSessionHasErrors(['url', 'starts_at']);

        $this->assertDatabaseCount('learning_materials', 0);
    }

    public function test_admin_can_create_a_published_learning_material(): void
    {
        [, $exam] = $this->createLearningContext();
        $admin = $this->createUser('admin@example.test', 1);

        $this->actingAs($admin)
            ->post('/admin/learning-materials', [
                'category_id' => $exam->category_id,
                'type' => LearningMaterial::TYPE_LIVE_CLASS,
                'title' => 'Kelas TPS Live',
                'price' => 85000,
                'description' => 'Sesi pendalaman materi.',
                'url' => 'https://zoom.example.test/session',
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'is_published' => true,
                'position' => 1,
            ])
            ->assertRedirect('/admin/learning-materials');

        $this->assertDatabaseHas('learning_materials', [
            'exam_id' => null,
            'category_id' => $exam->category_id,
            'type' => LearningMaterial::TYPE_LIVE_CLASS,
            'title' => 'Kelas TPS Live',
            'price' => 85000,
            'is_published' => 1,
        ]);

        $material = LearningMaterial::query()->firstOrFail();

        $this->actingAs($admin)
            ->put("/admin/learning-materials/{$material->id}", [
                'category_id' => $exam->category_id,
                'type' => LearningMaterial::TYPE_VIDEO,
                'title' => 'Rekaman Pembahasan TPS',
                'price' => 65000,
                'description' => 'Rekaman kelas yang telah selesai.',
                'url' => 'https://video.example.test/recording',
                'is_published' => false,
                'position' => 2,
            ])
            ->assertRedirect('/admin/learning-materials');

        $this->assertDatabaseHas('learning_materials', [
            'id' => $material->id,
            'category_id' => $exam->category_id,
            'type' => LearningMaterial::TYPE_VIDEO,
            'title' => 'Rekaman Pembahasan TPS',
            'price' => 65000,
            'is_published' => 0,
            'position' => 2,
        ]);

        $this->actingAs($admin)
            ->delete("/admin/learning-materials/{$material->id}")
            ->assertRedirect('/admin/learning-materials');

        $this->assertSoftDeleted('learning_materials', ['id' => $material->id]);
    }

    public function test_exam_program_presets_seed_utbk_tka_and_structured_sections(): void
    {
        $this->assertDatabaseHas('categories', ['name' => 'UTBK']);
        $this->assertDatabaseHas('categories', ['name' => 'TKA']);

        $utbkId = Category::query()->where('name', 'UTBK')->value('id');
        $tkaId = Category::query()->where('name', 'TKA')->value('id');

        $this->assertDatabaseCount('value_categories', 15);
        $this->assertDatabaseHas('value_categories', [
            'category_id' => $utbkId,
            'name' => 'Penalaran Matematika',
            'section' => 7,
        ]);
        $this->assertDatabaseHas('value_categories', [
            'category_id' => $tkaId,
            'name' => 'Bahasa Inggris',
            'section' => 3,
        ]);
        $this->assertDatabaseHas('value_categories', [
            'category_id' => $tkaId,
            'name' => 'Mata Pelajaran Pilihan 2',
            'section' => 5,
        ]);
    }

    public function test_cpns_value_category_rejects_non_option_point_scoring(): void
    {
        $admin = $this->createUser('assessment-admin@example.test', 1);
        $cpnsId = Category::query()->where('name', 'CPNS')->value('id');

        $this->actingAs($admin)
            ->post('/admin/value-categories', [
                'category_id' => $cpnsId,
                'name' => 'Simulasi tambahan',
                'assessment_type' => 1,
                'section' => 4,
            ])
            ->assertSessionHasErrors(['assessment_type']);

        $this->assertDatabaseMissing('value_categories', ['name' => 'Simulasi tambahan']);
    }

    public function test_cpns_question_requires_points_for_every_configured_option(): void
    {
        $admin = $this->createUser('cpns-question-admin@example.test', 1);
        $cpnsId = Category::query()->where('name', 'CPNS')->value('id');
        $valueCategory = ValueCategory::query()->where('category_id', $cpnsId)->where('section', 1)->firstOrFail();
        $questionTitle = QuestionTitle::create([
            'category_id' => $cpnsId,
            'name' => 'Validasi Soal CPNS',
            'total_choices' => 4,
            'total_section' => 3,
            'add_value_category' => 1,
            'assessment_type' => 1,
            'show_answer' => 0,
        ]);
        $payload = [
            'question_title_id' => $questionTitle->id,
            'value_category_id' => $valueCategory->id,
            'type' => 1,
            'audio_input' => 0,
            'question' => '<p>Contoh soal</p>',
            'option_1' => '<p>A</p>',
            'option_2' => '<p>B</p>',
            'option_3' => '<p>C</p>',
            'option_4' => '<p>D</p>',
            'answer' => 1,
            'option_has_point' => 0,
        ];

        $this->actingAs($admin)
            ->post("/admin/question-titles/{$questionTitle->id}/questions", $payload)
            ->assertSessionHasErrors(['option_has_point', 'option_1_point']);

        $this->actingAs($admin)
            ->post("/admin/question-titles/{$questionTitle->id}/questions", array_merge($payload, [
                'option_has_point' => 1,
                'option_1_point' => 5,
                'option_2_point' => 0,
                'option_3_point' => 0,
                'option_4_point' => 0,
                'discussion' => '<p>Penjelasan jawaban CPNS.</p>',
            ]))
            ->assertRedirect("/admin/question-titles/{$questionTitle->id}/questions");

        $this->assertDatabaseHas('questions', [
            'question_title_id' => $questionTitle->id,
            'option_has_point' => 1,
            'option_1_point' => 5,
            'option_2_point' => 0,
            'discussion' => '<p>Penjelasan jawaban CPNS.</p>',
        ]);
    }

    public function test_conversion_range_rejects_a_maximum_below_its_minimum(): void
    {
        $admin = $this->createUser('conversion-admin@example.test', 1);
        $utbkId = Category::query()->where('name', 'UTBK')->value('id');
        $valueCategory = ValueCategory::query()->where('category_id', $utbkId)->firstOrFail();

        $this->actingAs($admin)
            ->post("/admin/value-categories/{$valueCategory->id}/detail-value-categories", [
                'value_category_id' => $valueCategory->id,
                'min_grade' => 36,
                'max_grade' => 35,
                'final_grade' => 52,
            ])
            ->assertSessionHasErrors(['max_grade']);

        $this->assertDatabaseMissing('detail_value_categories', [
            'value_category_id' => $valueCategory->id,
            'min_grade' => 36,
            'max_grade' => 35,
        ]);
    }

    public function test_finished_exam_review_displays_current_discussion_and_is_owner_scoped(): void
    {
        [$user, $exam] = $this->createLearningContext();
        $otherUser = $this->createUser('review-attacker@example.test');
        // The exam-level setting is authoritative for legacy TOEFL data, where
        // the related question-title flag can still be disabled.
        $exam->questionTitle()->update(['show_answer' => 0, 'minimum_grade' => 0]);
        $valueCategory = ValueCategory::create([
            'category_id' => $exam->category_id,
            'name' => 'Penalaran Umum',
            'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE,
            'section' => 1,
        ]);
        $question = Question::create([
            'question_title_id' => $exam->question_title_id,
            'value_category_id' => $valueCategory->id,
            'type' => 1,
            'audio_input' => 0,
            'question' => '<p>Soal review</p>',
            'option_1' => '<p>Benar</p>',
            'option_2' => '<p>Salah</p>',
            'answer' => 1,
            'discussion' => '<p>Pembahasan terbaru dari bank soal.</p>',
            'section' => 1,
            'option_has_point' => 0,
        ]);
        $grade = Grade::create([
            'category_id' => $exam->category_id,
            'exam_id' => $exam->id,
            'user_id' => $user->id,
            'duration' => 120,
            'number' => 0,
            'section' => 1,
            'total_section' => 1,
            'start_time' => now()->subHour(),
            'end_time' => now(),
            'total_correct' => 1,
            'grade_old' => 100,
            'grade' => 100,
            'answers' => [[
                'question_id' => $question->id,
                'value_category_id' => $valueCategory->id,
                'value_category_name' => $valueCategory->name,
                'type' => 1,
                'question_order' => 1,
                'navigation_order' => 1,
                'question' => $question->question,
                'question_answer' => 1,
                'option_1' => $question->option_1,
                'option_2' => $question->option_2,
                'answer' => 1,
                'is_correct' => 'Y',
                'section' => 1,
                'discussion' => null,
            ]],
            'is_finished' => 1,
            'total_tolerance' => 3,
            'is_blocked' => 0,
        ]);

        $this->actingAs($user)
            ->get("/user/grades/{$grade->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/TryOut/Grade/Show')
                ->where('canReviewAnswers', true)
                ->where('answers.0.discussion', '<p>Pembahasan terbaru dari bank soal.</p>')
            );

        $this->actingAs($user)
            ->get("/user/grades/show-answer/{$exam->id}/{$grade->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/TryOut/Grade/ShowAnswer')
                ->where('answers.0.review_number', 1)
                ->where('answers.0.discussion', '<p>Pembahasan terbaru dari bank soal.</p>')
            );

        $this->actingAs($otherUser)
            ->get("/user/grades/{$grade->id}")
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->get("/user/grades/show-answer/{$exam->id}/{$grade->id}")
            ->assertNotFound();

        $grade->update(['is_finished' => 0]);

        $this->actingAs($user)
            ->get("/user/grades/{$grade->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canReviewAnswers', false)
                ->has('answers', 0)
            );

        $this->actingAs($user)
            ->get("/user/grades/show-answer/{$exam->id}/{$grade->id}")
            ->assertNotFound();
    }

    public function test_answer_review_respects_exam_visibility_setting(): void
    {
        [$user, $exam] = $this->createLearningContext();
        $exam->questionTitle()->update(['show_answer' => 0, 'minimum_grade' => 0]);
        $exam->update(['show_answer' => 0]);
        $grade = Grade::create([
            'category_id' => $exam->category_id,
            'exam_id' => $exam->id,
            'user_id' => $user->id,
            'duration' => 120,
            'number' => 0,
            'section' => 1,
            'total_section' => 1,
            'start_time' => now()->subHour(),
            'end_time' => now(),
            'total_correct' => 0,
            'grade_old' => 0,
            'grade' => 0,
            'answers' => [],
            'is_finished' => 1,
            'total_tolerance' => 3,
            'is_blocked' => 0,
        ]);

        $this->actingAs($user)
            ->get("/user/grades/show-answer/{$exam->id}/{$grade->id}")
            ->assertRedirect("/user/grades/{$grade->id}")
            ->assertSessionHas('error');
    }

    public function test_sample_tryouts_are_idempotent_complete_and_finishable_for_every_program(): void
    {
        $user = $this->createUser('usertesting3@mail.com');
        Student::create([
            'user_id' => $user->id,
            'province_id' => 1,
            'city_id' => 1,
            'district_id' => 1,
            'village_id' => 1,
            'address' => 'Bandar Lampung',
            'phone_number' => '081234567899',
            'gender' => 'M',
            'is_member' => 1,
        ]);

        $seeder = app(ProgramTryoutSampleSeeder::class);
        $seeder->run();
        $seeder->run();

        $expectedQuestionCounts = [
            'CPNS' => 6,
            'UTBK' => 7,
            'TKA' => 5,
            'TOEFL' => 6,
            'TOEIC' => 4,
        ];
        $expectedScores = [
            'CPNS' => 30.0,
            'UTBK' => 700.0,
            'TKA' => 500.0,
            'TOEFL' => 233.0,
            'TOEIC' => 10.0,
        ];

        $exams = Exam::query()
            ->with(['category', 'questionTitle'])
            ->where('title', 'like', '[SIMULASI]%')
            ->get()
            ->keyBy(fn (Exam $exam) => strtoupper($exam->category->name));

        $this->assertCount(5, $exams);
        $this->assertSame(5, Transaction::query()->where('user_id', $user->id)->where('voucher_activated', 1)->count());

        foreach ($expectedQuestionCounts as $program => $questionCount) {
            $exam = $exams->get($program);
            $this->assertNotNull($exam, "Paket sampel {$program} tidak ditemukan.");
            $this->assertSame(1, (int) $exam->show_answer);
            $this->assertSame(1, (int) $exam->questionTitle->show_answer);

            $questions = Question::query()
                ->where('question_title_id', $exam->question_title_id)
                ->orderBy('created_at')
                ->get();

            $this->assertCount($questionCount, $questions);
            $this->assertSame($questionCount, $questions->filter(fn (Question $question) => filled($question->discussion))->count());
            $this->assertSame(
                range(1, (int) $exam->questionTitle->total_section),
                $questions->pluck('section')->unique()->sort()->values()->all(),
            );

            $this->actingAs($user)
                ->get("/user/exams/{$exam->id}/exam-start")
                ->assertRedirect("/user/exams/{$exam->id}/sections/1");

            $grade = Grade::query()->where('exam_id', $exam->id)->where('user_id', $user->id)->firstOrFail();

            foreach (range(1, (int) $exam->questionTitle->total_section) as $section) {
                $url = "/user/exams/{$exam->id}/sections/{$section}".($section > 1 ? '?nextsection=1' : '');
                $this->actingAs($user)
                    ->get($url)
                    ->assertOk()
                    ->assertInertia(fn (Assert $page) => $page
                        ->component('User/TryOut/Exam/Question')
                        ->where('section', $section)
                    );

                foreach ($questions->where('section', $section) as $number => $question) {
                    $this->actingAs($user)
                        ->post("/user/exams/{$exam->id}/exam-answer", [
                            'grade_id' => $grade->id,
                            'question_id' => $question->id,
                            'answer' => $question->answer,
                            'number' => $number,
                        ])
                        ->assertOk();
                }
            }

            $this->actingAs($user)
                ->post("/user/exams/{$exam->id}/exam-end", ['grade_id' => $grade->id])
                ->assertRedirect("/user/categories/{$exam->category_id}/exams/{$exam->id}");

            $grade->refresh();
            $this->assertSame(1, (int) $grade->is_finished);
            $this->assertSame($expectedScores[$program], (float) $grade->getRawOriginal('grade'));
            $this->assertSame((int) $exam->questionTitle->total_section, $grade->gradeDetail()->count());

            $this->actingAs($user)
                ->get("/user/grades/show-answer/{$exam->id}/{$grade->id}")
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('User/TryOut/Grade/ShowAnswer')
                    ->has('answers', $questionCount)
                );
        }
    }

    public function test_empty_unfinished_attempt_is_rebuilt_without_changing_its_identity(): void
    {
        [$user, $exam] = $this->createLearningContext();
        $user->student()->update(['is_member' => 1]);
        $valueCategory = ValueCategory::create([
            'category_id' => $exam->category_id,
            'name' => 'Penalaran Umum',
            'assessment_type' => ValueCategory::ASSESSMENT_PERCENTAGE,
            'section' => 1,
        ]);
        $question = Question::create([
            'question_title_id' => $exam->question_title_id,
            'value_category_id' => $valueCategory->id,
            'type' => 1,
            'audio_input' => 0,
            'question' => '<p>Soal UTBK</p>',
            'option_1' => '<p>A</p>',
            'option_2' => '<p>B</p>',
            'answer' => 1,
            'section' => 1,
            'option_has_point' => 1,
            'option_1_point' => 5,
            'option_2_point' => 0,
        ]);
        Transaction::create(array_merge($this->transactionData($user, $exam, 'done'), [
            'voucher_activated' => 1,
            'voucher_used' => 1,
        ]));
        $grade = Grade::create([
            'category_id' => $exam->category_id,
            'exam_id' => $exam->id,
            'user_id' => $user->id,
            'duration' => 120,
            'number' => 0,
            'section' => 1,
            'total_section' => 1,
            'start_time' => now(),
            'end_time' => now()->addMinutes(120),
            'total_correct' => 0,
            'answers' => [],
            'is_finished' => 0,
            'total_tolerance' => 3,
        ]);

        $this->actingAs($user)
            ->get("/user/exams/{$exam->id}/exam-start")
            ->assertRedirect("/user/exams/{$exam->id}/sections/1");

        $grade->refresh();
        $this->assertSame($question->id, $grade->answers[0]['question_id']);
        $this->assertSame(0, (int) $grade->answers[0]['option_2_point']);
        $this->assertCount(1, $grade->answers);

        $this->actingAs($user)
            ->get("/user/exams/{$exam->id}/sections/1")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/TryOut/Exam/Question')
                ->where('questionLists.0.question_id', $question->id)
                ->where('questionLists.0.value_category_name', 'Penalaran Umum')
                ->where('section', 1)
                ->where('lastSection', 1)
            );

        $this->actingAs($user)
            ->post("/user/exams/{$exam->id}/exam-answer", [
                'grade_id' => $grade->id,
                'question_id' => $question->id,
                'answer' => 1,
                'number' => 0,
            ])
            ->assertOk();

        $this->actingAs($user)
            ->post("/user/exams/{$exam->id}/exam-end", ['grade_id' => $grade->id])
            ->assertRedirect("/user/categories/{$exam->category_id}/exams/{$exam->id}");

        $grade->refresh();
        $this->assertSame(1, (int) $grade->is_finished);
        $this->assertSame(100.0, (float) str_replace(',', '', $grade->grade));
    }

    public function test_editor_upload_requires_admin_and_rejects_non_images(): void
    {
        Storage::fake('public');

        $this->post('/upload', [
            'file' => UploadedFile::fake()->create('payload.txt', 1, 'text/plain'),
        ])->assertRedirect('/login');

        $admin = $this->createUser('editor-admin@example.test', 1);

        $this->actingAs($admin)
            ->post('/upload', [
                'file' => UploadedFile::fake()->create('payload.txt', 1, 'text/plain'),
            ])
            ->assertSessionHasErrors(['file']);

        $response = $this->actingAs($admin)
            ->post('/upload', ['file' => UploadedFile::fake()->image('diagram.png')])
            ->assertOk()
            ->assertJsonStructure(['location']);

        Storage::disk('public')->assertExists(
            str($response->json('location'))->after('/storage/')->toString(),
        );
    }

    public function test_member_cannot_submit_an_answer_to_another_members_grade(): void
    {
        [$owner, $exam] = $this->createLearningContext();
        $otherUser = $this->createUser('answer-attacker@example.test');
        $questionId = (string) Str::uuid();
        $grade = Grade::create([
            'category_id' => $exam->category_id,
            'exam_id' => $exam->id,
            'user_id' => $owner->id,
            'duration' => 120,
            'number' => 0,
            'section' => 1,
            'total_section' => 1,
            'total_correct' => 0,
            'answers' => [[
                'question_id' => $questionId,
                'question_answer' => 1,
                'answer' => 0,
                'is_correct' => 'N',
                'type' => 1,
            ]],
        ]);

        $this->actingAs($otherUser)
            ->post("/user/exams/{$exam->id}/exam-answer", [
                'grade_id' => $grade->id,
                'question_id' => $questionId,
                'answer' => 1,
                'number' => 1,
            ])
            ->assertNotFound();

        $this->assertSame(0, $grade->fresh()->number);
    }

    public function test_inertia_material_creation_forces_a_fresh_admin_page(): void
    {
        [, $exam] = $this->createLearningContext();
        $admin = $this->createUser('inertia-admin@example.test', 1);

        $this->actingAs($admin)
            ->withHeader('X-Inertia', 'true')
            ->post('/admin/learning-materials', [
                'category_id' => $exam->category_id,
                'type' => LearningMaterial::TYPE_VIDEO,
                'title' => 'Rekaman TPS',
                'price' => 50000,
                'url' => 'https://video.example.test/tps',
                'is_published' => true,
                'position' => 0,
            ])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('admin.learning-materials.index'));

        $this->assertDatabaseHas('learning_materials', ['title' => 'Rekaman TPS']);
    }

    public function test_inertia_login_uses_a_full_page_redirect_to_the_role_dashboard(): void
    {
        $member = $this->createUser('login-member@example.test');

        $this->withHeader('X-Inertia', 'true')
            ->post('/login', [
                'email' => $member->email,
                'password' => 'password',
            ])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', '/user/dashboard');
    }

    public function test_admin_status_update_generates_one_stable_voucher_and_redirects(): void
    {
        [$user, $exam] = $this->createLearningContext();
        $admin = $this->createUser('transaction-admin@example.test', 1);
        $transaction = Transaction::create($this->transactionData($user, $exam, 'pending'));

        $notifier = $this->mock(TransactionStatusNotificationService::class);
        $notifier->shouldReceive('send')->once();

        $this->actingAs($admin)
            ->put("/admin/transactions/{$transaction->id}", ['transaction_status' => 'done'])
            ->assertRedirect("/admin/transactions/{$transaction->id}")
            ->assertSessionHas('success');

        $transaction->refresh();
        $this->assertSame('done', $transaction->transaction_status);
        $this->assertNotNull($transaction->voucher_token);
        $originalToken = $transaction->voucher_token;

        $this->actingAs($admin)
            ->put("/admin/transactions/{$transaction->id}", ['transaction_status' => 'done'])
            ->assertSessionHas('success');

        $this->assertSame($originalToken, $transaction->fresh()->voucher_token);
    }

    public function test_completed_transaction_cannot_be_reopened(): void
    {
        [$user, $exam] = $this->createLearningContext();
        $admin = $this->createUser('immutable-admin@example.test', 1);
        $transaction = Transaction::create(array_merge(
            $this->transactionData($user, $exam, 'done'),
            ['voucher_token' => 'IMMUTABLE-ACCESS-01']
        ));

        $this->mock(TransactionStatusNotificationService::class)
            ->shouldNotReceive('send');

        $this->actingAs($admin)
            ->put("/admin/transactions/{$transaction->id}", ['transaction_status' => 'paid'])
            ->assertSessionHas('failed');

        $this->assertSame('done', $transaction->fresh()->transaction_status);
    }

    public function test_admin_can_soft_delete_pending_transaction_and_user_can_purchase_again(): void
    {
        Notification::fake();
        [$user, $exam] = $this->createLearningContext();
        $admin = $this->createUser('delete-admin@example.test', 1);

        $this->actingAs($user)
            ->post("/user/transactions/exams/{$exam->id}/buy")
            ->assertRedirect();

        $original = Transaction::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($admin)
            ->delete("/admin/transactions/{$original->id}")
            ->assertRedirect('/admin/transactions')
            ->assertSessionHas('success');

        $this->assertSoftDeleted('transactions', [
            'id' => $original->id,
            'deleted_by' => $admin->id,
            'deletion_source' => 'admin',
        ]);

        $this->actingAs($user)
            ->post("/user/transactions/exams/{$exam->id}/buy")
            ->assertRedirect();

        $replacement = Transaction::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertNotSame($original->id, $replacement->id);
        $this->assertNotSame($original->code, $replacement->code);
    }

    public function test_user_can_soft_delete_own_pending_or_failed_transaction(): void
    {
        [$user, $exam] = $this->createLearningContext();

        foreach (['pending', 'failed'] as $status) {
            $transaction = Transaction::create($this->transactionData($user, $exam, $status));

            $this->actingAs($user)
                ->delete("/user/transactions/{$transaction->id}")
                ->assertRedirect('/user/transactions')
                ->assertSessionHas('success');

            $this->assertSoftDeleted('transactions', [
                'id' => $transaction->id,
                'deleted_by' => $user->id,
                'deletion_source' => 'user',
            ]);
        }
    }

    public function test_inertia_transaction_cancellation_refreshes_the_transaction_list(): void
    {
        [$user, $exam] = $this->createLearningContext();
        $transaction = Transaction::create($this->transactionData($user, $exam, 'pending'));

        $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->delete("/user/transactions/{$transaction->id}")
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('user.transactions.index'));

        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_user_cannot_delete_another_users_transaction(): void
    {
        [$owner, $exam] = $this->createLearningContext();
        $otherUser = $this->createUser('other-buyer@example.test');
        $transaction = Transaction::create($this->transactionData($owner, $exam, 'pending'));

        $this->actingAs($otherUser)
            ->delete("/user/transactions/{$transaction->id}")
            ->assertNotFound();

        $this->assertNotSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_paid_and_completed_transactions_cannot_be_deleted(): void
    {
        [$user, $exam] = $this->createLearningContext();
        $admin = $this->createUser('protected-delete-admin@example.test', 1);
        $paid = Transaction::create($this->transactionData($user, $exam, 'paid'));
        $done = Transaction::create(array_merge(
            $this->transactionData($user, $exam, 'done'),
            ['voucher_token' => 'PROTECTED-DELETE-01']
        ));

        $this->actingAs($user)
            ->delete("/user/transactions/{$paid->id}")
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->delete("/admin/transactions/{$done->id}")
            ->assertSessionHas('failed');

        $this->assertNotSoftDeleted('transactions', ['id' => $paid->id]);
        $this->assertNotSoftDeleted('transactions', ['id' => $done->id]);
    }

    private function createLearningContext(): array
    {
        $user = $this->createUser('member@example.test');
        Student::create([
            'user_id' => $user->id,
            'province_id' => 1,
            'city_id' => 1,
            'district_id' => 1,
            'village_id' => 1,
            'address' => 'Bandar Lampung',
            'phone_number' => '081234567890',
            'gender' => 'M',
            'is_member' => 0,
        ]);

        $category = Category::create(['name' => 'UTBK']);
        $questionTitle = QuestionTitle::create([
            'category_id' => $category->id,
            'name' => 'Tes Potensi Skolastik',
            'total_choices' => 5,
            'total_section' => 1,
            'add_value_category' => 0,
            'assessment_type' => 2,
            'show_answer' => 1,
        ]);
        $exam = Exam::create([
            'category_id' => $category->id,
            'question_title_id' => $questionTitle->id,
            'title' => 'Paket UTBK Intensif',
            'price' => 150000,
            'duration_type' => 1,
            'duration' => 120,
            'description' => 'Paket persiapan UTBK.',
            'random_question' => 1,
            'random_answer' => 1,
            'show_answer' => 1,
            'show_question_number_navigation' => 1,
            'show_question_number' => 1,
            'next_question_automatically' => 0,
            'show_prev_next_button' => 1,
            'type_option' => 1,
            'total_tolerance' => 3,
        ]);

        return [$user->fresh(), $exam];
    }

    private function createUser(string $email, int $level = 2): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('password'),
            'level' => $level,
            'is_active' => 1,
        ]);
    }

    private function transactionData(User $user, Exam $exam, string $status): array
    {
        return [
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'code' => 'INV'.strtoupper(substr($status, 0, 2)).random_int(1000, 9999),
            'voucher_activated' => 0,
            'voucher_used' => 0,
            'total_purchases' => $exam->price,
            'maximum_payment_time' => now()->addDay(),
            'transaction_status' => $status,
        ];
    }

    private function materialTransactionData(User $user, LearningMaterial $material, string $status): array
    {
        return [
            'user_id' => $user->id,
            'exam_id' => null,
            'learning_material_id' => $material->id,
            'code' => 'INVMAT'.random_int(1000, 9999),
            'voucher_activated' => 0,
            'voucher_used' => 0,
            'total_purchases' => $material->price,
            'maximum_payment_time' => now()->addDay(),
            'transaction_status' => $status,
        ];
    }

    private function createStandaloneMaterial(Exam $exam, string $type, string $title, int $price): LearningMaterial
    {
        return LearningMaterial::create([
            'exam_id' => null,
            'category_id' => $exam->category_id,
            'type' => $type,
            'title' => $title,
            'price' => $price,
            'standalone_since' => now(),
            'description' => 'Produk belajar mandiri.',
            'url' => $type === LearningMaterial::TYPE_VIDEO
                ? 'https://video.example.test/standalone'
                : 'https://zoom.example.test/standalone',
            'starts_at' => $type === LearningMaterial::TYPE_LIVE_CLASS ? now()->addDay() : null,
            'is_published' => true,
            'position' => 1,
        ]);
    }
}
