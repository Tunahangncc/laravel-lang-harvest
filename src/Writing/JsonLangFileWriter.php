<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Writing;

/**
 * Merges harvested plain-text strings into a lang/{locale}.json file as
 * identity keys (the source text is both the key and the default
 * value), without ever overwriting an existing value, and writes the
 * result back out.
 */
final class JsonLangFileWriter
{
    /**
     * @param  array<int, string>  $texts
     *
     * @throws UnreadableLangFileException
     */
    public function merge(string $path, array $texts): JsonMergeResult
    {
        $data = $this->readExisting($path);
        $added = [];

        foreach ($texts as $text) {
            if (array_key_exists($text, $data)) {
                continue;
            }

            $data[$text] = $text;
            $added[] = $text;
        }

        return new JsonMergeResult($data, $added);
    }

    /**
     * @param  array<string, string>  $data
     */
    public function write(string $path, array $data): void
    {
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        file_put_contents($path, $json.PHP_EOL);
    }

    /**
     * @return array<string, string>
     */
    private function readExisting(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw UnreadableLangFileException::unreadable($path);
        }

        if (trim($contents) === '') {
            return [];
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded) || ($decoded !== [] && array_is_list($decoded))) {
            throw UnreadableLangFileException::invalidJson($path);
        }

        return $decoded;
    }
}
