<?php
namespace Pyncer\Snyppet\Communication\Table\Communication;

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
use Pyncer\Validation\Rule\UidRule;

class CommunicationValidator extends AbstractValidator
{
    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection);

        $this->addRules(
            'uid',
            new RequiredRule(UidRule::EMPTY),
            new UidRule(),
            new StringRule(
                maxLength: 36,
            ),
        );

        $this->addRules(
            'content_id',
            new IntRule(
                minValue: 0,
            ),
            new IdRule(
                mapper: new ContentMapper($this->getConnection()),
            ),
        );

        $this->addRules(
            'insert_date_time',
            new RequiredRule(DateTimeRule::EMPTY),
            new DateTimeRule(),
        );

        $this->addRules(
            'update_date_time',
            new DateTimeRule(
                allowNull: true,
            ),
        );

        $this->addRules(
            'schedule_date_time',
            new DateTimeRule(
                allowNull: true,
            ),
        );

        $this->addRules(
            'type',
            new RequiredRule(),
            new EnumRule(
                values: ['email', 'sms']
                allowNull: true,
            ),
        );

        $this->addRules(
            'status',
            new RequiredRule(),
            new EnumRule(
                values: ['scheduled', 'queued', 'sending', 'sent', 'failed']
            ),
        );

        $this->addRules(
            'enabled',
            new BoolRule(),
        );
    }
}
