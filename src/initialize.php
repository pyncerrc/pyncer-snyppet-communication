<?php
namespace Pyncer\Snyppet\Communication;

use Pyncer\Initializer;

Initializer::defineFrom('Pyncer\Snyppet\Communication\PHONE_ALLOW_E164', 'Pyncer\Validation\PHONE_ALLOW_E164', true);
Initializer::defineFrom('Pyncer\Snyppet\Communication\PHONE_ALLOW_NANP', 'Pyncer\Validation\PHONE_ALLOW_NANP', false);
Initializer::defineFrom('Pyncer\Snyppet\Communication\PHONE_ALLOW_FORMATTING', 'Pyncer\Validation\PHONE_ALLOW_FORMATTING', false);
