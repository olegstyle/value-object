<?php

namespace Tests\Unit;

use OlegStyle\ValueObject\ValueObject;
use Tests\TestCase;

/**
 * Class ValueObjectTest
 * @package Tests\Unit
 *
 * @author Oleh Borysenko <olegstyle1@gmail.com>
 */
class ValueObjectTest extends TestCase
{
    public function testObjectInObject()
    {
        $b = B_ValueObjectTrait::fromArray([
            'year' => 1994,
            'a' => [
                'name' => 'Tester',
            ]
        ]);

        $this->assertInstanceOf(A_ValueObjectTrait::class, $b->a);
        $this->assertEquals($b->a->name, 'Tester');
        $this->assertNull($b->a->surname);
    }
}

class A_ValueObjectTrait extends ValueObject
{
    /** @var string */
    public $name;
    /** @var null|string */
    public $surname;

    public function __construct(string $name, ?string $surname)
    {
        $this->name = $name;
        $this->surname = $surname;
    }
}

class B_ValueObjectTrait extends ValueObject
{
    /** @var int */
    public $year;

    /** @var A_ValueObjectTrait */
    public $a;

    public function __construct(int $year, A_ValueObjectTrait $a)
    {
        $this->year = $year;
        $this->a = $a;
    }
}
