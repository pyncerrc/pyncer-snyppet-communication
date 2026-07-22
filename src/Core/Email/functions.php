<?php
namespace Pyncer\Snyppet\Communication\Email;

use Pyncer\Validation\Rule\EmailRule;

use function Pyncer\Array\data_explode as pyncer_array_data_explode;

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

function clean_emails(string|array $emails): null|string|array
{
    $isString = is_string($emails);
    if ($isString) {
        $emails = explode_emails($emails);
    }

    $result = [];

    $rule = new EmailRule();

    if (array_is_list($emails)) {
        foreach ($emails as $value) {
            if (is_string($value)) {
                $value = explode_email($value);
            } elseif (!is_array($value) || !$value) {
                continue;
            } else {
                $value = [
                    trim(strval($value[0])),
                    trim(strval($value[1] ?? '')),
                ];

                if ($value[1] === '') {
                    $value[1] = null;
                }
            }

            if ($value[0] === '' ||
                !$rule->isValid($value[0])
            ) {
                continue;
            }

            $value[0] = $rule->clean($value[0]);

            $result[] = $value;
        }
    } else {
        foreach ($emails as $email => $name) {
            if (is_int($email)) {
                $value = [
                    trim(strval($name)),
                    null,
                ];
            } else {
                $value = [
                    trim(strval($email)),
                    trim(strval($name)),
                ];

                if ($value[1] === '') {
                    $value[1] = null;
                }
            }

            if ($value[0] === '' ||
                !$rule->isValid($value[0])
            ) {
                continue;
            }

            $value[0] = $rule->clean($value[0]);

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
