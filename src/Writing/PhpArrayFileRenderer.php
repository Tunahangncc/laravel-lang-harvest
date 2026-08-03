<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Writing;

/**
 * Renders a nested associative array back into a `<?php return [...];`
 * lang file, following Laravel's own convention for these files.
 *
 * This regenerates the whole file from the array structure, so any
 * comments or custom formatting in a hand-edited file are not
 * preserved -- only the key/value structure is kept.
 */
final class PhpArrayFileRenderer
{
    /**
     * @param  array<array-key, mixed>  $data
     */
    public function render(array $data): string
    {
        return "<?php\n\nreturn ".$this->renderArray($data, 0).";\n";
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function renderArray(array $data, int $depth): string
    {
        if ($data === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth + 1);
        $closingIndent = str_repeat('    ', $depth);
        $isList = array_is_list($data);

        $lines = [];

        foreach ($data as $key => $value) {
            $renderedValue = is_array($value)
                ? $this->renderArray($value, $depth + 1)
                : $this->renderScalar($value);

            $lines[] = $isList
                ? "{$indent}{$renderedValue},"
                : "{$indent}{$this->renderKey($key)} => {$renderedValue},";
        }

        return "[\n".implode("\n", $lines)."\n{$closingIndent}]";
    }

    private function renderKey(int|string $key): string
    {
        return is_int($key) ? (string) $key : $this->renderScalar($key);
    }

    private function renderScalar(mixed $value): string
    {
        if (is_string($value)) {
            return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $value)."'";
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return 'null';
    }
}
