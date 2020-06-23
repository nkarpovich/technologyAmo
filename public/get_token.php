<?php
include_once __DIR__ . '/bootstrap.php';
/*try {
    echo $clientAuth;
    $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($clientAuth);
    if (!$accessToken->hasExpired()) {
        saveToken([
            'accessToken' => $accessToken->getToken(),
            'refreshToken' => $accessToken->getRefreshToken(),
            'expires' => $accessToken->getExpires(),
            'baseDomain' => $apiClient->getAccountBaseDomain(),
        ]);
    }
} catch (Exception $e) {
    printError($e);
//    die((string)$e);
}*/

$accessToken = getToken();
if ($accessToken->hasExpired()){
    echo 'expired';
}