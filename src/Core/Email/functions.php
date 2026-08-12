<?php
namespace Pyncer\Snyppet\Communication\Email;

use Pyncer\Validation\Rule\EmailRule;

use function Pyncer\Array\data_explode as pyncer_array_data_explode;

use const Pyncer\Snyppet\Communication\EMAIL_ALLOW_UPPERCASE as PYNCER_COMMUNICATION_EMAIL_ALLOW_UPPERCASE;

function implode_email(string $email, string $name): string
{
    return $name . ' <' . $email . '>';
}

function implode_emails(array $emails): ?string
{
    $result = [];

    if (array_is_list($emails)) {
        foreach ($emails as $email) {
            if (is_array($email)) {
                if ($email[1] === null || $email[1] === '') {
                    $result[] = $email[0];
                } else {
                    $result[] = $email[1] . ' <' . $email[0] . '>';
                }
            } else {
                $result[] = $email;
            }
        }
    } else  {
        foreach ($emails as $email => $name) {
            if (is_int($email)) {
                $result[] = $name;
            } else {
                $result[] = $name . ' <' . $email . '>';
            }
        }
    }

    if (!$result) {
        return null;
    }

    return implode(',', $result);
}

function explode_email(string $email): ?array
{
    $email = trim($email);

    if (str_ends_with($email, ">")) {
        $pos = strpos($email, "<");
        if ($pos === false) {
            return null;
        }

        $name = trim(substr($email, 0, $pos));
        $email = substr($email, $pos + 1, strlen($email) - $pos - 2);

        if ($name === '') {
            $name = null;
        }

        return [$email, $name];
    }

    return [$email, null];
}

function explode_emails(string $emails): array
{
    $result = [];

    $emails = pyncer_array_data_explode(",", $emails);
    foreach ($emails as $email) {
        $result[] = explode_email($email);
    }

    return $result;
}

function clean_email(string|array $email): null|string|array
{
    $rule = new EmailRule(
        allowUppercase: PYNCER_COMMUNICATION_EMAIL_ALLOW_UPPERCASE,
    );

    $isString = false;

    if (is_string($email)) {
        $isString = true;
        $email = explode_email($email);
    } elseif (!is_array($email) || !$email) {
        return null;
    } else {
        $email = [
            trim(strval($email[0])),
            trim(strval($email[1] ?? '')),
        ];

        if ($email[1] === '') {
            $email[1] = null;
        }
    }

    if ($email[0] === '' ||
        !$rule->isValid($email[0])
    ) {
        return null;
    }

    $email[0] = $rule->clean($email[0]);

    if ($isString) {
        if ($email[1] === null) {
            $email = $email[0];
        } else {
            $email = implode_email($email[0], $email[1]);
        }
    }

    return $email;
}

function clean_emails(string|array $emails): null|string|array
{
    $isString = is_string($emails);
    if ($isString) {
        $emails = explode_emails($emails);
    }

    $result = [];

    if (array_is_list($emails)) {
        foreach ($emails as $value) {
            if (is_string($value)) {
                $value = explode_email($value);
            }

            $value = clean_email($value);

            if ($value === null) {
                continue;
            }

            $result[] = $value;
        }
    } else {
        foreach ($emails as $email => $name) {
            if (is_int($email)) {
                $value = [$name, null];
            } else {
                $value = [$email, $name];
            }

            $value = clean_email($value);

            if ($value === null) {
                continue;
            }

            $result[] = $value;
        }
    }

    if ($isString) {
        $result = implode_emails($result);
    }

    return $result;
}

function unique_emails(string|array $emails): null|string|array
{
    $isString = is_string($emails);
    if ($isString) {
        $emails = explode_emails($emails);
    }

    $emails = clean_emails($emails);

    $map = [];
    $order = [];

    foreach ($emails as $value) {
        $email = strtolower($value[0]);
        $name  = $value[1];

        if (!array_key_exists($email, $map)) {
            $map[$email] = $value;
            $order[] = $email;
        } else {
            $existingName = $map[$email][1];

            if ($existingName === null && $name !== null) {
                $map[$email] = $value;
            }
        }
    }

    $result = [];

    foreach ($order as $email) {
        $result[] = $map[$email];
    }

    if ($isString) {
        $result = implode_emails($result);
    }

    return $result;
}
