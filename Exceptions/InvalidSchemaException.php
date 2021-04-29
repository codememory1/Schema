<?php

namespace Codememory\Components\Schema\Exceptions;

use ErrorException;
use JetBrains\PhpStorm\Pure;

/**
 * Class InvalidSchemaException
 * @package System\Schemes\Arr\Exceptions
 *
 * @author  Codememory
 */
class InvalidSchemaException extends ErrorException
{

    /**
     * InvalidSchemaException constructor.
     *
     * @param string $path
     */
    #[Pure] public function __construct(string $path)
    {

        parent::__construct(sprintf(
            'An incorrect scheme is found at the address %s',
            $path
        ));

    }

}