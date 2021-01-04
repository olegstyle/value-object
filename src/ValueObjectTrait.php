<?php

namespace OlegStyle\ValueObject;

use OlegStyle\ValueObject\Exceptions\ValidationException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * Trait ValueObject
 * @package OlegStyle\ValueObject
 *
 * @author Oleh Borysenko <olegstyle1@gmail.com>
 */
trait ValueObjectTrait
{
    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param array $data
     * @return static
     * @throws ValidationException
     */
    public static function fromArray(array $data): self
    {
        try {
            $reflection = new ReflectionClass(static::class);
        } catch (ReflectionException $ex) {
            throw new ValidationException('Incorrect class');
        }

        return static::getInstanceFromReflectionClass($data, $reflection);
    }

    /**
     * @param array $data
     * @param ReflectionClass $reflection
     * @return mixed
     * @throws ValidationException
     */
    public static function getInstanceFromReflectionClass(array $data, ReflectionClass $reflection)
    {
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return new static();
        }
        $parameters = $constructor->getParameters();
        $args = [];
        foreach ($parameters as $parameter) {
            // when value is not specified then try to get default value
            if (!isset($data[$parameter->name])) {
                $args[$parameter->name] = static::getDefaultParameterValue($parameter);

                continue;
            }
            // if used parameter instance of some class then try to create object for this class
            $parameterClass = $parameter->getType() && !$parameter->getType()->isBuiltin()
                ? new ReflectionClass($parameter->getType()->getName())
                : null;
            if ($parameterClass !== null) {
                $args[$parameter->name] = static::getParameterFromClass($parameterClass, $data[$parameter->name]);

                continue;
            }

            // in another way try to set parameter
            $args[$parameter->name] = $data[$parameter->name];
        }
        /** @var static $instance */
        $instance = $reflection->newInstanceArgs($args);

        return $instance;
    }

    /**
     * @param ReflectionParameter $parameter
     * @return mixed|null
     * @throws ValidationException
     */
    protected static function getDefaultParameterValue(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        } elseif ($parameter->allowsNull()) {
            return null;
        }

        throw new ValidationException("{$parameter->name} is not defined in array");
    }

    /**
     * @param ReflectionClass $parameterClass
     * @param mixed $data
     * @return mixed
     */
    protected static function getParameterFromClass(ReflectionClass $parameterClass, $data)
    {
        // get last parent class
        do {
            $lastParentClass = $parameterClass->getParentClass();
        } while ($lastParentClass->getParentClass());

        // if last parent class is a ValueObject then we can make instance by using function `fromArray`
        if ($lastParentClass->getName() === self::class && is_array($data)) {
            return ($parameterClass->getName())::fromArray($data);
        }

        // in another way try to return data value
        return $data;
    }
}
