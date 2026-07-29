<?php
namespace Pyncer\Snyppet\Communication\Transport;

use function Pyncer\he as pyncer_he;

trait ReplaceMessageDataTrait
{
    protected function replaceMessageData(
        string $message,
        array $data,
        string $type = 'text/plain',
    ): string
    {
        // Replace [[key]] and [[key|default]] placeholders
        $message = preg_replace_callback(
            '/\[\[([^\]|]+)(?:\|([^\]]*))?\]\]/',
            function (array $matches) use ($data, $type) {
                $key = trim($matches[1]);
                $default = trim($matches[2] ?? '');

                $value = $data[$key] ?? $default;

                if (is_array($value)) {
                    $replacement = $value[$type] ?? $default);
                } elseif ($type === 'text/html') {
                    $replacement = pyncer_he($value);
                } else {
                    $replacement = $value;
                }

                return $replacement;
            },
            $message
        );

        return $message;
    }
}
