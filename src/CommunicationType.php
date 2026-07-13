<?php
namespace Pyncer\Snyppet\Communication;

enum CommunicationType: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
}
