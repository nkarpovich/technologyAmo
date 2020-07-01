<?php

use AmoCRM\Client\AmoCRMApiClient;
use Symfony\Component\Dotenv\Dotenv;

include_once __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv();
if (file_exists(__DIR__ . '/../config/.prod.env')) {
    $dotenv->load(__DIR__ . '/../config/.prod.env');
} else {
    $dotenv->load(__DIR__ . '/../config/.env');
}

$clientId = $_ENV['CLIENT_ID'];
$clientSecret = $_ENV['CLIENT_SECRET'];
$redirectUri = $_ENV['CLIENT_REDIRECT_URI'];
$clientAuth = $_ENV['CLIENT_AUTH'];
$baseDir = $_ENV['BASE_DIR'];
$accountBaseDomain = $_ENV['CLIENT_BASE_DOMAIN'];
$pathToLeadsXml = $baseDir . $_ENV['PATH_TO_LEADS_DIR'];
$pathToPaymentsXml = $baseDir . $_ENV['PATH_TO_PAYMENTS_DIR'];
$pathToOldLeadsXml = $baseDir . $_ENV['PATH_TO_OLD_LEADS_DIR'];
$pathToOldPaymentsXml = $baseDir . $_ENV['PATH_TO_OLD_PAYMENTS_DIR'];
$pathToExportedLeadsFile = $baseDir . $_ENV['PATH_TO_EXPORTED_LEADS_FROM_AMO'];
$pathToTokenFile = __DIR__.'/../config/token_info.json';
$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);
$apiClient->setAccountBaseDomain($accountBaseDomain);
