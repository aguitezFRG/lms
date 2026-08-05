<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class DatabaseOutageResponseTest extends TestCase
{
    public function test_database_connection_failure_renders_the_dedicated_503_page(): void
    {
        $this->withoutMiddleware();

        Route::get('/_test/database-outage', function (): never {
            throw new PDOException('SQLSTATE[08006] [7] connection refused');
        });

        $this->get('/_test/database-outage')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertSee('The library service is temporarily unavailable.')
            ->assertSee('Try again');
    }

    public function test_database_connection_failure_returns_json_for_json_requests(): void
    {
        $this->withoutMiddleware();

        Route::get('/_test/database-outage-json', function (): never {
            throw new PDOException('SQLSTATE[08006] [7] could not connect to server');
        });

        $this->getJson('/_test/database-outage-json')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertExactJson([
                'message' => 'The database service is temporarily unavailable. Please try again shortly.',
            ]);
    }

    public function test_unrelated_exceptions_are_not_reclassified_as_database_outages(): void
    {
        $this->withoutMiddleware();

        Route::get('/_test/unrelated-error', function (): never {
            throw new RuntimeException('Unrelated application failure');
        });

        $this->get('/_test/unrelated-error')
            ->assertStatus(500);
    }
}
