<?php

namespace Codememory\Components\Schema\Traits;

use JetBrains\PhpStorm\Pure;

/**
 * Trait TypesSchemaTrait
 * @package System\Schemes\Arr
 *
 * @author  Codememory
 */
trait TypesSchemaTrait
{

    /**
     * @param string $type
     * @param mixed  $value
     * @param mixed  $key
     * @param mixed  $data
     *
     * @return bool
     */
    #[Pure] private function typeArray(string $type, mixed $value, mixed $key, mixed $data): bool
    {

        return is_array($data);

    }

    /**
     * @param string $type
     * @param mixed  $value
     * @param mixed  $key
     * @param mixed  $data
     *
     * @return bool
     */
    #[Pure] private function typeNotEmptyArray(string $type, mixed $value, mixed $key, mixed $data): bool
    {

        return $this->typeArray($type, $value, $key, $data) && [] !== $data;

    }

    /**
     * @param string $type
     * @param mixed  $value
     * @param mixed  $key
     * @param mixed  $data
     *
     * @return bool
     */
    #[Pure] private function typeString(string $type, mixed $value, mixed $key, mixed $data): bool
    {

        return is_string($data) || empty($data);

    }

    /**
     * @param string $type
     * @param mixed  $value
     * @param mixed  $key
     * @param mixed  $data
     *
     * @return bool
     */
    #[Pure] private function typeNumber(string $type, mixed $value, mixed $key, mixed $data): bool
    {

        return is_numeric($data);

    }

    /**
     * @param string $type
     * @param mixed  $value
     * @param mixed  $key
     * @param mixed  $data
     *
     * @return bool
     */
    #[Pure] private function typeObject(string $type, mixed $value, mixed $key, mixed $data): bool
    {

        if (empty($value)) {
            return is_object($data);
        }

        return is_object($data) && $data instanceof $value;

    }

    /**
     * @param string $type
     * @param mixed  $value
     * @param mixed  $key
     * @param mixed  $data
     *
     * @return bool
     */
    #[Pure] private function typeMultiArray(string $type, mixed $value, mixed $key, mixed $data): bool
    {

        $isArray = $this->typeArray($type, $value, $key, $data);

        if (false === $isArray) {
            return false;
        }

        foreach ($data as $item) {
            if (is_array($item)) {
                return true;
            }
        }

        return false;

    }

}