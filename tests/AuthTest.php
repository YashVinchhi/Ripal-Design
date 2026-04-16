<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    public function testCsrfTokenAndValidation(): void
    {
        $_SESSION = [];
        $token = csrf_token();

        $this->assertNotSame('', $token);
        $this->assertTrue(csrf_validate($token));
        $this->assertFalse(csrf_validate('invalid-token'));
    }
}
