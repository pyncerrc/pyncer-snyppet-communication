<?php
namespace Pyncer\Snyppet\Communication;

enum CommunicationStatus: string
{
    case SCHEDULED = 'scheduled';
    case QUEUED = 'queued';
    case SENDING = 'sending';
    case SENT = 'sent';
    case FAILED = 'FAILED';
}
