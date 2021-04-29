<?php

namespace Codememory\Components\Schema\Traits;

use Codememory\Components\Schema\Utils;
use Codememory\Support\Arr;

/**
 * Trait AttributesSchemaTrait
 * @package System\Schemes\Arr\Traits
 *
 * @author  Codememory
 */
trait AttributesSchemaTrait
{

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method will remove all reserved keys from the
     * processed array
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return array
     */
    private function cleanData(): array
    {

        foreach ($this->data as $key => $value) {
            if (preg_match(sprintf('/^%s/', Utils::CIRCUIT_KEY_SYMBOL), $key)) {
                unset($this->data[$key]);
            }
        }

        return $this->data;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * An attribute that takes a boolean value that means whether
     * to strictly check the array according to the scheme, i.e.
     * if true, then the extra keys in the array that are not in the
     * scheme will not be validated according to the scheme
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param mixed $value
     * @param mixed $currentData
     *
     * @return bool
     */
    private function attrArrayPrecision(mixed $value, mixed $currentData): bool
    {

        $data = Arr::dot($this->cleanData());

        if (true === $value) {
            foreach ($data as $key => $item) {
                if (false === array_key_exists($key, $this->getSchema())) {
                    return false;
                }
            }
        }

        return true;

    }

}