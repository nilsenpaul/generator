<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\generator\helpers;

use yii\base\InvalidArgumentException;

/**
 * Code helper
 */
abstract class Code
{
    /**
     * Splits a fully-qualified class name into its namespace and class name.
     *
     * @param string $class
     * @return array
     */
    public static function classParts(string $class): array
    {
        $parts = explode('\\', $class);
        $className = array_pop($parts);
        return [implode('\\', $parts) ?: null, $className];
    }

    /**
     * Returns the namespace from a fully-qualified class name, or `null` for root level classes.
     *
     * @param string $class
     * @return string|null
     */
    public static function namespace(string $class): ?string
    {
        return self::classParts($class)[0];
    }

    /**
     * Returns the class name (sans namespace) from a fully-qualified class name.
     *
     * @param string $class
     * @return string
     */
    public static function className(string $class): string
    {
        return self::classParts($class)[1];
    }

    /**
     * Normalizes a class/namespace by replacing forward slashes with backslashes, removing double backslashes,
     * and any leading/trailing backslashes.
     *
     * @param string $class
     * @return string
     * @throws InvalidArgumentException
     * @since 4.4.0
     */
    public static function normalizeClass(string $class): string
    {
        $class = trim(preg_replace('/\\\\+/', '\\', str_replace('/', '\\', $class)), '\\');

        if (!static::validateClass($class)) {
            throw new InvalidArgumentException("`$class` is an invalid class/namespace.");
        }

        return $class;
    }

    /**
     * Validates the class/namespace.
     *
     * @param string $class
     * @return bool
     * @since 4.4.0
     */
    public static function validateClass(string $class): bool
    {
        // Classes/namespaces must only consist of alphanumeric characters and underscores,
        // and cannot begin with a number
        return preg_match('/^[a-z_]\w*(\\\\[a-z_]\w*)*$/i', $class);
    }
}