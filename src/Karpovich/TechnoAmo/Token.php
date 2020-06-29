<?php


namespace Karpovich\TechnoAmo;

use AmoCRM\Client\AmoCRMApiClient;
use Monolog\Logger;

/**
 * Методы для работы с токеном
 * Class Token
 * @package Karpovich\TechnoAmo
 */
class Token
{
    public static function setAccessToken(AmoCRMApiClient $apiClient,string $clientAuth,Logger $log){
        /** Получение токена, обновление токена при необходимости */
        try
        {
            $accessToken = getToken();
        } catch (\Exception $e)
        {
            try
            {
                $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($clientAuth);
                if (!$accessToken->hasExpired())
                {
                    saveToken([
                        'accessToken' => $accessToken->getToken(),
                        'refreshToken' => $accessToken->getRefreshToken(),
                        'expires' => $accessToken->getExpires(),
                        'baseDomain' => $apiClient->getAccountBaseDomain(),
                    ]);
                }
            } catch (Exception $e)
            {
                $log->error((string)$e);
                die((string)$e);
            }
        }

        try
        {
            if ($accessToken->hasExpired())
            {
                try
                {
                    $accessToken = $apiClient->getOAuthClient()->getAccessTokenByRefreshToken($accessToken);

                    saveToken([
                        'accessToken' => $accessToken->getToken(),
                        'refreshToken' => $accessToken->getRefreshToken(),
                        'expires' => $accessToken->getExpires(),
                        'baseDomain' => $apiClient->getAccountBaseDomain()
                    ]);

                } catch (Exception $e)
                {
                    $log->error((string)$e);
                    die((string)$e);
                }
            }
        } catch (Exception $e)
        {
            $log->error((string)$e);
            die((string)$e);
        }
        $apiClient->setAccessToken($accessToken);
    }
}