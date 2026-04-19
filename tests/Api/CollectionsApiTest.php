<?php
use PHPUnit\Framework\TestCase;

final class CollectionsApiTest extends TestCase
{
    protected function setUp(): void
    {
        if (!getenv('RUN_API_TESTS')) {
            $this->markTestSkipped('RUN_API_TESTS not set');
        }
    }

    public function testCreateAndAddRequiresAuth(): void
    {
        // Calling without CSRF / auth should fail
        $opts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => http_build_query(['action' => 'create_and_add', 'title' => 'Test', 'project_file_id' => 1])]];
        $context = stream_context_create($opts);
        $resp = @file_get_contents('http://localhost/api/collections.php', false, $context);
        $this->assertNotFalse($resp);
        $json = json_decode($resp, true);
        $this->assertArrayHasKey('error', $json);
    }
}
