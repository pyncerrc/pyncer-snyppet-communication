<?php
namespace Pyncer\Snyppet\Communication\Table\Communication\Queue;

use Pyncer\Snyppet\Content\Table\Content\ContentMapper;
use Pyncer\Data\Validation\AbstractValidator;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Validation\Rule\BoolRule;
use Pyncer\Validation\Rule\DateTimeRule;
use Pyncer\Validation\Rule\EnumRule;
use Pyncer\Validation\Rule\IdRule;
use Pyncer\Validation\Rule\IntRule;
use Pyncer\Validation\Rule\PhoneRule;
use Pyncer\Validation\Rule\RequiredRule;
use Pyncer\Validation\Rule\StringRule;
use Pyncer\Validation\Rule\UidRule;

use const Pyncer\Snyppet\Communication\PHONE_ALLOW_E164 as PYNCER_COMMUNICATION_PHONE_ALLOW_E164;
use const Pyncer\Snyppet\Communication\PHONE_ALLOW_NANP as PYNCER_COMMUNICATION_PHONE_ALLOW_NANP;
use const Pyncer\Snyppet\Communication\PHONE_ALLOW_FORMATTING as PYNCER_COMMUNICATION_PHONE_ALLOW_FORMATTING;

class QueueValidator extends AbstractValidator
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
            'name',
            new StringRule(
                maxLength: 50,
                allowNull: true,
            ),
        );

        $this->addRules(
            'email',
            new EmailRule(),
            new StringRule(
                maxLength: 125,
                allowNull: true,
            ),
        );

        $this->addRules(
            'phone',
            new PhoneRule(
                allowNanp: PYNCER_COMMUNICATION_PHONE_ALLOW_NANP,
                allowE164: PYNCER_COMMUNICATION_PHONE_ALLOW_E164,
                allowFormatting: PYNCER_COMMUNICATION_PHONE_ALLOW_FORMATTING,
            ),
            new StringRule(
                maxLength: 25,
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
            'status',
            new RequiredRule(),
            new EnumRule(
                values: ['queued', 'sent', 'delivered', 'dopped', 'bounced']
            ),
        );

        $this->addRules(
            'attempts',
            new IntRule(
                minValue: 0,
            ),
        )

        $this->addRules(
            'opened',
            new BoolRule(),
        );

        $this->addRules(
            'unsubscribed',
            new BoolRule(),
        );

        $this->addRules(
            'complained',
            new BoolRule(),
        );
    }
}
