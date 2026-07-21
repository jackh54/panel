<?php

if (!function_exists('is_digit')) {
    /**
     * Deal with normal (and irritating) PHP behavior to determine if
     * a value is a non-float positive integer.
     */
    function is_digit(mixed $value): bool
    {
        return !is_bool($value) && ctype_digit(strval($value));
    }
}

if (!function_exists('sentry_release')) {
    /**
     * Resolve the Sentry release name shared by PHP and the React client.
     */
    function sentry_release(): ?string
    {
        $configured = config('sentry.release');
        if (is_string($configured) && trim($configured) !== '') {
            return trim($configured);
        }

        $fromEnv = trim((string) env('SENTRY_RELEASE', ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        $version = trim((string) config('app.version', ''));
        if ($version === '' || $version === 'canary') {
            return null;
        }

        return 'panel@' . ltrim($version, 'v');
    }
}

if (!function_exists('object_get_strict')) {
    /**
     * Get an object using dot notation. An object key with a value of null is still considered valid
     * and will not trigger the response of a default value (unlike object_get).
     */
    function object_get_strict(object $object, ?string $key, $default = null): mixed
    {
        if (is_null($key) || trim($key) == '') {
            return $object;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_object($object) || !property_exists($object, $segment)) {
                return value($default);
            }

            $object = $object->{$segment};
        }

        return $object;
    }
}
