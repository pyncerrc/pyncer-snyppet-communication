<?php
namespace Pyncer\Snyppet\Communication\Table\Communication\Organization;

use Pyncer\Data\Mapper\AbstractRelationMapper;

class OrganizationRelationMapper extends AbstractRelationMapper
{
    public function getTable(): string
    {
        return 'communication__organization';
    }

    public function getParentIdColumn(): string
    {
        return 'communication_id';
    }

    public function getChildIdColumn(): string
    {
        return 'organization_id';
    }
}
