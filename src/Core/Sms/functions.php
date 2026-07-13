<?php
namespace Pyncer\Snyppet\Communication\Sms;

use Pyncer\Validation\Rule\PhoneRule;

use function Pyncer\Array\data_explode as pyncer_array_data_explode;
use function Pyncer\Array\data_explode as pyncer_array_data_implode;

use const Pyncer\Snyppet\Communication\PHONE_ALLOW_E164 as PYNCER_COMMUNICATION_PHONE_ALLOW_E164;
use const Pyncer\Snyppet\Communication\PHONE_ALLOW_NANP as PYNCER_COMMUNICATION_PHONE_ALLOW_NANP;
use const Pyncer\Snyppet\Communication\PHONE_ALLOW_FORMATTING as PYNCER_COMMUNICATION_PHONE_ALLOW_FORMATTING;

function implode_phones(array $phones): null|string
{
    $result = pyncer_array_data_implode($result);

    if ($result === '') {
        return null;
    }

    return $result;
}

function explode_phones(string $phones): array
{
    return pyncer_array_data_explode($phones);
}

function clean_phones(string|array $phones): null|string|array
{
    $isString = is_string($phones);
    if ($isString) {
        $phones = explode_phones($phones);
    }

    $rule = new PhoneRule(
        allowNanp: PYNCER_COMMUNICATION_PHONE_ALLOW_NANP,
        allowE164: PYNCER_COMMUNICATION_PHONE_ALLOW_E164,
        allowFormatting: PYNCER_COMMUNICATION_PHONE_ALLOW_FORMATTING,
    );

    $result = [];

    foreach ($phones as $phone) {
        if (!$rule->isValid($phone)) {
            continue;
        }

        $result[] = $rule->clean($phone);
    }

    if ($isString) {
        $result = implode_phones($result);
    }

    return $result;
}

function unique_phones(string|array $phones): null|string|array
{
    $isString = is_string($phones);
    if ($isString) {
        $phones = explode_phones($phones);
    }

    $phones = clean_phones($phones);

    $map = [];

    foreach ($phones as $value) {
        $phone = preg_replace('/[^\d\+]/', '', $value);

        if (!array_key_exists($phone, $map)) {
            $map[$phone] = $value;
        }
    }

    $result = array_values($map);

    if ($isString) {
        $result = implode_phones($result);
    }

    return $result;
}
