<?php

namespace Codememory\Components\Schema;

use Codememory\Components\Configuration\Interfaces\ConfigInterface;
use Codememory\Support\Str;

/**
 * Class Utils
 * @package System\Schemes\Arr
 *
 * @author  Codememory
 */
class Utils
{

    public const PATH_TO_SCHEMA = '@schema';
    public const TYPE_SCHEMA = '@typeSchema';
    public const SETTINGS_SCHEMA = '@settings';
    public const CIRCUIT_KEY_SYMBOL = '@';
    private const STORAGE_TYPE_SCHEMA = 'file';
    private const EXPANSION = 'yaml';

    /**
     * @var array|mixed
     */
    private array $config;

    /**
     * Utils constructor.
     */
    public function __construct(ConfigInterface $config)
    {

        $this->config = $config->open('schema')->get('array');

    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {

        return Str::asPath($this->config['path']) ?? null;

    }

    /**
     * @return string|null
     */
    public function getPrefix(): ?string
    {

        return $this->config['prefix'] ?? null;

    }

    /**
     * @return string
     */
    public function storageTypeSchema(): string
    {

        return $this->config['storageType'] ?? self::STORAGE_TYPE_SCHEMA;

    }

    /**
     * @return string
     */
    public function getExpansion(): string
    {

        return $this->config['expansion'] ?? self::EXPANSION;

    }

    /**
     * @param string $type
     *
     * @return string|null
     */
    public function typeValue(string $type): ?string
    {

        return $this->typeInfo($type)['value'];

    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function typeInfo(string $type): array
    {

        preg_match('/(?<type>[^\[\]]+)(\[(?<value>.*)])?/', $type, $match);

        return [
            'type'  => $match['type'],
            'value' => $match['value'] ?? null
        ];

    }

}