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
$pathToLeadsXml = __DIR__.'/../resources/leads/';
$pathToPaymentsXml = __DIR__.'/../resources/payments/';
$pathToOldLeadsXml = __DIR__.'/../resources/old_leads/';
$pathToOldPaymentsXml = __DIR__.'/../resources/old_payments/';

$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);
$apiClient->setAccountBaseDomain($accountBaseDomain);

include_once __DIR__ . '/token_actions.php';
include_once __DIR__ . '/error_printer.php';