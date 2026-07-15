<?php

namespace Tests\Unit;

use App\Support\PublicMedia;
use Tests\TestCase;

class PublicMediaTest extends TestCase
{
    public function test_it_builds_a_storage_url_for_public_media(): void
    {
        $this->assertSame('/storage/stocks/example.png', PublicMedia::url('stocks/example.png'));
    }

    public function test_it_returns_null_for_empty_path(): void
    {
        $this->assertNull(PublicMedia::url(null));
    }
}
