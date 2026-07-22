<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class LinxenRootRedirectTest extends TestCase
{
    public function test_linxen_root_is_configured_to_redirect_to_commerce_v2(): void
    {
        $route = Route::getRoutes()->getByName('linxen.home');

        $this->assertSame('linxen.vn', $route->getDomain());
        $this->assertSame('/', $route->uri());
        $this->assertSame('/v2', $route->defaults['destination'] ?? null);
    }
}
