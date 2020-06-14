<?php

use AmoCRM\Client\AmoCRMApiClient;
use Symfony\Component\Dotenv\Dotenv;

include_once __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../config/.env');

$clientId = $_ENV['CLIENT_ID'];
$clientSecret = $_ENV['CLIENT_SECRET'];
$redirectUri = $_ENV['CLIENT_REDIRECT_URI'];
$clientAuth = $_ENV['CLIENT_AUTH'];
$accountBaseDomain = $_ENV['CLIENT_BASE_DOMAIN'];
$pathToXml = __DIR__.'/../resources/xml/';

$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);
$apiClient->setAccountBaseDomain($accountBaseDomain);

include_once __DIR__ . '/token_actions.php';
include_once __DIR__ . '/error_printer.php';