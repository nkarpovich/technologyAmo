<?php


namespace Karpovich\TechnoAmo;

use League\OAuth2\Client\Token\AccessToken;
use AmoCRM\Client\AmoCRMApiClient;
use Exception;
use Monolog\Logger;

/**
 * Методы для работы с токеном
 * Class Token
 * @package Karpovich\TechnoAmo
 */
class Token
{
    const TOKEN_FILE = __DIR__ . DIRECTORY_SEPARATOR . '..' .
    DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'token_info.json';

    public static function setAccessToken(AmoCRMApiClient $apiClient, string $clientAuth, Logger $log)
    {
        /** Получение токена, обновление токена при необходимости */
        try {
            $accessToken = self::getToken();
        } catch (Exception $e) {
            try {
                $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($clientAuth);
                if (!$accessToken->hasExpired()) {
                    self::saveToken([
                        'accessToken' => $accessToken->getToken(),
                        'refreshToken' => $accessToken->getRefreshToken(),
                        'expires' => $accessToken->getExpires(),
                        'baseDomain' => $apiClient->getAccountBaseDomain(),
                    ]);
                }
            } catch (Exception $e) {
                $log->error((string)$e);
                die((string)$e);
            }
        }

        try {
            if ($accessToken->hasExpired()) {
                try {
                    $accessToken = $apiClient->getOAuthClient()->getAccessTokenByRefreshToken($accessToken);

                    self::saveToken([
                        'accessToken' => $accessToken->getToken(),
                        'refreshToken' => $accessToken->getRefreshToken(),
                        'expires' => $accessToken->getExpires(),
                        'baseDomain' => $apiClient->getAccountBaseDomain()
                    ]);
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
     * @param array $accessToken
     */
    public static function saveToken($accessToken)
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

            file_put_contents(self::TOKEN_FILE, json_encode($data));
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }

    /**
     * @return AccessToken
     * @throws Exception
     */
    public static function getToken()
    {
        if (!file_exists(self::TOKEN_FILE)) {
            throw new Exception('Access token file not found');
        }

        $accessToken = json_decode(file_get_contents(self::TOKEN_FILE), true);

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
