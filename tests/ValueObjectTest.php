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
            ],
            'listOfC' => [
                ['value1' => 1],
                [2, 'not default'],
            ],
            'floatConvert' => 0.00063535
        ]);

        $this->assertInstanceOf(A_ValueObjectTrait::class, $b->a);
        $this->assertEquals('Tester', $b->a->name);
        $this->assertNull($b->a->surname);

        $this->assertInstanceOf(A_ValueObjectTrait::class, $b->listOfA['a']);
        $this->assertEquals('1', $b->listOfA['a']->name);
        $this->assertNull($b->listOfA['a']->surname);

        $this->assertInstanceOf(A_ValueObjectTrait::class, $b->listOfA['b']);
        $this->assertEquals('2', $b->listOfA['b']->name);
        $this->assertEquals('two', $b->listOfA['b']->surname);

        $this->assertInstanceOf(A_ValueObjectTrait::class, $b->listOfA['c']);
        $this->assertEquals('3', $b->listOfA['c']->name);
        $this->assertEquals('three', $b->listOfA['c']->surname);

        $this->assertInstanceOf(C_ValueObjectTrait::class, $b->listOfC[0]);
        $this->assertEquals(1, $b->listOfC[0]->value1);
        $this->assertEquals(2, $b->listOfC[1]->value1);
        $this->assertEquals('Default', $b->listOfC[0]->value2);
        $this->assertEquals('not default', $b->listOfC[1]->value2);
        $this->assertEquals($b->floatConvert, '0.00063535');
    }
}

class C_ValueObjectTrait extends ValueObject
{
    /** @var int */
    public $value1;

    /** @var string */
    public $value2;

    public function __construct(int $value1, ?string $value2 = 'Default')
    {
        $this->value1 = $value1;
        $this->value2 = $value2;
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

    /** @var array|A_ValueObjectTrait[] */
    public $listOfA;

    /** @var array|C_ValueObjectTrait[] */
    public $listOfC;

    /** @var string */
    public $floatConvert;

    public function __construct(int $year, A_ValueObjectTrait $a, array $listOfA, array $listOfC, string $floatConvert)
    {
        $this->year = $year;
        $this->a = $a;
        $this->listOfA = $listOfA;
        $this->listOfC = $listOfC;
        $this->floatConvert = $floatConvert;
    }
}
