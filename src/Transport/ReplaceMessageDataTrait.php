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
        foreach ($data as $key => $value) {
            if ($isHtml) {
                // TODO:
                $message = str_replace(
                    '[[' . $key . ']]',
                    pyncer_he($value ?? ''),
                    $message
                );
            } else {
                $message = str_replace(
                    '[[' . $key . ']]',
                    $value ?? '',
                    $message
                );
            }
        }

        return $message;
    }
}
