<?php
namespace Pyncer\Snyppet\Communication\Transport;

trait ReplaceMessageDataTrait
{
    protected function replaceMessageData(
        string $message,
        array $data,
        bool $isHtml = false,
    ): string
    {
        // Replace [[key]] and [[key|default]] placeholders
        $message = preg_replace_callback(
            '/\[\[([^\]|]+)(?:\|([^\]]*))?\]\]/',
            function (array $matches) use ($data, $isHtml) {
                $key = trim($matches[1]);
                $default = trim($matches[2] ?? '');

                $value = $data[$key] ?? $default;

                if (is_array($value)) {
                    $replacement = $isHtml ?
                        ($value['html'] ?? $default) :
                        ($value['text'] ?? $default);
                } elseif ($isHtml) {
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
