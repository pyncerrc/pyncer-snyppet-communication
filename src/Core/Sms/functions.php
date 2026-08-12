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

function clean_phone(string|array $phone): null|string|array
{
    $rule = new PhoneRule(
        allowNanp: PYNCER_COMMUNICATION_PHONE_ALLOW_NANP,
        allowE164: PYNCER_COMMUNICATION_PHONE_ALLOW_E164,
        allowFormatting: PYNCER_COMMUNICATION_PHONE_ALLOW_FORMATTING,
    );

    $isString = false;

    if (is_string($phone)) {
        $isString = true;
        $phone = [
            trim(strval($phone)),
            null,
        ];
    } elseif (!is_array($phone) || !$phone) {
        return null;
    } else {
        $phone = [
            trim(strval($phone[0])),
            trim(strval($phone[1] ?? '')),
        ];

        if ($phone[1] === '') {
            $phone[1] = null;
        }
    }

    if ($phone[0] === '' ||
        !$rule->isValid($phone[0])
    ) {
        return null;
    }

    $phone[0] = $rule->clean($phone[0]);

    if ($isString) {
        $phone = $phone[0];
    }

    return $phone;
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
        $phone = clean_phone($phone);

        if ($phone === null) {
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

        if (PYNCER_COMMUNICATION_PHONE_ALLOW_NANP) {
            if (strlen($phone) === 11 &&
                str_starts_with($phone, '1')
            ) {
                $phone = substr($phone, 1);
            }
        }

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
