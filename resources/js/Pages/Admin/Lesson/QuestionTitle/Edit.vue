<template>
    <Head>
        <title>{{ appName }} - Edit Judul Soal</title>
    </Head>
    <!--start page wrapper -->
    <div class="page-wrapper">
        <div class="page-content">
            <!--breadcrumb-->
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Judul Soal</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Tambah Judul Soal</li>
                        </ol>
                    </nav>
                </div>
            </div>
            <!--end breadcrumb-->

            <div class="card">
                <div class="card-body">
                    <div class="d-lg-flex align-items-center">
                        <div class="ms-auto">
                            <Link href="/admin/question-titles" class="btn btn-primary mt-2 mt-lg-0">Kembali</Link>
                        </div>
                    </div>
                    <form @submit.prevent="submit" class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Kategori</label>
                            <select v-model="form.category_id" @change="syncProgramProfile" :class="{ 'is-invalid': errors.category_id }" class="form-select">
                                <option value="">[ Pilih ]</option>
                                <option v-for="(category, index) in categories" :key="index" :value="category.id">
                                    {{ category.name }}</option>
                            </select>
                            <div v-if="errors.category_id" class="invalid-feedback">
                                {{ errors.category_id }}
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Judul Soal</label>
                            <input type="text" class="form-control" v-model="form.name" :class="{ 'is-invalid': errors.name }" placeholder="Judul Soal">
                            <div v-if="errors.name" class="invalid-feedback">
                                {{ errors.name }}
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Jumlah Pilihan</label>
                            <input type="number" class="form-control" v-model="form.total_choices" :class="{ 'is-invalid': errors.total_choices }" placeholder="Jumlah Pilihan">
                            <div v-if="errors.total_choices" class="invalid-feedback">
                                {{ errors.total_choices }}
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Jumlah {{ currentProfile.section_label }}</label>
                            <input type="number" min="1" max="100" class="form-control" v-model="form.total_section" :class="{ 'is-invalid': errors.total_section }" placeholder="Jumlah Section">
                            <div class="form-text">{{ currentProfile.section_help }}</div>
                            <div v-if="errors.total_section" class="invalid-feedback">
                                {{ errors.total_section }}
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Tambahkan Kategori Penilaian</label>
                            <select v-model="form.add_value_category" :class="{ 'is-invalid': errors.add_value_category }" class="form-select">
                                <option value="">[ Pilih ]</option>
                                <option value="0">Tidak</option>
                                <option value="1">Ya</option>
                            </select>
                            <div v-if="errors.add_value_category" class="invalid-feedback">
                                {{ errors.add_value_category }}
                            </div>
                        </div>

                        <div class="col-12" v-if="form.add_value_category == 1">
                            <label class="form-label">Jenis Penilaian</label>
                            <select v-model="form.assessment_type" :class="{ 'is-invalid': errors.assessment_type }" class="form-select">
                                <option value="0">[ Pilih ]</option>
                                <option v-for="option in assessmentOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                            <div v-if="selectedAssessmentOption" class="form-text">{{ selectedAssessmentOption.description }}</div>
                            <div v-if="errors.assessment_type" class="invalid-feedback">
                                {{ errors.assessment_type }}
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Tampilkan Koreksi Jawaban</label>
                            <select v-model="form.show_answer" :class="{ 'is-invalid': errors.show_answer }" class="form-select">
                                <option value="">[ Pilih ]</option>
                                <option value="0">Tidak</option>
                                <option value="1">Ya</option>
                            </select>
                            <div v-if="errors.show_answer" class="invalid-feedback">
                                {{ errors.show_answer }}
                            </div>
                        </div>

                        <div class="col-12" v-if="form.show_answer == 1">
                            <label class="form-label">Minimal Nilai Untuk Melihat Jawaban</label>
                            <input type="number" class="form-control" v-model="form.minimum_grade" :class="{ 'is-invalid': errors.minimum_grade }" placeholder="Minimal Nilai Untuk Melihat Jawaban">
                            <div v-if="errors.minimum_grade" class="invalid-feedback">
                                {{ errors.minimum_grade }}
                            </div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary px-5">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--end page wrapper -->
</template>

<script>
    //import layout admin
    import LayoutAdmin from '../../../../Layouts/Layout.vue';

    // import Link
    import { Link } from '@inertiajs/inertia-vue3';

    //import reactive
    import { computed, reactive } from 'vue';

    // import Swal
    import Swal from 'sweetalert2';

    // import Head from Inertia
    import {
        Head
    } from '@inertiajs/inertia-vue3';

    import { Inertia } from '@inertiajs/inertia';

    export default {
        // layout
        layout: LayoutAdmin,

        // register components
        components: {
            Link,
            Head,
        },

        //props
        props: {
            errors: Object,
            categories: Object,
            questionTitle: Object,
            programProfiles: Object,
            sectionCounts: Object,
        },

        // initialization composition API
        setup(props) {
            const form = reactive({
                category_id: props.questionTitle.category_id,
                name: props.questionTitle.name,
                total_choices: props.questionTitle.total_choices,
                total_section: props.questionTitle.total_section,
                add_value_category: props.questionTitle.add_value_category,
                assessment_type: props.questionTitle.assessment_type,
                show_answer: props.questionTitle.show_answer,
                minimum_grade: props.questionTitle.minimum_grade,
            });

            const fallbackProfile = {
                program: 'UMUM',
                section_label: 'Section',
                section_help: 'Section menentukan urutan soal dan hasil.',
                question_assessment_options: [],
            };
            const currentProfile = computed(() => props.programProfiles?.[form.category_id] || fallbackProfile);
            const isStructuredProgram = computed(() => ['CPNS', 'UTBK', 'TKA'].includes(currentProfile.value.program));
            const assessmentOptions = computed(() => currentProfile.value.question_assessment_options || []);
            const selectedAssessmentOption = computed(() => assessmentOptions.value.find(option => Number(option.value) === Number(form.assessment_type)));
            const syncProgramProfile = () => {
                form.assessment_type = assessmentOptions.value[0]?.value || '';

                if (isStructuredProgram.value) {
                    form.add_value_category = 1;
                    form.total_section = Number(props.sectionCounts?.[form.category_id] || 0);
                }
            };

            // submit method
            const submit = () => {
                // send data to server
                Inertia.put(`/admin/question-titles/${props.questionTitle.id}`, {
                    // data
                    category_id: form.category_id,
                    name: form.name,
                    total_choices: form.total_choices,
                    total_section: form.total_section,
                    add_value_category: form.add_value_category,
                    assessment_type: form.assessment_type,
                    show_answer: form.show_answer,
                    minimum_grade: form.minimum_grade,
                }, {
                    onSuccess: () => {
                        //show success alert
                        Swal.fire({
                            title: 'Success!',
                            text: 'Judul Soal Berhasil Diupdate!.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1000
                        });
                    },
                });
            }

            // return form state and submit method
            return {
                form,
                submit,
                currentProfile,
                isStructuredProgram,
                assessmentOptions,
                selectedAssessmentOption,
                syncProgramProfile,
            }
        }
    }
</script>
