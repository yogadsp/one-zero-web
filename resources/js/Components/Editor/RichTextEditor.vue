<template>
  <div class="rich-text-editor" :class="{ 'is-invalid': invalid, 'is-fullscreen': isFullscreen }">
    <div v-if="editor" class="editor-toolbar" role="toolbar" aria-label="Pemformatan deskripsi ujian">
      <button type="button" title="Urungkan" aria-label="Urungkan" :disabled="!editor.can().chain().focus().undo().run()" @click="editor.chain().focus().undo().run()"><i class="bx bx-undo"></i></button>
      <button type="button" title="Ulangi" aria-label="Ulangi" :disabled="!editor.can().chain().focus().redo().run()" @click="editor.chain().focus().redo().run()"><i class="bx bx-redo"></i></button>
      <span class="toolbar-divider" aria-hidden="true"></span>
      <button type="button" :class="{ active: editor.isActive('paragraph') }" @click="editor.chain().focus().setParagraph().run()">Paragraf</button>
      <button type="button" title="Judul" aria-label="Judul" :class="{ active: editor.isActive('heading', { level: 2 }) }" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">H2</button>
      <span class="toolbar-divider" aria-hidden="true"></span>
      <button type="button" title="Tebal" aria-label="Tebal" :class="{ active: editor.isActive('bold') }" @click="editor.chain().focus().toggleBold().run()"><i class="bx bx-bold"></i></button>
      <button type="button" title="Miring" aria-label="Miring" :class="{ active: editor.isActive('italic') }" @click="editor.chain().focus().toggleItalic().run()"><i class="bx bx-italic"></i></button>
      <button type="button" title="Garis bawah" aria-label="Garis bawah" :class="{ active: editor.isActive('underline') }" @click="editor.chain().focus().toggleUnderline().run()"><i class="bx bx-underline"></i></button>
      <span class="toolbar-divider" aria-hidden="true"></span>
      <button type="button" title="Daftar poin" aria-label="Daftar poin" :class="{ active: editor.isActive('bulletList') }" @click="editor.chain().focus().toggleBulletList().run()"><i class="bx bx-list-ul"></i></button>
      <button type="button" title="Daftar nomor" aria-label="Daftar nomor" :class="{ active: editor.isActive('orderedList') }" @click="editor.chain().focus().toggleOrderedList().run()"><i class="bx bx-list-ol"></i></button>
      <span class="toolbar-divider" aria-hidden="true"></span>
      <button type="button" title="Rata kiri" aria-label="Rata kiri" :class="{ active: editor.isActive({ textAlign: 'left' }) }" @click="editor.chain().focus().setTextAlign('left').run()"><i class="bx bx-align-left"></i></button>
      <button type="button" title="Rata tengah" aria-label="Rata tengah" :class="{ active: editor.isActive({ textAlign: 'center' }) }" @click="editor.chain().focus().setTextAlign('center').run()"><i class="bx bx-align-middle"></i></button>
      <button type="button" title="Rata kanan" aria-label="Rata kanan" :class="{ active: editor.isActive({ textAlign: 'right' }) }" @click="editor.chain().focus().setTextAlign('right').run()"><i class="bx bx-align-right"></i></button>
      <span class="toolbar-divider" aria-hidden="true"></span>
      <button type="button" title="Tautan" aria-label="Tambahkan tautan" :class="{ active: editor.isActive('link') }" @click="setLink"><i class="bx bx-link"></i></button>
      <button type="button" title="Unggah gambar" aria-label="Unggah gambar" :disabled="uploading" @click="$refs.imageInput.click()"><i class="bx bx-image-add"></i></button>
      <input ref="imageInput" class="visually-hidden" type="file" accept="image/jpeg,image/png,image/webp,image/gif" @change="uploadImage">
      <button type="button" title="Sisipkan tabel" aria-label="Sisipkan tabel" @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()"><i class="bx bx-table"></i></button>
      <button v-if="editor.isActive('table')" type="button" title="Hapus tabel" aria-label="Hapus tabel" @click="editor.chain().focus().deleteTable().run()"><i class="bx bx-grid-alt"></i></button>
      <span class="toolbar-spacer"></span>
      <button type="button" title="Layar penuh" aria-label="Layar penuh" @click="isFullscreen = !isFullscreen"><i :class="isFullscreen ? 'bx bx-exit-fullscreen' : 'bx bx-fullscreen'"></i></button>
    </div>

    <EditorContent :editor="editor" class="editor-content" :style="{ '--editor-min-height': `${height}px` }" />
    <div class="editor-status" aria-live="polite">
      <span v-if="uploading"><i class="bx bx-loader-alt bx-spin"></i> Mengunggah gambar…</span>
      <span v-else-if="uploadError" class="editor-error">{{ uploadError }}</span>
      <span v-else>Tiptap • tersimpan sebagai HTML</span>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import { TableKit } from '@tiptap/extension-table';
import TextAlign from '@tiptap/extension-text-align';

