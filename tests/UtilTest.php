<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UtilTest extends TestCase
{
    public function testEscEscapesHtml(): void
    {
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', esc('<script>alert(1)</script>'));
    }

    public function testEscAttrEscapesQuotes(): void
    {
        $this->assertSame('x&#039;y', esc_attr("x'y"));
    }

    public function testEscJsReturnsJsonString(): void
    {
        $this->assertSame('"hello"', esc_js('hello'));
    }
}
