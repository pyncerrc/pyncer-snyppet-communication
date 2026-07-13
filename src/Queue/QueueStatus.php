<?php
namespace Pyncer\Snyppet\Communication\Queue;

enum QueueStatus: string
{
    case QUEUED = 'queued';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case DROPPED = 'dropped';
    case BOUNCED = 'bounced';
}
