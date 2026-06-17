<?php

namespace App\Support;

use JsonException;

class SafeJsonParser
{
    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public static function decodeObject(string $raw): array
    {
        $candidate = self::stripCodeFence($raw);

        if ($decoded = self::tryDecodeObject($candidate)) {
            return $decoded;
        }

        $jsonObject = self::extractFirstJsonObject($candidate);

        if ($jsonObject && ($decoded = self::tryDecodeObject($jsonObject))) {
            return $decoded;
        }

        throw new JsonException('Respons AI bukan JSON valid.');
    }

    private static function stripCodeFence(string $raw): string
    {
        $raw = trim($raw);
        $raw = preg_replace('/\A```(?:json)?\s*/i', '', $raw) ?? $raw;
        $raw = preg_replace('/\s*```\z/', '', $raw) ?? $raw;

        return trim($raw);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function tryDecodeObject(string $json): ?array
    {
        try {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private static function extractFirstJsonObject(string $text): ?string
    {
        $start = strpos($text, '{');

        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }

                if ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;
                continue;
            }

            if ($char === '{') {
                $depth++;
                continue;
            }

            if ($char !== '}') {
                continue;
            }

            $depth--;

            if ($depth === 0) {
                return substr($text, $start, $i - $start + 1);
            }
        }

        return null;
    }
}
