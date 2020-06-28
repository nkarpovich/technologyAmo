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
$baseDir = $_ENV['BASE_DIR'];
$accountBaseDomain = $_ENV['CLIENT_BASE_DOMAIN'];
$pathToLeadsXml = $baseDir.$_ENV['PATH_TO_LEADS_DIR'];
$pathToPaymentsXml = $baseDir.$_ENV['PATH_TO_PAYMENTS_DIR'];
$pathToOldLeadsXml = $baseDir.$_ENV['PATH_TO_OLD_LEADS_DIR'];
$pathToOldPaymentsXml = $baseDir.$_ENV['PATH_TO_OLD_PAYMENTS_DIR'];

$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);
$apiClient->setAccountBaseDomain($accountBaseDomain);

include_once __DIR__ . '/token_actions.php';
include_once __DIR__ . '/error_printer.php';