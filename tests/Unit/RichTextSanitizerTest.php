<?php

namespace Tests\Unit;

use App\Services\Content\RichTextSanitizer;
use PHPUnit\Framework\TestCase;

class RichTextSanitizerTest extends TestCase
{
    public function test_it_preserves_supported_editor_html_and_removes_executable_content(): void
    {
        $html = <<<'HTML'
<script>alert('x')</script>
<p style="text-align: center" onclick="steal()"><strong>Petunjuk</strong></p>
<ol><li><em>Langkah pertama</em></li></ol>
<a href="javascript:alert(1)" target="_blank">tautan buruk</a>
<img src="/storage/upload_files/questions/image.png" onerror="steal()">
HTML;

        $result = (new RichTextSanitizer())->sanitize($html);

        $this->assertStringNotContainsString('script', $result);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('onerror', $result);
        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringContainsString('style="text-align: center"', $result);
        $this->assertStringContainsString('<ol><li><em>Langkah pertama</em></li></ol>', $result);
        $this->assertStringContainsString('src="/storage/upload_files/questions/image.png"', $result);
    }
}
