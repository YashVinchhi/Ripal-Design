<?php
use PHPUnit\Framework\TestCase;

final class ProjectsApiTest extends TestCase
{
    protected function setUp(): void
    {
        if (!getenv('RUN_API_TESTS')) {
            $this->markTestSkipped('RUN_API_TESTS not set');
        }
    }

    public function testListPublishedProjectsReturnsArray(): void
    {
        $resp = file_get_contents('http://localhost/api/projects.php?limit=5');
        $this->assertNotFalse($resp, 'API did not respond');
        $json = json_decode($resp, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('projects', $json);
    }

    public function testGetMissingProjectReturns404(): void
    {
        $id = 99999999;
        $context = stream_context_create(['http' => ['ignore_errors' => true]]);
        $resp = @file_get_contents('http://localhost/api/projects.php?id=' . $id, false, $context);
        $this->assertNotFalse($resp);
        $json = json_decode($resp, true);
        $this->assertArrayHasKey('error', $json);
    }
}
