<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\Provider;

/**
 * Creates SDK model instances with private properties set via reflection.
 *
 * The new REST SDK uses JsonMapper for deserialization, so model classes
 * have no public constructors. This helper enables test data creation.
 */
class SdkModelFactory
{
    /**
     * Create an SDK model instance with the given property values.
     *
     * @template T of object
     * @param class-string<T> $className
     * @param array<string, mixed> $properties
     * @return T
     */
    public static function create(string $className, array $properties): object
    {
        $instance = new $className();
        $reflection = new \ReflectionClass($className);

        foreach ($properties as $name => $value) {
            $property = $reflection->getProperty($name);
            $property->setValue($instance, $value);
        }

        return $instance;
    }
}
