<?php

namespace OlegStyle\ValueObject;

use OlegStyle\ValueObject\Exceptions\ValidationException;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Mixed_;
use phpDocumentor\Reflection\Types\Object_;
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
    private static $ENUM_CLASS = 'OlegStyle\Enum\Enum';

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
        $parameters = $constructor->getParameters();
        $args = [];
        $i = -1;
        foreach ($parameters as $parameter) {
            $i += 1;
            // when value is not specified by name then try to get value by increment
            if (!isset($data[$parameter->name]) && isset($data[$i])) {
                $data[$parameter->name] = $data[$i];
            }

            // when value is not specified then try to get default value
            if (!isset($data[$parameter->name])) {
                $args[$parameter->name] = static::getDefaultParameterValue($parameter);

                continue;
            }
            $futureParameterValue = $data[$parameter->name];

            // if used parameter instance of some class then try to create object for this class
            $parameterClass = $parameter->getClass();
            if ($parameterClass !== null) {
                $args[$parameter->name] = static::getParameterFromClass($parameterClass, $futureParameterValue);

                continue;
            }

            if ($parameter->isArray()) {
                $args[$parameter->name] = static::getParameterForArray($reflection, $parameter, $futureParameterValue);
                // array of value-objects
                continue;
            }

            $parameterType = (string) $parameter->getType();
            if (is_float($futureParameterValue) && $parameterType === 'string') {
                $futureParameterValue = static::convertFloatToString($futureParameterValue);
            }

            // in another way try to set parameter
            $args[$parameter->name] = $futureParameterValue;
        }
        /** @var static $instance */
        $instance = $reflection->newInstanceArgs($args);

        return $instance;
    }

    protected static function convertFloatToString(float $value): string
    {
        return rtrim(sprintf('%.20F', $value), '0');
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

    protected static function isInstanceOfClass(ReflectionClass $reflection, string $className): bool
    {
        do {
            $parentClass = $reflection->getParentClass();
            if ($parentClass->getName() === $className) {
                return true;
            }
        } while ($parentClass->getParentClass());

        return false;
    }

    protected static function isInstanceOfSelf(ReflectionClass $reflection): bool
    {
        return static::isInstanceOfClass($reflection, self::class);
    }

    protected static function isReflectionInstanceOfEnum(ReflectionClass $reflectionClass): bool
    {
        if (class_exists(self::$ENUM_CLASS)) {
            return static::isInstanceOfClass($reflectionClass, self::$ENUM_CLASS);
        }

        return false;
    }

    /**
     * @param mixed $object
     * @return bool
     */
    protected static function isObjectInstanceOfEnum($object): bool
    {
        if (class_exists(self::$ENUM_CLASS)) {
            return is_subclass_of($object, self::$ENUM_CLASS);
        }

        return false;
    }

    /**
     * @param ReflectionClass $parameterClass
     * @param mixed $data
     * @return ValueObject|mixed
     */
    protected static function getParameterFromClass(ReflectionClass $parameterClass, $data)
    {
        if (is_array($data) && static::isInstanceOfSelf($parameterClass)) {
            return ($parameterClass->getName())::fromArray($data);
        }

        if (static::isReflectionInstanceOfEnum($parameterClass)) {
            if (self::isObjectInstanceOfEnum($data)) {
                return $data;
            }
            $className = $parameterClass->getName();
            return new $className($data);
        }

        // in another way try to return data value
        return $data;
    }

    /**
     * @param ReflectionClass $reflection
     * @param ReflectionParameter $parameter
     * @param mixed $data
     * @return mixed
     */
    public static function getParameterForArray(ReflectionClass $reflection, ReflectionParameter $parameter, $data)
    {
        $property = $reflection->getProperty($parameter->getName());
        $doc = $property->getDocComment();
        if (!$doc || !preg_match('/@var (\S+)/i', $doc, $matches)) {
            return $data;
        }

        $typeResolver = new TypeResolver();
        /** @var Compound $types */
        $contextFactory = new ContextFactory();
        $types = $typeResolver->resolve($matches[1], $contextFactory->createFromReflector($reflection));
        foreach ($types as $type) {
            // if it is not a array then continue
            if (($type instanceof Array_) === false) {
                continue;
            }

            // if array can get mixed values - then continue
            /** @var Array_ $type */
            $valueType = $type->getValueType();
            if ($valueType instanceof Mixed_) {
                continue;
            }

            // if it is a object, then we should check it for value object
            if ($valueType instanceof Object_) {
                $class = (string) $valueType->getFqsen();
                if (static::isInstanceOfSelf(new ReflectionClass($class))) {
                    $result = [];
                    foreach ($data as $key => $item) {
                        $result[$key] = ($class)::fromArray($item);
                    }

                    return $result;
                }
            }
        }

        return $data;
    }
}
