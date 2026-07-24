<?php

namespace Tests\Unit;

use App\Support\InputSanitizer;
use Tests\TestCase;

class InputSanitizerTest extends TestCase
{
    public function test_it_strips_html_tags(): void
    {
        $input = '<script>alert(1)</script>текст';
        $sanitized = InputSanitizer::sanitize($input);

        $this->assertEquals('alert(1)текст', $sanitized);
    }

    public function test_it_removes_control_characters_except_newline_tab_cr(): void
    {
        $input = "Hello\x00\x1FWorld\x7F\nLine2\tTabbed\rReturn";
        $sanitized = InputSanitizer::sanitize($input);

        $this->assertEquals("HelloWorld\nLine2 Tabbed\rReturn", $sanitized);
    }

    public function test_it_preserves_line_breaks_in_multiline_comment(): void
    {
        $input = "First line\nSecond line\r\nThird line";
        $sanitized = InputSanitizer::sanitize($input);

        $this->assertEquals("First line\nSecond line\r\nThird line", $sanitized);
    }

    public function test_it_collapses_only_horizontal_whitespace(): void
    {
        $input = "Multiple   spaces  and\t\ttabs\nNew   line";
        $sanitized = InputSanitizer::sanitize($input);

        $this->assertEquals("Multiple spaces and tabs\nNew line", $sanitized);
    }

    public function test_it_trims_leading_and_trailing_whitespace_per_line_and_overall(): void
    {
        $input = "  Leading and trailing   \n   Next line   ";
        $sanitized = InputSanitizer::sanitize($input);

        $this->assertEquals("Leading and trailing\nNext line", $sanitized);
    }
}
