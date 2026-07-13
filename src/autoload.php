<?php
use Pyncer\Snyppet\Snyppet;
use Pyncer\Snyppet\SnyppetManager;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'functions.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Email' . DIRECTORY_SEPARATOR . 'functions.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Phone' . DIRECTORY_SEPARATOR . 'functions.php';

SnyppetManager::register(new Snyppet(
    'communication',
    dirname(__DIR__),
));
