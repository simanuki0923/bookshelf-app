<?php

namespace Tests\Unit\Models;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class GenreTest extends TestCase
{
    public function test_books_relation_is_belongs_to_many(): void
    {
        $this->assertInstanceOf(
            BelongsToMany::class,
            (new Genre)->books()
        );
    }
}
