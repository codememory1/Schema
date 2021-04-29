<?php

namespace Codememory\Components\Schema;

use Codememory\Components\Caching\Exceptions\ConfigPathNotExistException;
use Codememory\Components\Configuration\Config;
use Codememory\Components\Environment\Exceptions\EnvironmentVariableNotFoundException;
use Codememory\Components\Environment\Exceptions\IncorrectPathToEnviException;
use Codememory\Components\Environment\Exceptions\ParsingErrorException;
use Codememory\Components\Environment\Exceptions\VariableParsingErrorException;
use Codememory\Components\JsonParser\Exceptions\JsonErrorException;
use Codememory\Components\Schema\Exceptions\NotValidSchemaException;
use Codememory\FileSystem\Interfaces\FileInterface;
use JetBrains\PhpStorm\Pure;
use RuntimeException;

/**
 * Class Schema
 * @package System\Schemes\Arr
 *
 * @author  Codememory
 */
class Schema
{

    /**
     * @var array
     */
    private array $data = [];

    /**
     * @var array|string[]
     */
    private array $keywords = [
        'array', 'notEmptyArray', 'string', 'number',
        'object', 'multiArray'
    ];

    /**
     * @var Utils
     */
    private Utils $utils;

    /**
     * @var bool
     */
    private bool $valid = false;

    /**
     * @var array
     */
    private array $causes = [];

    /**
     * Schema constructor.
     *
     * @param FileInterface $filesystem
     *
     * @throws ConfigPathNotExistException
     * @throws EnvironmentVariableNotFoundException
     * @throws IncorrectPathToEnviException
     * @throws ParsingErrorException
     * @throws VariableParsingErrorException
     */
    public function __construct(FileInterface $filesystem)
    {

        $this->utils = new Utils(new Config($filesystem));

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Set the data to be validated according to the scheme.
     * The data must be of type array, but no one bothers to
     * validate other formats, such as json, yaml. You just need
     * to parse this format and pass it as a php array
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param array $array
     *
     * @return $this
     */
    public function setArray(array $array): Schema
    {

        $this->data = $array;

        return $this;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method restores the path to the schema which is
     * specified in the processed array by the key @schema
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return string
     */
    private function getUrlSchema(): string
    {

        if (false === $this->checkSchemaToArray()) {
            throw new RuntimeException(sprintf(
                'The path to the schema was not specified. Add %s key to array',
                Utils::PATH_TO_SCHEMA
            ));
        }

        return $this->data[Utils::PATH_TO_SCHEMA];

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method returns the type of the schema by which the
     * schema should be obtained, it can be file and url by default,
     * the type is specified in the schema configuration
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return string
     */
    private function getTypeSchema(): string
    {

        if (array_key_exists(Utils::TYPE_SCHEMA, $this->data)) {
            return $this->data[Utils::TYPE_SCHEMA];
        }

        return $this->utils->storageTypeSchema();

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Checking the existence of a schema key in the
     * array being processed
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return bool
     */
    #[Pure] private function checkSchemaToArray(): bool
    {

        return array_key_exists(Utils::PATH_TO_SCHEMA, $this->data);

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method returns an exception if the array being
     * processed did not pass the schema check
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return Schema
     */
    public function validWithException(): Schema
    {

        return $this->whenNotValid(function () {
            throw new NotValidSchemaException($this->data, $this->causes);
        });

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Custom handler method, with which you can create your
     * own handler if the array being processed did not pass
     * the scheme check
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param callable $handler
     *
     * @return $this
     */
    public function whenNotValid(callable $handler): Schema
    {

        if (false === $this->valid) {
            call_user_func($handler, $this);
        }

        return $this;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method returns boolean by checking if the array passed
     * the schema check
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return bool
     */
    public function isValid(): bool
    {

        return $this->valid;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * A handler method that is called by the main class for
     * processing an array according to the scheme
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return Schema
     * @throws Exceptions\InvalidSchemaException
     * @throws JsonErrorException
     */
    private function handler(): Schema
    {

        $handler = new Handler($this->utils, $this->data, $this->getUrlSchema(), $this->getTypeSchema(), $this->keywords);
        $make = $handler->make();

        $this->valid = $make->isValid();
        $this->causes = $make->getCauses();

        return $this;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Starts processing an array according to the scheme
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return Schema
     * @throws Exceptions\InvalidSchemaException
     * @throws JsonErrorException
     */
    public function exec(): Schema
    {

        return $this->handler();

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method returns the currently processed array, BUT if
     * the array did not pass the check according to the scheme,
     * the method will return an empty array
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return array
     */
    public function get(): array
    {

        if ($this->isValid()) {
            unset($this->data[Utils::PATH_TO_SCHEMA]);

            return $this->data;
        }

        return [];

    }

}