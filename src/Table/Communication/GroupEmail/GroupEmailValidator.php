<?php
namespace Pyncer\Snyppet\Communication\Table\Communication\GroupEmail;

use Pyncer\Snyppet\Content\Table\Content\ContentMapper;
use Pyncer\Data\Validation\AbstractValidator;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Validation\Rule\BoolRule;
use Pyncer\Validation\Rule\DateTimeRule;
use Pyncer\Validation\Rule\EnumRule;
use Pyncer\Validation\Rule\IdRule;
use Pyncer\Validation\Rule\IntRule;
use Pyncer\Validation\Rule\RequiredRule;
use Pyncer\Validation\Rule\StringRule;

class GroupEmailValidator extends AbstractValidator
{
    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection);

        $this->addRules(
            'communication_id',
            new RequireRule(IntRule::EMPTY),
            new IntRule(
                minValue: 0,
            ),
            new IdRule(
                mapper: new ContentMapper($this->getConnection()),
            ),
        );

        $this->addRules(
            'emails',
            new RequireRule(),
            new StringRule(
                maxLength: 4000000,
            ),
        );

        $this->addRules(
            'cc_emails',
            new EmailRule(),
            new StringRule(
                maxLength: 4000000,
                allowNull: true,
            ),
        );

        $this->addRules(
            'bcc_emails',
            new EmailRule(),
            new StringRule(
                maxLength: 4000000,
                allowNull: true,
            ),
        );

        $this->addRules(
            'data',
            new RequiredRule(),
            new StringRule(
                maxLength: 16000,
                allowNull: true,
            ),
        );

        $this->addRules(
            'sent',
            new BoolRule(),
        );
    }

    public function validateData(array $data): array
    {
        [$data, $errors] = parent::validateData($data);

        [
            $data['emails'],
            $emailsError
        ] = $this->validateEmails($data['emails']);

        if ($emailsError !== null) {
            $errors['emails'] = $emailsError;
        }

        if (($data['cc_emails'] ?? null) !== null) {
            [
                $data['cc_emails'],
                $ccEmailsError
            ] = $this->validateEmails($data['cc_emails']);

            if ($ccEmailsError !== null) {
                $errors['cc_emails'] = $ccEmailsError;
            }
        }

        if (($data['bcc_emails'] ?? null) !== null) {
            [
                $data['bcc_emails'],
                $bccEmailsError
            ] = $this->validateEmails($data['bcc_emails']);

            if ($bccEmailsError !== null) {
                $errors['bcc_emails'] = $bccEmailsError;
            }
        }

        return [$data, $errors];
    }

    protected function validateEmails(string $emails): array
    {
        $emailValues = explode_emails($emails);

        if (in_array(null, $emailValues, true)) {
            return [$emails, 'invalid'];
        }

        $emails = implode_emails($emailValues);

        return [$emails, null];
    }
}
