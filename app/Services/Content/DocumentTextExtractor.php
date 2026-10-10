<?php

namespace App\Services\Content;

use App\Exceptions\QuestionImportException;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;
use ZipArchive;

class DocumentTextExtractor
{
    private const MAX_EXTRACTED_CHARACTERS = 500000;

    public function extract(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $text = match ($extension) {
            'docx' => $this->extractDocx($file->getRealPath()),
            'pdf' => $this->extractPdf($file->getRealPath()),
            default => throw new QuestionImportException('Format dokumen tidak didukung. Gunakan DOCX atau PDF.'),
        };

        $text = $this->normalize($text);

        if ($text === '') {
            throw new QuestionImportException(
                $extension === 'pdf'
                    ? 'PDF tidak memiliki teks yang dapat dibaca. PDF hasil scan/gambar perlu diubah menjadi PDF teks terlebih dahulu.'
                    : 'Dokumen Word tidak memiliki teks yang dapat dibaca.'
            );
        }

        if (mb_strlen($text) > self::MAX_EXTRACTED_CHARACTERS) {
            throw new QuestionImportException('Isi dokumen terlalu besar untuk diproses. Maksimal 500.000 karakter teks.');
        }

        return $text;
    }

    private function extractDocx(string $path): string
    {
        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            throw new QuestionImportException('Dokumen Word tidak valid atau rusak.');
        }

        $xml = $archive->getFromName('word/document.xml');
        $archive->close();

        if (! is_string($xml) || $xml === '') {
            throw new QuestionImportException('Dokumen Word tidak memiliki bagian dokumen yang dapat dibaca.');
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new QuestionImportException('Struktur dokumen Word tidak valid.');
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $paragraphs = [];

        foreach ($xpath->query('//w:body//w:p') ?: [] as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }

            $parts = [];
            foreach ($xpath->query('.//w:t|.//w:tab|.//w:br', $paragraph) ?: [] as $node) {
                $parts[] = match ($node->localName) {
                    'tab' => "\t",
                    'br' => "\n",
                    default => $node->textContent,
                };
            }

            $paragraphs[] = implode('', $parts);
        }

        return implode("\n", $paragraphs);
    }

    private function extractPdf(string $path): string
    {
        try {
            return (new PdfParser)->parseFile($path)->getText();
        } catch (Throwable $exception) {
            report($exception);

            throw new QuestionImportException('PDF tidak dapat dibaca. Pastikan berkas tidak rusak, terenkripsi, atau dilindungi kata sandi.');
        }
    }

    private function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], $text);
        $text = preg_replace('/[\t ]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/ *\n */u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
