<template>
  <Head>
    <title>{{ appName }} - Impor Soal</title>
  </Head>

  <div class="page-wrapper">
    <div class="page-content">
      <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Impor Soal</div>
        <div class="ps-3 text-muted">{{ questionTitle.name }}</div>
      </div>

      <div class="row">
        <div class="col-xl-7">
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                <div>
                  <h5 class="mb-1">Unggah DOCX atau PDF</h5>
                  <p class="text-muted mb-0">Berkas diparsing dan divalidasi dahulu. Data belum masuk database sebelum Anda menekan tombol impor.</p>
                </div>
                <Link :href="`/admin/question-titles/${questionTitle.id}/questions`" class="btn btn-outline-primary btn-sm">Kembali</Link>
              </div>

              <form @submit.prevent="submitPreview">
                <label for="question-document" class="form-label">Dokumen soal</label>
                <input
                  id="question-document"
                  class="form-control"
                  :class="{ 'is-invalid': previewForm.errors.document || $page.props.errors?.document }"
                  type="file"
                  accept=".docx,.pdf,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword"
                  @change="selectDocument"
                  @input="selectDocument" />
                <div v-if="previewForm.errors.document" class="invalid-feedback">{{ previewForm.errors.document }}</div>
                <div v-else-if="$page.props.errors?.document" class="invalid-feedback d-block">{{ $page.props.errors.document }}</div>
                <div v-if="selectedFile" class="text-success small mt-1">
                  <i class="bx bx-check-circle me-1"></i> Berkas terpilih: <strong>{{ selectedFile.name }}</strong> ({{ (selectedFile.size / 1024).toFixed(1) }} KB)
                </div>
                <div class="form-text">Maksimal 5 MB, paling banyak 200 soal. PDF harus berisi teks, bukan hanya hasil scan gambar.</div>

                <div class="d-flex gap-2 mt-3">
                  <button class="btn btn-primary" type="button" :disabled="previewForm.processing" @click="submitPreview">
                    <span v-if="previewForm.processing" class="spinner-border spinner-border-sm me-1"></span>
                    Periksa dan Tampilkan Pratinjau
                  </button>
                  <a :href="templateDownloadUrl" class="btn btn-outline-secondary" download>
                    <i class="bx bx-download me-1"></i> Unduh Format {{ questionTitle.category?.name || 'Word' }}
                  </a>
                </div>
              </form>
            </div>
          </div>

          <div v-if="preview" id="preview-result-card" class="card">
            <div class="card-body">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                  <h5 class="mb-1">Hasil Pemeriksaan</h5>
                  <div class="text-muted">{{ preview.file_name }} · {{ preview.total }} soal ditemukan</div>
                </div>
                <span class="badge" :class="preview.valid ? 'bg-success' : 'bg-danger'">
                  {{ preview.valid ? 'Siap diimpor' : 'Perlu diperbaiki' }}
                </span>
              </div>

              <div v-if="!preview.valid" class="alert alert-warning">
                Perbaiki kesalahan pada dokumen, lalu unggah kembali. Tidak ada data yang disimpan dari pratinjau ini.
              </div>
              <div v-if="commitForm.errors.import" class="alert alert-danger">{{ commitForm.errors.import }}</div>

              <div v-for="question in preview.questions" :key="question.number" class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                  <strong>Soal {{ question.number }}</strong>
                  <span class="text-muted small">Baris sumber {{ question.line }}</span>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                  <span class="badge bg-light-primary text-primary">Bagian {{ question.section || '-' }}</span>
                  <span class="badge bg-light-info text-info">{{ question.value_category || 'Kategori belum dikenali' }}</span>
                </div>

                <div v-if="question.errors.length" class="alert alert-danger py-2">
                  <ul class="mb-0 ps-3">
                    <li v-for="error in question.errors" :key="error">{{ error }}</li>
                  </ul>
                </div>

                <p class="mb-2" style="white-space: pre-line">{{ question.question }}</p>
                <ol type="A" class="mb-2">
                  <li v-for="(option, letter) in question.options" :key="letter" :class="{ 'text-success fw-bold': question.answer === letter }">
                    <span style="white-space: pre-line">{{ option }}</span>
                    <span v-if="question.points[letter] !== undefined" class="badge bg-light-secondary text-secondary ms-2">{{ question.points[letter] }} poin</span>
                  </li>
                </ol>
                <div><strong>Kunci:</strong> {{ question.answer || '-' }}</div>
                <div class="mt-2"><strong>Pembahasan:</strong> <span style="white-space: pre-line">{{ question.discussion || '-' }}</span></div>
              </div>

              <button v-if="preview.valid" class="btn btn-success" type="button" :disabled="commitForm.processing" @click="commitImport">
                <span v-if="commitForm.processing" class="spinner-border spinner-border-sm me-1"></span>
                Impor {{ preview.total }} Soal ke Database
              </button>
            </div>
          </div>
        </div>

        <div class="col-xl-5">
          <div class="card">
            <div class="card-body">
              <h5 class="mb-2">Format Dokumen</h5>
              <p class="text-muted small">Format disesuaikan untuk program <strong>{{ questionTitle.category?.name }}</strong> ({{ questionTitle.total_choices }} pilihan). BAGIAN dan KATEGORI berlaku untuk soal-soal setelahnya sampai label tersebut diganti.</p>
              <pre class="bg-light border rounded p-3 small text-dark" style="white-space: pre-wrap">{{ sampleFormatText }}</pre>

              <div v-if="hasOptionPoints" class="mt-3">
                <p class="mb-1"><strong>Kategori berbobot opsi</strong></p>
                <p class="small text-muted mb-0">Untuk kategori berbobot opsi, seluruh poin A sampai pilihan terakhir wajib ditulis (contoh: <code>POIN A: 1</code> dst). Label <code>BOBOT A</code> juga diterima.</p>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <h5>Konfigurasi Bank Soal</h5>
              <table class="table table-sm mb-3">
                <tbody>
                  <tr><th>Program</th><td>{{ questionTitle.category.name }}</td></tr>
                  <tr><th>Jumlah pilihan</th><td>{{ questionTitle.total_choices }}</td></tr>
                  <tr><th>Jumlah bagian</th><td>{{ questionTitle.total_section }}</td></tr>
                </tbody>
              </table>
              <div v-for="category in valueCategories" :key="category.id" class="d-flex justify-content-between border-bottom py-2">
                <span>{{ category.name }}</span>
                <span class="badge bg-light-primary text-primary">Bagian {{ category.section }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import LayoutAdmin from "../../../../Layouts/Layout.vue";
import { Head, Link, useForm } from "@inertiajs/inertia-vue3";
import { nextTick, ref, watch } from "vue";
import Swal from "sweetalert2";

export default {
  layout: LayoutAdmin,
  components: { Head, Link },
  props: {
    questionTitle: Object,
    valueCategories: Array,
    preview: Object,
    templateDownloadUrl: String,
    sampleFormatText: String,
    hasOptionPoints: Boolean,
  },
  setup(props) {
    const previewForm = useForm({ document: null });
    const commitForm = useForm({ token: "" });
    const selectedFile = ref(null);
    // Keep a local render state as well as the Inertia prop. The legacy
    // Inertia/Vue adapter used by this application can receive the JSON page
    // response successfully while a persistent page does not repaint from the
    // updated prop immediately.
    const preview = ref(props.preview || null);

    watch(() => props.preview, (value) => {
      preview.value = value || null;
    });

    const selectDocument = (event) => {
      const file = event.target.files?.[0] || null;
      previewForm.document = file;
      selectedFile.value = file;
      if (file) {
        previewForm.clearErrors();
      }
    };

    const submitPreview = () => {
      if (!previewForm.document) {
        Swal.fire({
          icon: "warning",
          title: "Pilih Dokumen",
          text: "Silakan pilih berkas dokumen DOCX atau PDF terlebih dahulu sebelum memeriksa.",
        });
        previewForm.setError("document", "Pilih berkas dokumen DOCX atau PDF terlebih dahulu.");
        return;
      }

      previewForm.post(`/admin/question-titles/${props.questionTitle.id}/questions/import/preview`, {
        forceFormData: true,
        preserveScroll: true,
        onError: (errors) => {
          const message = errors.document || Object.values(errors)[0] || "Gagal memeriksa dokumen. Pastikan format berkas sesuai.";
          Swal.fire({
            icon: "error",
            title: "Pemeriksaan Gagal",
            text: message,
          });
        },
        onSuccess: (page) => {
          preview.value = page?.props?.preview || null;
          nextTick(() => {
            const previewEl = document.getElementById("preview-result-card");
            if (previewEl) {
              previewEl.scrollIntoView({ behavior: "smooth" });
            }
          });
        },
      });
    };

    const commitImport = async () => {
      if (!preview.value?.token) {
        Swal.fire({
          icon: "error",
          title: "Token Tidak Valid",
          text: "Pratinjau tidak memiliki token valid. Silakan periksa dokumen kembali.",
        });
        return;
      }

      const result = await Swal.fire({
        title: "Impor soal ke database?",
        text: `${preview.value.total} soal akan ditambahkan ke bank soal ini.`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Ya, impor",
        cancelButtonText: "Batal",
      });

      if (!result.isConfirmed) {
        return;
      }

      commitForm.token = preview.value.token;
      commitForm.post(`/admin/question-titles/${props.questionTitle.id}/questions/import`);
    };

    return {
      commitForm,
      commitImport,
      preview,
      previewForm,
      selectedFile,
      selectDocument,
      submitPreview,
    };
  },
};
</script>
