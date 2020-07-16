<?php


namespace Karpovich\TechnoAmo;

use League\OAuth2\Client\Token\AccessToken;
use AmoCRM\Client\AmoCRMApiClient;
use Exception;
use Monolog\Logger;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Методы для работы с токеном
 * Class Token
 * @package Karpovich\TechnoAmo
 */
class Token
{

    public static function setAccessToken(
        AmoCRMApiClient $apiClient,
        string $clientAuth,
        Logger $log,
        string $pathToTokenFile
    ) {
        /** Получение токена, обновление токена при необходимости */
        try {
            $accessToken = self::getToken($pathToTokenFile);
        } catch (Exception $e) {
            try {
                $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($clientAuth);
                if (!$accessToken->hasExpired()) {
                    self::saveToken(
                        $pathToTokenFile,
                        ['accessToken' => $accessToken->getToken(),
                        'refreshToken' => $accessToken->getRefreshToken(),
                        'expires' => $accessToken->getExpires(),
                        'baseDomain' => $apiClient->getAccountBaseDomain(),
                        ]
                    );
                }
            } catch (Exception $e) {
                $log->error((string)$e);
                die((string)$e);
            }
        }

        try {
            if ($accessToken->hasExpired()) {
                try {
                    echo $accessToken->getRefreshToken();
                    exit();
                    $accessToken = $apiClient->getOAuthClient()->getAccessTokenByRefreshToken($accessToken);
                    self::saveToken(
                        $pathToTokenFile,
                        [
                        'accessToken' => $accessToken->getToken(),
                        'refreshToken' => $accessToken->getRefreshToken(),
                        'expires' => $accessToken->getExpires(),
                        'baseDomain' => $apiClient->getAccountBaseDomain()
                        ]
                    );
                } catch (Exception $e) {
                    $log->error((string)$e);
                    die((string)$e);
                }
            }
        } catch (Exception $e) {
            $log->error((string)$e);
            die((string)$e);
        }
        $apiClient->setAccessToken($accessToken);
    }

    /**
     * @param $pathToTokenFile
     * @param array $accessToken
     */
    public static function saveToken(string $pathToTokenFile, array $accessToken)
    {
        if (isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            $data = [
                'accessToken' => $accessToken['accessToken'],
                'expires' => $accessToken['expires'],
                'refreshToken' => $accessToken['refreshToken'],
                'baseDomain' => $accessToken['baseDomain'],
            ];

            file_put_contents($pathToTokenFile, json_encode($data));
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }

    /**
     * @param $pathToTokenFile
     * @return AccessToken
     * @throws Exception
     */
    public static function getToken($pathToTokenFile)
    {
        if (!file_exists($pathToTokenFile)) {
            throw new Exception('Access token file not found');
        }

        $accessToken = json_decode(file_get_contents($pathToTokenFile), true);

        if (isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            return new AccessToken([
                'access_token' => $accessToken['accessToken'],
                'refresh_token' => $accessToken['refreshToken'],
                'expires' => $accessToken['expires'],
                'baseDomain' => $accessToken['baseDomain'],
            ]);
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }
}
