<?php

namespace DeveloperItsMe\FiscalService\Validation;

class ValidationHelper
{
    public const REGISTRATION_CODE = '/^[a-z]{2}[0-9]{3}[a-z]{2}[0-9]{3}$/';
    public const TIN = '/^([0-9]{13}|[0-9]{8})$/';
    public const HEX_32 = '/^[0-9a-fA-F]{32}$/';
    public const HEX_512 = '/^[0-9a-fA-F]{512}$/';
    public const TAX_PERIOD = '/^((0[1-9])|(1[0-2]))\/(\d{4})$/';
    public const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    public const DATE = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/';

    public static function required(array &$errors, $value, string $field, string $label): void
    {
        if ($value === null || $value === '') {
            $errors[$field][] = "{$label} is required.";
        }
    }

    public static function pattern(array &$errors, $value, string $pattern, string $field, string $label, string $patternName): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!preg_match($pattern, (string) $value)) {
            $errors[$field][] = "{$label} must match {$patternName} format.";
        }
    }

    public static function requiredAndPattern(array &$errors, $value, string $pattern, string $field, string $label, string $patternName): void
    {
        self::required($errors, $value, $field, $label);
        self::pattern($errors, $value, $pattern, $field, $label, $patternName);
    }

    public static function positive(array &$errors, $value, string $field, string $label): void
    {
        if ($value !== null && $value <= 0) {
            $errors[$field][] = "{$label} must be greater than 0.";
        }
    }

    public static function notEmpty(array &$errors, $items, string $field, string $label): void
    {
        if (empty($items)) {
            $errors[$field][] = "{$label} must not be empty.";
        }
    }

    public static function maxLength(array &$errors, $value, int $max, string $field, string $label): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (mb_strlen($value) > $max) {
            $errors[$field][] = "{$label} must not exceed {$max} characters.";
        }
    }
}
