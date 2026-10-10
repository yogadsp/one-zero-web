<template>
  <Head><title>Materi Belajar</title></Head>
  <div class="page-wrapper material-page">
    <div class="page-content">
      <header class="material-toolbar">
        <div>
          <span class="material-eyebrow">MATERI BELAJAR</span>
          <h1>Kelola kelas dan rekaman</h1>
          <p>Kelas online dan video rekaman dijual sebagai produk mandiri dengan harga masing-masing.</p>
        </div>
        <form class="material-search" role="search" @submit.prevent="searchMaterials">
          <i class="bx bx-search" aria-hidden="true"></i>
          <input v-model="search" aria-label="Cari materi" maxlength="120" placeholder="Cari judul atau program">
          <button type="submit">Cari</button>
        </form>
      </header>

      <div v-if="$page.props.session.success" class="alert alert-success mt-3">{{ $page.props.session.success }}</div>
      <div v-if="$page.props.session.error" class="alert alert-danger mt-3">{{ $page.props.session.error }}</div>

      <nav class="material-segment" aria-label="Jenis materi">
        <button type="button" :class="{ active: selectedType === 'live_class' }" @click="switchType('live_class')">
          <i class="bx bx-video-plus"></i><span>Kelas Online</span><strong>{{ liveClasses.total }}</strong>
        </button>
        <button type="button" :class="{ active: selectedType === 'video' }" @click="switchType('video')">
          <i class="bx bx-play-circle"></i><span>Video Rekaman</span><strong>{{ videos.total }}</strong>
        </button>
      </nav>

      <main class="material-workspace">
        <section class="material-panel material-form-panel" :aria-labelledby="`${selectedType}-form-title`">
          <div class="panel-heading">
            <div class="panel-icon"><i :class="selectedType === 'live_class' ? 'bx bx-video' : 'bx bx-movie-play'"></i></div>
            <div><span>{{ editingId ? 'PERBARUI MATERI' : 'MATERI BARU' }}</span><h2 :id="`${selectedType}-form-title`">{{ formTitle }}</h2></div>
          </div>

          <form @submit.prevent="submit" class="material-form">
            <div class="field-group">
              <label for="material-category">Program</label>
              <select id="material-category" v-model="form.category_id" :class="{ 'is-invalid': errors.category_id }" required>
                <option value="" disabled>Pilih program belajar</option>
                <option v-for="item in categories" :key="item.id" :value="item.id">{{ item.name }}</option>
              </select>
              <small v-if="errors.category_id" class="field-error">{{ errors.category_id }}</small>
            </div>

            <div class="field-group">
              <label for="material-price">Harga</label>
              <div class="input-with-prefix"><span>Rp</span><input id="material-price" v-model.number="form.price" type="number" min="0" max="999999999999" step="1" :class="{ 'is-invalid': errors.price }" required></div>
              <small class="field-hint">Gunakan 0 jika produk gratis; voucher tetap perlu diaktifkan.</small>
              <small v-if="errors.price" class="field-error">{{ errors.price }}</small>
            </div>

            <div class="field-group">
              <label for="material-title">{{ selectedType === 'live_class' ? 'Nama kelas' : 'Nama video' }}</label>
              <input id="material-title" v-model="form.title" maxlength="255" :placeholder="selectedType === 'live_class' ? 'Contoh: Pembahasan TWK Intensif' : 'Contoh: Rekaman Pembahasan TPS'" :class="{ 'is-invalid': errors.title }" required>
              <small v-if="errors.title" class="field-error">{{ errors.title }}</small>
            </div>

            <div class="field-group">
              <label for="material-url">{{ selectedType === 'live_class' ? 'Tautan ruang kelas' : 'Tautan video' }}</label>
              <div class="input-with-icon"><i class="bx bx-link-alt"></i><input id="material-url" v-model="form.url" type="url" maxlength="2048" placeholder="https://..." :class="{ 'is-invalid': errors.url }" required></div>
              <small class="field-hint">Tautan hanya dibuka setelah transaksi produk selesai dan voucher aktif.</small>
              <small v-if="errors.url" class="field-error">{{ errors.url }}</small>
            </div>

            <div v-if="selectedType === 'live_class'" class="date-grid">
              <div class="field-group"><label for="material-start">Mulai</label><input id="material-start" v-model="form.starts_at" type="datetime-local" :class="{ 'is-invalid': errors.starts_at }" required><small v-if="errors.starts_at" class="field-error">{{ errors.starts_at }}</small></div>
              <div class="field-group"><label for="material-end">Selesai</label><input id="material-end" v-model="form.ends_at" type="datetime-local" :class="{ 'is-invalid': errors.ends_at }"><small v-if="errors.ends_at" class="field-error">{{ errors.ends_at }}</small></div>
            </div>

            <div class="field-group">
              <label for="material-description">Deskripsi</label>
              <textarea id="material-description" v-model="form.description" rows="5" maxlength="5000" placeholder="Tuliskan ringkasan materi dan informasi penting untuk siswa."></textarea>
              <small class="field-hint">{{ (form.description || '').length }}/5000 karakter</small>
            </div>

            <div class="form-meta-grid">
              <div class="field-group"><label for="material-position">Nomor urut</label><input id="material-position" v-model.number="form.position" type="number" min="0" max="65535"></div>
              <label class="publish-control" for="material-published"><span><strong>Tampilkan ke siswa</strong><small>{{ form.is_published ? 'Materi akan terlihat' : 'Simpan sebagai draft' }}</small></span><input id="material-published" v-model="form.is_published" type="checkbox" role="switch"></label>
            </div>

            <div class="form-actions">
              <button class="primary-action" type="submit" :disabled="form.processing"><i :class="editingId ? 'bx bx-save' : 'bx bx-plus'"></i>{{ editingId ? 'Simpan perubahan' : `Tambah ${selectedType === 'live_class' ? 'kelas' : 'video'}` }}</button>
              <button v-if="editingId" class="secondary-action" type="button" @click="resetForm">Batal edit</button>
            </div>
          </form>
        </section>

        <section class="material-panel material-list-panel" :aria-labelledby="`${selectedType}-list-title`">
          <div class="list-heading"><div><span>{{ selectedType === 'live_class' ? 'JADWAL PEMBELAJARAN' : 'PUSTAKA VIDEO' }}</span><h2 :id="`${selectedType}-list-title`">{{ listTitle }}</h2></div><div class="list-count">{{ activeCollection.total }} item</div></div>
          <div class="table-responsive">
            <table class="material-table">
              <thead><tr><th class="number-column">No</th><th>Materi</th><th>Harga</th><th>{{ selectedType === 'live_class' ? 'Jadwal' : 'Akses' }}</th><th>Status</th><th class="action-column">Aksi</th></tr></thead>
              <tbody>
                <tr v-for="(material, index) in activeCollection.data" :key="material.id">
                  <td><span class="row-number">{{ rowNumber(index) }}</span></td>
                  <td><strong>{{ material.title }}</strong><small>{{ material.category.name }}</small></td>
                  <td><span v-if="material.price !== null" class="price-value">{{ formatPrice(material.price) }}</span><span v-else class="price-missing">Atur harga</span></td>
                  <td><span v-if="selectedType === 'live_class'" class="schedule-value">{{ formatDate(material.starts_at) }}</span><span v-else class="link-value"><i class="bx bx-link-external"></i> Tautan terlindungi</span></td>
                  <td><span class="status-pill" :class="material.is_published ? 'published' : 'draft'">{{ material.is_published ? 'Tayang' : 'Draft' }}</span></td>
                  <td><div class="row-actions"><button type="button" aria-label="Edit materi" @click="edit(material)"><i class="bx bx-edit-alt"></i></button><button type="button" class="danger" aria-label="Hapus materi" @click="destroy(material.id)"><i class="bx bx-trash"></i></button></div></td>
                </tr>
                <tr v-if="!activeCollection.data.length"><td colspan="6"><div class="empty-state"><i :class="selectedType === 'live_class' ? 'bx bx-calendar-x' : 'bx bx-video-off'"></i><strong>Belum ada {{ selectedType === 'live_class' ? 'kelas online' : 'video rekaman' }}</strong><span>Gunakan form di sebelah kiri untuk menambahkan materi pertama.</span></div></td></tr>
              </tbody>
            </table>
          </div>
          <Pagination :links="activeCollection.links" align="end" />
        </section>
      </main>
    </div>
  </div>
