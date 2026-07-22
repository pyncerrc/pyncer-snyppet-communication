<?php
namespace Pyncer\Snyppet\Communication\Install;

use Pyncer\Database\Table\Column\IntSize;
use Pyncer\Database\Table\Column\TextSize;
use Pyncer\Database\Table\ReferentialAction;
use Pyncer\Database\Value;
use Pyncer\Snyppet\AbstractInstall;

class Install extends AbstractInstall
{
    protected function safeInstall(): bool
    {
        $this->connection->createTable('communication')
            ->serial('id')
            ->char('uid', 36)->index()
            ->int('content_id', IntSize::BIG)->index()
            ->dateTime('insert_date_time')->default(Value::NOW)->index()
            ->dateTime('update_date_time')->null()->index()
            ->dateTime('schedule_date_time')->null()->index()
            ->enum('type', ['email', 'sms'])->null()->index()
            ->enum('status', ['scheduled', 'queued', 'sending', 'sent', 'failed'])->default('scheduled')->index()
            ->bool('enabled')->default(false)->index()
            ->index('#unique', 'uid')->unique()
            ->foreignKey(null, 'content_id')
                ->references('content', 'id')
                ->deleteAction(ReferentialAction::CASCADE)
                ->updateAction(ReferentialAction::CASCADE)
            ->execute();

        $this->connection->createTable('communication__group_email')
            ->serial('id')
            ->int('communication_id', IntSize::BIG)->null()->index()
            ->text('emails', TextSize::MEDIUM)
            ->text('cc_emails', TextSize::MEDIUM)->null()
            ->text('bcc_emails', TextSize::MEDIUM)->null()
            ->text('data', TextSize::SMALL)->null()
            ->bool('sent')->default(false)->index()
            ->foreignKey(null, 'communication_id')
                ->references('communication', 'id')
                ->deleteAction(ReferentialAction::CASCADE)
                ->updateAction(ReferentialAction::CASCADE)
            ->execute();

        $this->connection->createTable('communication__queue')
            ->serial('id')
            ->int('communication_id', IntSize::BIG)->null()->index()
            ->string('name', 50)->null()->index()
            ->string('email', 125)->null()->index()
            ->string('phone', 25)->null()->index()
            ->text('data', TextSize::SMALL)->null()
            ->enum('status', ['queued', 'sent', 'delivered', 'dopped', 'bounced'])->default('queued')->index()
            ->int('attempts')->default(0)->index()
            ->bool('opened')->default(false)->index()
            ->bool('unsubscribed')->default(false)->index()
            ->bool('complained')->default(false)->index()
            ->foreignKey(null, 'communication_id')
                ->references('communication', 'id')
                ->deleteAction(ReferentialAction::CASCADE)
                ->updateAction(ReferentialAction::CASCADE)
            ->execute();

        return true;
    }

    protected function safeUninstall(): bool
    {
        if ($this->connection->hasTable('communication__queue')) {
            $this->connection->dropTable('communication__queue');
        }

        if ($this->connection->hasTable('communication')) {
            $this->connection->dropTable('communication');
        }

        return true;
    }

    public function getRequired(): array
    {
        return [
            'content' => '*',
            'task' => '*',
        ];
    }

    /**
     * @inheritdoc
     */
    public function hasRelated(string $snyppetAlias): bool
    {
        switch ($snyppetAlias) {
            case 'contact':
            case 'organization':
                return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function safeInstallRelated(string $snyppetAlias): bool
    {
        switch ($snyppetAlias) {
            case 'contact':
                return $this->installContact();
            case 'organization':
                return $this->installOrganization();
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function safeUninstallRelated(string $snyppetAlias): bool
    {
        switch ($snyppetAlias) {
            case 'contact':
                return $this->uninstallContact();
            case 'organization':
                return $this->uninstallOrganization();
        }

        return false;
    }

    protected function installContact(): bool
    {
        $this->connection->createTable('communication__queue__contact')
            ->serial('id')
            ->int('communication_queue_id', IntSize::BIG)->index()
            ->int('contact_id', IntSize::BIG)->index()
            ->index('#unique', 'communication_queue_id')->unique()
            ->foreignKey(null, 'communication_queue_id')
                ->references('communication__queue', 'id')
                ->deleteAction(ReferentialAction::CASCADE)
                ->updateAction(ReferentialAction::CASCADE)
            ->foreignKey(null, 'contact_id')
                ->references('contact', 'id')
                ->deleteAction(ReferentialAction::CASCADE)
                ->updateAction(ReferentialAction::CASCADE)
            ->execute();

        return true;
    }

    protected function uninstallContact(): bool
    {
        if ($this->connection->hasTable('communication__queue__contact')) {
            $this->connection->dropTable('communication__queue__contact');
        }

        return true;
    }

    protected function installOrganization(): bool
    {
        $this->connection->createTable('communication__organization')
            ->serial('id')
            ->int('communication_id', IntSize::BIG)->index()
            ->int('organization_id', IntSize::BIG)->index()
            ->index('#unique', 'communication_id')->unique()
            ->foreignKey(null, 'communication_id')
                ->references('communication', 'id')
                ->deleteAction(ReferentialAction::CASCADE)
                ->updateAction(ReferentialAction::CASCADE)
            ->foreignKey(null, 'organization_id')
                ->references('organization', 'id')
                ->deleteAction(ReferentialAction::CASCADE)
                ->updateAction(ReferentialAction::CASCADE)
            ->execute();

        return true;
    }

    protected function uninstallOrganization(): bool
    {
        if ($this->connection->hasTable('communication__organization')) {
            $this->connection->dropTable('communication__organization');
        }

        return true;
    }
}
