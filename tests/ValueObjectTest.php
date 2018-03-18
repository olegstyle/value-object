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
        $b = B_ValueObject::fromArray([
            'year' => 1994,
            'a' => [
                'name' => 'Tester',
            ]
        ]);

        $this->assertInstanceOf(A_ValueObject::class, $b->a);
        $this->assertEquals($b->a->name, 'Tester');
        $this->assertNull($b->a->surname);
    }
}

class A_ValueObject extends ValueObject
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

class B_ValueObject extends ValueObject
{
    /** @var int */
    public $year;

    /** @var A_ValueObject */
    public $a;

    public function __construct(int $year, A_ValueObject $a)
    {
        $this->year = $year;
        $this->a = $a;
    }
}