</template>

<script>
import { Head, useForm } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import Swal from 'sweetalert2';
import LayoutAdmin from '../../../../Layouts/Layout.vue';
import Pagination from '../../../../Components/Pagination.vue';

const emptyForm = type => ({ category_id: '', type, title: '', price: null, description: '', url: '', starts_at: '', ends_at: '', is_published: false, position: 0 });

export default {
  layout: LayoutAdmin,
  components: { Head, Pagination },
  props: { liveClasses: Object, videos: Object, categories: Array, filters: Object, activeType: String, errors: Object },
  data() {
    const initialType = this.activeType === 'video' ? 'video' : 'live_class';
    return { selectedType: initialType, editingId: null, search: this.filters?.search || '', form: useForm(emptyForm(initialType)) };
  },
  computed: {
    activeCollection() { return this.selectedType === 'live_class' ? this.liveClasses : this.videos; },
    formTitle() {
      if (this.editingId) return this.selectedType === 'live_class' ? 'Edit kelas online' : 'Edit video rekaman';
      return this.selectedType === 'live_class' ? 'Tambah kelas online' : 'Tambah video rekaman';
    },
    listTitle() { return this.selectedType === 'live_class' ? 'Daftar kelas online' : 'Daftar video rekaman'; },
  },
  methods: {
    switchType(type) { if (type !== this.selectedType) { this.selectedType = type; this.resetForm(); } },
    submit() {
      this.form.type = this.selectedType;
      if (this.selectedType === 'video') { this.form.starts_at = ''; this.form.ends_at = ''; }
      if (this.editingId) { this.form.put(`/admin/learning-materials/${this.editingId}`, { onSuccess: () => this.resetForm() }); return; }
      this.form.post('/admin/learning-materials', { onSuccess: () => this.resetForm() });
    },
    edit(material) {
      this.selectedType = material.type;
      this.editingId = material.id;
      Object.assign(this.form, { category_id: material.category_id, type: material.type, title: material.title, price: material.price, description: material.description || '', url: material.url, starts_at: this.toInputDate(material.starts_at), ends_at: this.toInputDate(material.ends_at), is_published: Boolean(material.is_published), position: material.position || 0 });
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    resetForm() { this.editingId = null; this.form.clearErrors(); this.form.reset(); Object.assign(this.form, emptyForm(this.selectedType)); },
    searchMaterials() { Inertia.get('/admin/learning-materials', { search: this.search, type: this.selectedType }); },
    destroy(id) {
      Swal.fire({ title: 'Hapus materi?', text: 'Materi tidak lagi tampil untuk siswa.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal' }).then(result => { if (result.isConfirmed) Inertia.delete(`/admin/learning-materials/${id}`); });
    },
    rowNumber(index) { return (this.activeCollection.from || 1) + index; },
    toInputDate(value) { return value ? String(value).slice(0, 16) : ''; },
    formatDate(value) { return value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'Belum dijadwalkan'; },
    formatPrice(value) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0)); },
  },
};
</script>

