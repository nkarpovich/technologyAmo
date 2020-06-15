<?php
require_once 'bootstrap.php';

use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Exceptions\AmoCRMApiException;
use TechnoAmo\Helper;
use TechnoAmo\Lead;

/** Получение токена, обновление токена при необходимости */
$accessToken = getToken();
try
{
    if ($accessToken->hasExpired())
    {
        /**
         * Получаем токен по рефрешу
         */
        try
        {
            $accessToken = $provider->getAccessToken(new League\OAuth2\Client\Grant\RefreshToken(), [
                'refresh_token' => $accessToken->getRefreshToken(),
            ]);

            saveToken([
                'accessToken' => $accessToken->getToken(),
                'refreshToken' => $accessToken->getRefreshToken(),
                'expires' => $accessToken->getExpires(),
                'baseDomain' => $provider->getBaseDomain(),
            ]);

        } catch (Exception $e)
        {
            die((string)$e);
        }
    }
} catch (Exception $e)
{
    die((string)$e);
}
//\Symfony\Component\VarDumper\VarDumper::dump($accessToken);
$apiClient->setAccessToken($accessToken);
/*$apiClient->setAccessToken($accessToken)
    ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
    ->onAccessTokenRefresh(
        function (AccessTokenInterface $accessToken, string $baseDomain) {
            saveToken(
                [
                    'accessToken' => $accessToken->getToken(),
                    'refreshToken' => $accessToken->getRefreshToken(),
                    'expires' => $accessToken->getExpires(),
                    'baseDomain' => $baseDomain,
                ]
            );
        }
    );*/

//Получаем все файлы лидов, прилетевших из 1С
$arFiles = Helper::scanDir($pathToXml);
if ($arFiles)
{
    foreach ($arFiles as $file)
    {
        $fileName = $pathToXml . $file;
        if (file_exists($fileName))
        {
            $xml = simplexml_load_file($fileName);
            $Lead = new Lead($apiClient, $xml);
            try
            {
                if ($leadId = Helper::xmlAttributeToString($xml, 'ИДАМО'))
                {
                    //Ищем лид по ID
                    $leadId = preg_replace('/[^0-9]/', '', $leadId);
                    $lead = $apiClient->leads()->getOne($leadId);

                }
                elseif ($leadGUID = Helper::xmlAttributeToString($xml, 'GUID'))
                {
                    //Ищем лид по GUID
                    $filter = new LeadsFilter();
                    $filter->setCustomFieldsValues(['1C_GUID' => $leadGUID]);
                    $leadsCollection = $apiClient->leads()->get($filter);
                    if (!$leadsCollection->isEmpty())
                    {
                        $lead = $leadsCollection->first();
                    }
                }
                else
                {
                    die('Не указаны обязательные параметры: ID сделки из АМО или GUID из 1С');
                }
                //Лид не найден
                if ($lead->getId())
                {
                    //                    die('Лид не найден');
                    try
                    {
                        $Lead->create($xml);
                    } catch (AmoCRMApiException $e)
                    {
                        printError($e);
                        die;
                    }
                }
                else
                {
                    die('Лид найден');
                    try
                    {
                        $Lead->update($xml, $lead);
                    } catch (AmoCRMApiException $e)
                    {
                        printError($e);
                        die;
                    }
                }
            } catch (AmoCRMApiException $e)
            {
                printError($e);
                die;
            }
        }
        else
        {
            die('Не удалось открыть файл ' . $fileName);
        }
    }
}
else
{
    die('Нет ни одного XML файла из 1С в директории ' . $pathToXml);
}

/*try
{
    $ownerDetails = $apiClient->getOAuthClient()->getResourceOwner($accessToken);
    printf('Hello, %s!', $ownerDetails->getName());
} catch (Exception $e)
{
    printError($e);
}*/