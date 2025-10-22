<?php

namespace App\Helpers;

class XssHelper
{
    /**
     * Sanitize input by removing script tags and other potentially dangerous elements
     *
     * @param string|null $input
     * @return string|null
     */
    public static function sanitize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $input = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $input);
        
        $input = preg_replace('/<(iframe|object|embed|form|input|button|select|textarea|link|meta|style)[^>]*>/i', '', $input);
        
        $input = preg_replace('/javascript:/i', '', $input);
        
        $input = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $input);
        
        $input = preg_replace('/\s*srcdoc\s*=\s*["\'][^"\']*["\']/i', '', $input);
        
        $input = trim($input);
        
        return $input;
    }

    /**
     * Clean array of values recursively
     *
     * @param array $data
     * @return array
     */
    public static function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = self::sanitize($value);
            } elseif (is_array($value)) {
                $data[$key] = self::sanitizeArray($value);
            }
        }
        
        return $data;
    }

    /**
     * Strip script tags specifically (basic protection)
     *
     * @param string|null $input
     * @return string|null
     */
    public static function stripScriptTags(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        return preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $input);
    }
}