<style scoped>
.material-page { --accent: #2563eb; --ink: #172033; --muted: #667085; --line: rgba(22, 32, 51, .1); background: radial-gradient(circle at 78% 0%, rgba(91, 124, 250, .12), transparent 34%), #f4f6fb; min-height: 100vh; }
.material-toolbar { display:flex; align-items:center; justify-content:space-between; gap:24px; padding:26px 28px; border:1px solid rgba(255,255,255,.72); border-radius:26px; background:rgba(255,255,255,.78); box-shadow:0 18px 50px rgba(42,55,90,.1), inset 0 1px 0 rgba(255,255,255,.95); }
@supports (backdrop-filter:blur(18px)) { .material-toolbar,.material-segment { backdrop-filter:blur(18px) saturate(135%); -webkit-backdrop-filter:blur(18px) saturate(135%); } }
.material-eyebrow,.panel-heading span,.list-heading span { color:var(--accent); font-size:.72rem; font-weight:750; letter-spacing:.12em; }
.material-toolbar h1 { margin:5px 0 4px; color:var(--ink); font-size:clamp(1.45rem,2.2vw,2rem); font-weight:720; letter-spacing:-.035em; }
.material-toolbar p { margin:0; color:var(--muted); }
.material-search { display:flex; align-items:center; min-width:min(390px,100%); padding:6px; border:1px solid var(--line); border-radius:16px; background:rgba(255,255,255,.9); }
.material-search i { margin-left:10px; color:var(--muted); font-size:1.2rem; }.material-search input { flex:1; min-width:0; border:0; padding:9px 10px; outline:none; background:transparent; }.material-search button { border:0; border-radius:11px; padding:9px 16px; color:#fff; background:var(--accent); font-weight:650; }
.material-segment { display:inline-flex; gap:6px; margin:18px 0; padding:6px; border:1px solid rgba(255,255,255,.75); border-radius:18px; background:rgba(255,255,255,.72); box-shadow:0 10px 30px rgba(42,55,90,.08); }
.material-segment button { display:flex; align-items:center; gap:8px; min-height:44px; border:0; border-radius:13px; padding:9px 14px; color:var(--muted); background:transparent; font-weight:650; transition:transform 160ms ease,background 160ms ease,color 160ms ease; }.material-segment button:hover { transform:translateY(-1px); color:var(--ink); }.material-segment button.active { color:#fff; background:var(--accent); box-shadow:0 8px 20px rgba(37,99,235,.24); }.material-segment strong { min-width:24px; padding:2px 7px; border-radius:999px; background:rgba(127,127,127,.14); font-size:.75rem; text-align:center; }
.material-workspace { display:grid; grid-template-columns:minmax(320px,.82fr) minmax(520px,1.45fr); gap:20px; align-items:start; }.material-panel { border:1px solid var(--line); border-radius:22px; background:#fff; box-shadow:0 16px 44px rgba(42,55,90,.08); }.material-form-panel { padding:24px; }.material-list-panel { overflow:hidden; }.panel-heading,.list-heading { display:flex; align-items:center; gap:13px; }.panel-heading { margin-bottom:24px; }.panel-heading h2,.list-heading h2 { margin:2px 0 0; color:var(--ink); font-size:1.2rem; font-weight:700; letter-spacing:-.02em; }.panel-icon { display:grid; place-items:center; width:46px; height:46px; border-radius:14px; color:var(--accent); background:#eef4ff; font-size:1.35rem; }
.material-form { display:grid; gap:17px; }.field-group { display:grid; gap:7px; }.field-group label { color:#344054; font-size:.86rem; font-weight:650; }.field-group input,.field-group select,.field-group textarea { width:100%; border:1px solid #d9dee8; border-radius:13px; padding:11px 13px; color:var(--ink); background:#fff; outline:none; transition:border 150ms ease,box-shadow 150ms ease; }.field-group textarea { resize:vertical; min-height:116px; }.field-group input:focus,.field-group select:focus,.field-group textarea:focus { border-color:#7da5ff; box-shadow:0 0 0 4px rgba(37,99,235,.11); }.field-group .is-invalid { border-color:#dc3545; }.field-hint { color:var(--muted); }.field-error { color:#c42b3b; }.input-with-icon { position:relative; }.input-with-icon i { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--muted); }.input-with-icon input { padding-left:38px; }.date-grid,.form-meta-grid { display:grid; grid-template-columns:1fr 1fr; gap:13px; }
.input-with-prefix { display:flex; align-items:center; overflow:hidden; border:1px solid #d9dee8; border-radius:13px; background:#fff; }.input-with-prefix:focus-within { border-color:#7da5ff; box-shadow:0 0 0 4px rgba(37,99,235,.11); }.input-with-prefix span { padding:11px 0 11px 13px; color:var(--muted); font-weight:700; }.input-with-prefix input { border:0; box-shadow:none !important; }.price-value { color:var(--ink); font-size:.82rem; font-weight:680; }.price-missing { display:inline-flex; border-radius:999px; padding:5px 9px; color:#92400e; background:#fffbeb; font-size:.73rem; font-weight:700; }
.publish-control { display:flex; align-items:center; justify-content:space-between; gap:16px; align-self:end; min-height:66px; padding:10px 13px; border:1px solid var(--line); border-radius:14px; }.publish-control span { display:grid; }.publish-control strong { color:#344054; font-size:.86rem; }.publish-control small { color:var(--muted); }.publish-control input { width:42px; height:23px; accent-color:var(--accent); }.form-actions { display:flex; gap:10px; padding-top:4px; }.primary-action,.secondary-action { min-height:44px; border-radius:13px; padding:10px 17px; font-weight:680; }.primary-action { flex:1; border:0; color:#fff; background:var(--accent); box-shadow:0 9px 20px rgba(37,99,235,.2); }.secondary-action { border:1px solid var(--line); color:#344054; background:#f8fafc; }
.list-heading { justify-content:space-between; padding:24px 24px 18px; border-bottom:1px solid var(--line); }.list-count { border-radius:999px; padding:6px 10px; color:var(--muted); background:#f2f4f7; font-size:.78rem; font-weight:650; }.material-table { width:100%; border-collapse:collapse; }.material-table th { padding:13px 15px; color:var(--muted); background:#f8f9fc; font-size:.74rem; font-weight:720; letter-spacing:.04em; text-transform:uppercase; }.material-table td { padding:16px 15px; border-top:1px solid var(--line); color:#344054; vertical-align:middle; }.material-table td strong,.material-table td small { display:block; }.material-table td strong { color:var(--ink); font-weight:680; }.material-table td small { margin-top:3px; color:var(--muted); }.number-column { width:64px; text-align:center; }.action-column { width:104px; }.row-number { display:inline-grid; place-items:center; width:30px; height:30px; border-radius:9px; color:#38527d; background:#eef3fb; font-weight:700; }.schedule-value,.link-value { font-size:.82rem; }.link-value { color:#4c6fae; font-weight:620; }.status-pill { display:inline-flex; border-radius:999px; padding:5px 9px; font-size:.73rem; font-weight:700; }.status-pill.published { color:#067647; background:#ecfdf3; }.status-pill.draft { color:#475467; background:#f2f4f7; }.row-actions { display:flex; gap:7px; }.row-actions button { display:grid; place-items:center; width:36px; height:36px; border:1px solid var(--line); border-radius:11px; color:#3c4a61; background:#fff; }.row-actions button.danger { color:#c52a3b; }.empty-state { display:grid; justify-items:center; gap:6px; padding:54px 18px; color:var(--muted); text-align:center; }.empty-state i { color:#9aabc3; font-size:2.2rem; }.empty-state strong { color:#344054; }
@media (max-width:1199px) { .material-workspace { grid-template-columns:1fr; } }
@media (max-width:767px) { .page-content { padding-inline:14px; }.material-toolbar { align-items:stretch; flex-direction:column; padding:20px; border-radius:20px; }.material-search { min-width:0; }.material-segment { display:grid; grid-template-columns:1fr 1fr; width:100%; }.material-segment button { justify-content:center; padding-inline:8px; }.date-grid,.form-meta-grid { grid-template-columns:1fr; }.material-form-panel { padding:19px; } }
@media (prefers-reduced-motion:reduce) { .material-page *,.material-page *::before,.material-page *::after { scroll-behavior:auto !important; transition:none !important; } }
@media (prefers-contrast:more) { .material-toolbar,.material-segment,.material-panel { border-color:#667085; box-shadow:none; } }
@media (prefers-reduced-transparency:reduce) { .material-toolbar,.material-segment { background:#fff; backdrop-filter:none; -webkit-backdrop-filter:none; } }
</style>
