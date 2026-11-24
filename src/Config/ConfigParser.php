<?php

declare(strict_types=1);

namespace Varify\Core\Config;

use JsonException;
use stdClass;
use Varify\Core\Id\ExperimentId;
use Varify\Core\Id\VariationId;
use Varify\Core\VarifyError;
use Varify\Core\VarifyErrorReason;

use function array_is_list;
use function get_object_vars;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class ConfigParser
{
    /**
     * @throws VarifyError with reason invalid-config
     */
    public static function parse(string $json): Config
    {
        try {
            $data = json_decode($json, false, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new VarifyError('The Varify config is not valid JSON', VarifyErrorReason::InvalidConfig, $exception);
        }

        $experiments = [];
        foreach (self::list($data, 'experiments') as $experiment) {
            $experiments[] = self::experiment($experiment);
        }

        return Config::of($experiments);
    }

    private static function experiment(mixed $data): Experiment
    {
        $variations = [];
        foreach (self::list($data, 'variations') as $variation) {
            $variations[] = self::variation($variation);
        }

        return new Experiment(
            ExperimentId::make(self::positiveInt($data, 'id')),
            self::string($data, 'slug'),
            self::bool($data, 'tracking_enabled'),
            $variations,
        );
    }

    private static function variation(mixed $data): Variation
    {
        return new Variation(
            VariationId::make(self::positiveInt($data, 'id')),
            self::string($data, 'slug'),
            self::number($data, 'traffic_allocation'),
        );
    }

    /**
     * @return list<mixed>
     */
    private static function list(mixed $data, string $key): array
    {
        $value = self::field($data, $key);
        if (!is_array($value) || !array_is_list($value)) {
            throw self::invalid($key);
        }

        return $value;
    }

    /**
     * @return positive-int
     */
    private static function positiveInt(mixed $data, string $key): int
    {
        $value = self::field($data, $key);
        if (!is_int($value) || $value < 1) {
            throw self::invalid($key);
        }

        return $value;
    }

    private static function string(mixed $data, string $key): string
    {
        $value = self::field($data, $key);
        if (!is_string($value)) {
            throw self::invalid($key);
        }

        return $value;
    }

    private static function bool(mixed $data, string $key): bool
    {
        $value = self::field($data, $key);
        if (!is_bool($value)) {
            throw self::invalid($key);
        }

        return $value;
    }

    private static function number(mixed $data, string $key): float
    {
        $value = self::field($data, $key);
        if (!is_int($value) && !is_float($value)) {
            throw self::invalid($key);
        }

        return (float) $value;
    }

    /**
     * JSON objects are decoded as `stdClass`, so an object never passes for a list, as in varify-sdk-js.
     */
    private static function field(mixed $data, string $key): mixed
    {
        return $data instanceof stdClass ? get_object_vars($data)[$key] ?? null : null;
    }

    private static function invalid(string $key): VarifyError
    {
        return new VarifyError(
            "The Varify config has an unexpected shape at \"{$key}\"",
            VarifyErrorReason::InvalidConfig,
        );
    }
}