export default {
  name: 'RichTextEditor',
  components: { EditorContent },
  props: {
    modelValue: { type: String, default: '' },
    invalid: { type: Boolean, default: false },
    height: { type: Number, default: 320 },
  },
  emits: ['update:modelValue'],
  data() {
    return { editor: null, uploading: false, uploadError: '', isFullscreen: false };
  },
  watch: {
    modelValue(value) {
      if (!this.editor || this.editor.getHTML() === (value || '')) return;
      this.editor.commands.setContent(value || '', { emitUpdate: false });
    },
  },
  mounted() {
    this.editor = new Editor({
      content: this.modelValue || '',
      extensions: [
        StarterKit.configure({
          heading: { levels: [2, 3] },
          link: { openOnClick: false, HTMLAttributes: { rel: 'noopener noreferrer' } },
        }),
        Image.configure({ allowBase64: false, HTMLAttributes: { class: 'editor-image' } }),
        TableKit,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
      ],
      editorProps: {
        attributes: {
          class: 'tiptap-document',
          role: 'textbox',
          'aria-label': 'Deskripsi ujian',
          'aria-multiline': 'true',
        },
      },
      onUpdate: ({ editor }) => this.$emit('update:modelValue', editor.getHTML()),
    });
  },
  beforeUnmount() {
    this.editor?.destroy();
  },
  methods: {
    setLink() {
      this.uploadError = '';
      const current = this.editor.getAttributes('link').href || '';
      const href = window.prompt('Masukkan tautan HTTPS atau email:', current);

      if (href === null) return;
      if (href.trim() === '') {
        this.editor.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
      }
      if (!/^(https?:\/\/|mailto:)/i.test(href.trim())) {
        this.uploadError = 'Tautan harus diawali https://, http://, atau mailto:.';
        return;
      }

      this.editor.chain().focus().extendMarkRange('link').setLink({ href: href.trim() }).run();
    },
    async uploadImage(event) {
      const [file] = event.target.files || [];
      event.target.value = '';
      this.uploadError = '';
      if (!file) return;

      const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
      if (!allowed.includes(file.type) || file.size > 5 * 1024 * 1024) {
        this.uploadError = 'Gunakan JPG, PNG, WebP, atau GIF dengan ukuran maksimal 5 MB.';
        return;
      }

      this.uploading = true;
      try {
        const payload = new FormData();
        payload.append('file', file, file.name);
        const response = await axios.post('/upload', payload, { headers: { 'Content-Type': 'multipart/form-data' } });
        this.editor.chain().focus().setImage({ src: response.data.location, alt: file.name }).run();
      } catch (error) {
        this.uploadError = error.response?.data?.message || 'Gambar tidak dapat diunggah.';
      } finally {
        this.uploading = false;
      }
    },
  },
};
</script>

<style scoped>
.rich-text-editor { border:1px solid #d9dee8; border-radius:14px; overflow:hidden; background:#fff; box-shadow:0 1px 2px rgba(16,24,40,.04); }
.rich-text-editor.is-invalid { border-color:#dc3545; box-shadow:0 0 0 3px rgba(220,53,69,.12); }
.editor-toolbar { display:flex; align-items:center; flex-wrap:wrap; gap:4px; padding:8px; border-bottom:1px solid #e8ebf1; background:#f8fafc; }
.editor-toolbar button { display:inline-grid; place-items:center; min-width:34px; min-height:34px; border:0; border-radius:8px; padding:6px 9px; color:#3f4b5f; background:transparent; font-size:.82rem; font-weight:650; }
.editor-toolbar button:hover:not(:disabled),.editor-toolbar button.active { color:#2459cc; background:#e9f0ff; }.editor-toolbar button:focus-visible { outline:2px solid #5286f7; outline-offset:1px; }.editor-toolbar button:disabled { opacity:.4; }.editor-toolbar i { font-size:1.15rem; }
.toolbar-divider { width:1px; height:24px; margin:0 3px; background:#d9dee8; }.toolbar-spacer { flex:1; }
.editor-content :deep(.tiptap-document) { min-height:var(--editor-min-height); margin:0; padding:18px; color:#172033; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; font-size:15px; line-height:1.65; outline:none; }
.editor-content :deep(.tiptap-document p:first-child) { margin-top:0; }.editor-content :deep(.tiptap-document p:last-child) { margin-bottom:0; }.editor-content :deep(ol),.editor-content :deep(ul) { padding-left:1.5rem; }.editor-content :deep(img) { max-width:100%; height:auto; border-radius:10px; }
.editor-content :deep(table) { width:100%; border-collapse:collapse; table-layout:fixed; }.editor-content :deep(th),.editor-content :deep(td) { min-width:1em; border:1px solid #d0d5dd; padding:7px 9px; vertical-align:top; }.editor-content :deep(th) { background:#f2f4f7; font-weight:700; }.editor-content :deep(.selectedCell::after) { content:""; position:absolute; inset:0; pointer-events:none; background:rgba(37,99,235,.12); }
.editor-status { min-height:31px; padding:7px 12px; border-top:1px solid #eef0f4; color:#7a8495; background:#fbfcfe; font-size:.75rem; text-align:right; }.editor-error { color:#b42318; }
.rich-text-editor.is-fullscreen { position:fixed; inset:12px; z-index:1090; display:flex; flex-direction:column; border-radius:18px; box-shadow:0 30px 90px rgba(16,24,40,.3); }.is-fullscreen .editor-content { flex:1; overflow:auto; }.is-fullscreen .editor-content :deep(.tiptap-document) { min-height:calc(100vh - 132px); }
@media (max-width:767px) { .toolbar-divider { display:none; }.editor-toolbar { gap:2px; }.editor-toolbar button { min-width:32px; padding-inline:7px; } }
@media (prefers-reduced-motion:reduce) { .rich-text-editor * { scroll-behavior:auto !important; transition:none !important; } }
</style>
