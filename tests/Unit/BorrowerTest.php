<?php

namespace Tests\Unit;

use App\Models\Borrower;
use App\Enums\BorrowerType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_public_id_on_creation(): void
    {
        $borrower = Borrower::create([
            'name' => 'John Doe',
            'type' => BorrowerType::Student,
            'identifier' => 'STUDENT001',
        ]);

        $this->assertNotNull($borrower->public_id);
        $this->assertStringStartsWith('0', $borrower->public_id); // ULID usually starts with 0
    }

    public function test_it_casts_type_to_enum(): void
    {
        $borrower = Borrower::create([
            'name' => 'John Doe',
            'type' => 'student',
            'identifier' => 'STUDENT001',
        ]);

        $this->assertInstanceOf(BorrowerType::class, $borrower->type);
        $this->assertEquals(BorrowerType::Student, $borrower->type);
    }
}
