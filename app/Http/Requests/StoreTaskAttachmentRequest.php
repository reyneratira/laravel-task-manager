<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,docx,doc,xlsx,xls,zip',
                'max:10240', // 10MB in KB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Silakan pilih file yang akan diunggah.',
            'file.file' => 'Berkas yang diunggah tidak valid.',
            'file.mimes' => 'Format file harus berupa: pdf, jpg, jpeg, png, docx, doc, xlsx, xls, atau zip.',
            'file.max' => 'Ukuran file tidak boleh melebihi 10MB.',
        ];
    }

    /**
     * Helper static untuk sanitasi nama file client.
     */
    public static function sanitizeFilename(string $originalName): string
    {
        // Ambil nama file tanpa path
        $basename = basename($originalName);

        // Pisahkan nama dan ekstensi
        $info = pathinfo($basename);
        $filename = $info['filename'] ?? 'attachment';
        $extension = isset($info['extension']) ? '.' . strtolower($info['extension']) : '';

        // Clean HTML/script tags
        $filename = strip_tags($filename);

        // Hapus null byte dan path traversal characters
        $filename = str_replace(["\0", '../', '..\\', '/', '\\'], '', $filename);

        // Ganti karakter non-alphanumeric (selain _ - spasi) dengan underscore
        $filename = preg_replace('/[^\w\s\.-]/u', '_', $filename);

        // Trim spasi berlebih
        $filename = trim(preg_replace('/\s+/', ' ', $filename));

        // Jika nama file kosong setelah disanitasi
        if (empty($filename)) {
            $filename = 'attachment_' . time();
        }

        // Batasi panjang max 200 karakter
        $filename = substr($filename, 0, 200);

        return $filename . $extension;
    }
}
