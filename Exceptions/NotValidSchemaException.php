<?php

namespace Codememory\Components\Schema\Exceptions;

use Codememory\Components\JsonParser\Exceptions\JsonErrorException;
use Codememory\Components\JsonParser\JsonParser;
use ErrorException;
use JetBrains\PhpStorm\Pure;

/**
 * Class NotValidSchemaException
 * @package System\Schemes\Arr\Exceptions
 *
 * @author  Codememory
 */
class NotValidSchemaException extends ErrorException
{

    /**
     * NotValidSchemaException constructor.
     *
     * @param array $data
     * @param array $causes
     *
     * @throws JsonErrorException
     */
    public function __construct(array $data, array $causes)
    {

        $jsonParser = new JsonParser();

        parent::__construct(sprintf(
            'The array being processed did not pass the scheme check.<br><br>%s <br><br>%s',
            $jsonParser->setData($data)->encode(),
            $this->causes($causes)
        ));

    }

    /**
     * @param array $causes
     *
     * @return string|null
     */
    #[Pure] private function causes(array $causes): ?string
    {

        $causesToString = null;

        foreach ($causes as $key => $type) {
            $causesToString .= sprintf('The %s key is not an %s<br>', $key, $type);
        }

        return $causesToString;

    }

}