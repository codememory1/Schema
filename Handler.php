<?php

namespace Codememory\Components\Schema;

use Codememory\Components\JsonParser\Exceptions\JsonErrorException;
use Codememory\Components\Markup\Types\YamlType;
use Codememory\Components\Schema\Exceptions\InvalidSchemaException;
use Codememory\Components\JsonParser\JsonParser;
use Codememory\Components\Markup\Markup;
use RuntimeException;
use Codememory\Components\Schema\Traits\AttributesSchemaTrait;
use Codememory\Components\Schema\Traits\TypesSchemaTrait;

/**
 * Class Handler
 * @package System\Schemes\Arr
 *
 * @author  Codememory
 */
class Handler
{

    use TypesSchemaTrait;
    use AttributesSchemaTrait;

    /**
     * @var array
     */
    private array $data;

    /**
     * @var string
     */
    private string $schema;

    /**
     * @var string
     */
    private string $typeSchema;

    /**
     * @var array
     */
    private array $keywords;

    /**
     * @var Utils
     */
    private Utils $utils;

    /**
     * @var array
     */
    private array $causes = [];

    /**
     * @var bool
     */
    private bool $valid = false;

    /**
     * Handler constructor.
     *
     * @param Utils  $utils
     * @param array  $data
     * @param string $schema
     * @param string $typeSchema
     * @param array  $keywords
     */
    public function __construct(Utils $utils, array $data, string $schema, string $typeSchema, array $keywords)
    {

        $this->utils = $utils;
        $this->data = $data;
        $this->schema = $schema;
        $this->typeSchema = $typeSchema;
        $this->keywords = $keywords;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Check if the path passed by @schema key is actually a schema
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param mixed  $schema
     * @param string $path
     *
     * @throws InvalidSchemaException
     */
    private function isSchema(mixed $schema, string $path): void
    {

        if (false === is_array($schema)) {
            throw new InvalidSchemaException($path);
        }

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method returns the schema by reference from
     * the @schema key
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param string $path
     *
     * @return array
     * @throws InvalidSchemaException
     * @throws JsonErrorException
     */
    private function getSchemaOfUrl(string $path): array
    {

        $jsonParser = new JsonParser();

        $schema = file_get_contents($this->schema);
        $schema = $jsonParser->setData($schema)->ofJson()->decode();

        $this->isSchema($schema, $path);

        return $schema;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method returns the schema from a file using the path
     * specified in the schema configuration
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return array
     * @throws InvalidSchemaException
     */
    private function getSchemaOfFile(): array
    {

        $yamlMarkup = new Markup(new YamlType());
        $path = sprintf(
            '%s/%s%s',
            $this->utils->getPath(),
            $this->schema,
            $this->utils->getPrefix()
        );

        $schema = match ($this->utils->getExpansion()) {
            'yaml' => $yamlMarkup->open($path)->get(),
//            'php' => File::getImport($path . '.php')
        };

        $this->isSchema($schema, $path);

        return $schema;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method renews an automatically prepared scheme by
     * determining the type of transfer of the scheme
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param bool $withReservedKeys
     *
     * @return array
     * @throws InvalidSchemaException
     * @throws JsonErrorException
     */
    private function getSchema(bool $withReservedKeys = false): array
    {

        $schema = [];
        $path = $this->schema;

        if ('url' === $this->typeSchema) {
            $schema = $this->getSchemaOfUrl($path);
        } elseif ('file' === $this->typeSchema) {
            $schema = $this->getSchemaOfFile();
        }

        if (false === $withReservedKeys) {
            foreach ($schema as $key => $value) {
                if (preg_match(sprintf('/^%s/', Utils::CIRCUIT_KEY_SYMBOL), $key)) {
                    unset($schema[$key]);
                }
            }
        }

        return $schema;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method returns all the attributes from the schema,
     * the attributes are in an array with the @settings key
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return array
     * @throws InvalidSchemaException
     * @throws JsonErrorException
     */
    private function getAttributes(): array
    {

        if (is_array($this->getSchema(true)[Utils::SETTINGS_SCHEMA])) {
            return $this->getSchema(true)[Utils::SETTINGS_SCHEMA];
        }

        return [];

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * A handler using callback can handle a non-existing
     * schema type
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param string   $type
     * @param callable $handler
     *
     * @return mixed
     */
    private function whenTypeExist(string $type, callable $handler): mixed
    {

        $typeInfo = $this->utils->typeInfo($type);

        if (false === in_array($typeInfo['type'], $this->keywords)) {
            throw new RuntimeException(sprintf(
                'There is no processing type %s for array schema',
                $typeInfo['type']
            ));
        }

        return call_user_func($handler, $typeInfo['type'], $typeInfo['value']);

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Validating values in data array against schema types
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param mixed  $key
     * @param mixed  $data
     * @param string $type
     *
     * @return bool
     */
    private function checkingValuesToData(mixed $key, mixed $data, string $type): bool
    {

        return $this->whenTypeExist($type, function (string $type, ?string $value) use ($key, $data) {
            return call_user_func_array(
                [$this, sprintf('type%s', ucfirst($type))],
                [$type, $value, $key, $data]
            );
        });

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Main data value processor for schema
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param array  $keys
     * @param mixed  $key
     * @param mixed  $data
     * @param string $type
     *
     * @return bool
     */
    private function handlerValues(array $keys, mixed $key, mixed $data, string $type): bool
    {

        if (end($keys) === $key) {
            $checkingValues = $this->checkingValuesToData($key, $data, $type);

            if (false === $checkingValues) {
                $this->createCause($key, $type);

                return false;
            }
        }

        return true;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method that creates the reasons why the data array
     * did not pass the check according to the scheme
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param mixed  $key
     * @param string $type
     *
     * @return Handler
     */
    private function createCause(mixed $key, string $type): Handler
    {

        $this->causes[$key] = $type;

        return $this;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method that calls the methods for handling attributes
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param mixed  $value
     * @param string $attribute
     * @param mixed  $currentKeyWithData
     *
     * @return mixed
     */
    private function callAttribute(mixed $value, string $attribute, mixed $currentKeyWithData): mixed
    {

        return call_user_func_array([$this, $attribute], [$value, $currentKeyWithData]);

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Main method that processes attributes which should
     * return a boolean result for schema validation
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @param mixed $data
     *
     * @return bool
     * @throws InvalidSchemaException
     * @throws JsonErrorException
     */
    private function handlerAttributes(mixed $data): bool
    {

        foreach ($this->getAttributes() as $attr => $value) {
            $attribute = sprintf('attr%s', ucfirst($attr));

            if (method_exists($this, $attribute)) {
                if (false === $this->callAttribute($value, $attribute, $data)) {
                    return false;
                }
            }
        }

        return true;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The main method for processing the entire scheme,
     * in this method the keys are processed according to
     * the scheme and the necessary methods are called for
     * further validation, such as value validation
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return bool
     * @throws InvalidSchemaException
     * @throws JsonErrorException
     */
    private function checkingKeys(): bool
    {

        $schema = $this->getSchema();

        foreach ($schema as $keyString => $type) {
            $keys = explode('.', $keyString);
            $data = $this->data;

            foreach ($keys as $key) {
                if (array_key_exists($key, $data)) {
                    $data = $data[$key];

                    if (false === $this->handlerValues($keys, $key, $data, $type)
                        || false === $this->handlerAttributes($data)) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }

        return true;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Returns an array of all existing causes
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return array
     */
    public function getCauses(): array
    {

        return $this->causes;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * Returns the boolean value of the passed schema validation
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return bool
     */
    public function isValid(): bool
    {

        return $this->valid;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The method returns a clone of the current object
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return Handler
     */
    public function getContext(): Handler
    {

        return clone $this;

    }

    /**
     * =>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>=>
     * The main method for starting processing and for obtaining
     * the result of validation
     * <=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=<=
     *
     * @return $this
     * @throws InvalidSchemaException
     * @throws JsonErrorException
     */
    public function make(): Handler
    {

        $this->valid = $this->checkingKeys();

        return $this;

    }

}