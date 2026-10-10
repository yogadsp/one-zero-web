<?php

namespace App\Http\Requests\Lesson;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class QuestionImportPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'file',
                'max:5120',
                'mimetypes:application/pdf,application/x-pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,application/x-zip,application/x-zip-compressed,application/octet-stream,application/msword',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('document');
            if ($file && ! in_array(strtolower($file->getClientOriginalExtension()), ['docx', 'pdf'], true)) {
                $validator->errors()->add('document', 'Ekstensi dokumen harus .docx atau .pdf.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'document.required' => 'Pilih berkas dokumen DOCX atau PDF terlebih dahulu.',
            'document.file' => 'Berkas yang diunggah tidak valid.',
            'document.max' => 'Ukuran berkas maksimal 5 MB.',
            'document.mimetypes' => 'Format berkas harus berupa dokumen Word (.docx) atau PDF (.pdf).',
        ];
    }

    public function attributes(): array
    {
        return [
            'document' => 'dokumen soal',
        ];
    }
}
