<?php

namespace Tests\Unit;

use OlegStyle\ValueObject\ValueObject;
use PHPUnit\Framework\TestCase;

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
            ],
            'listOfA' => [
                'a' => ['name' => '1'],
                'b' => ['name' => '2', 'surname' => 'two'],
                'c' => ['name' => '3', 'surname' => 'three'],
            ]
        ]);

        $this->assertInstanceOf(A_ValueObjectTrait::class, $b->a);
        $this->assertEquals($b->a->name, 'Tester');
        $this->assertNull($b->a->surname);

        $this->assertInstanceOf(A_ValueObjectTrait::class, $b->listOfA['a']);
        $this->assertEquals($b->listOfA['a']->name, '1');
        $this->assertNull($b->listOfA['a']->surname);

        $this->assertInstanceOf(A_ValueObjectTrait::class, $b->listOfA['b']);
        $this->assertEquals($b->listOfA['b']->name, '2');
        $this->assertEquals($b->listOfA['b']->surname, 'two');

        $this->assertInstanceOf(A_ValueObjectTrait::class, $b->listOfA['c']);
        $this->assertEquals($b->listOfA['c']->name, '3');
        $this->assertEquals($b->listOfA['c']->surname, 'three');
    }
}

class A_ValueObjectTrait extends ValueObject
{
    ///** @var string */
    public $name;

    ///** @var null|string */
    public $surname;

    public function __construct(string $name, ?string $surname)
    {
        $this->name = $name;
        $this->surname = $surname;
    }
}

class B_ValueObjectTrait extends ValueObject
{
    ///** @var int */
    public $year;

    ///** @var A_ValueObjectTrait */
    public $a;

    /** @var array|A_ValueObjectTrait[] */
    public $listOfA;

    public function __construct(int $year, A_ValueObjectTrait $a, array $listOfA)
    {
        $this->year = $year;
        $this->a = $a;
        $this->listOfA = $listOfA;
    }
}
