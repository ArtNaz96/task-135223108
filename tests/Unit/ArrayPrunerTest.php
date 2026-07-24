<?php

namespace Tests\Unit;

use App\Support\ArrayPruner;
use Tests\TestCase;

class ArrayPrunerTest extends TestCase
{
    public function test_removes_null_and_empty_string_values(): void
    {
        $result = ArrayPruner::pruneEmpty(['a' => null, 'b' => '', 'c' => 'keep']);

        $this->assertSame(['c' => 'keep'], $result);
    }

    public function test_keeps_false_and_zero(): void
    {
        $result = ArrayPruner::pruneEmpty(['a' => false, 'b' => 0, 'c' => []]);

        $this->assertSame(['a' => false, 'b' => 0], $result);
    }

    public function test_removes_nested_array_that_becomes_empty(): void
    {
        $result = ArrayPruner::pruneEmpty(['nested' => ['x' => null, 'y' => null]]);

        $this->assertSame([], $result);
    }

    public function test_reindexes_list_after_removing_empty_items(): void
    {
        $result = ArrayPruner::pruneEmpty(['list' => [1, null, 'a', '']]);

        $this->assertSame(['list' => [1, 'a']], $result);
    }

    public function test_prunes_real_ai_extraction_shaped_payload(): void
    {
        $payload = [
            'order' => ['order_id' => null, 'phone_number' => null, 'email' => null, 'delivery_address' => null, 'delivery_date' => null],
            'pet' => ['pet_species' => null, 'pet_breed' => null, 'pet_age_group' => null, 'pet_health_tags' => []],
            'products' => [],
            'intent' => ['intent_category' => 'other', 'urgency' => false, 'problem_description' => 'Test comment for rate limiting'],
        ];

        $result = ArrayPruner::pruneEmpty($payload);

        $this->assertSame([
            'intent' => ['intent_category' => 'other', 'urgency' => false, 'problem_description' => 'Test comment for rate limiting'],
        ], $result);
    }
}
