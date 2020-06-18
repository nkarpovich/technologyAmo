<?php
require_once 'bootstrap.php';

use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Exceptions\AmoCRMApiException;
use Karpovich\Helper;
use Karpovich\TechnoAmo\Lead;

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
        die((string)$e);
    }
}

try
{
    if ($accessToken->hasExpired())
    {
        /**
         * Получаем токен по рефрешу
         */
      /*  try
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
        }*/
    }
} catch (Exception $e)
{
    die((string)$e);
}
$apiClient->setAccessToken($accessToken);

//Ручное тестирование запросов
/*$request = $apiClient->getRequest();
try
{
    $queryResult = $request->get('/api/v4/leads/unsorted');
} catch (\AmoCRM\Exceptions\AmoCRMoAuthApiException $e)
{
} catch (AmoCRMApiException $e)
{
}*/
//Получаем все файлы лидов, прилетевших из 1С
$arFiles = \Karpovich\Helper::scanDir($pathToXml);
if ($arFiles)
{
    foreach ($arFiles as $file)
    {
        $fileName = $pathToXml . $file;
        if (file_exists($fileName))
        {
            $xml = simplexml_load_file($fileName);
            $Lead = new Lead($apiClient, $xml);
            $leadId = Helper::xmlAttributeToString($xml, 'ИДАМО');
            $leadGUID = Helper::xmlAttributeToString($xml, 'GUID');
            try
            {
                if ($leadId)
                {
                    echo 'leadId '.$leadId;
                    //Ищем лид по ID
                    $leadId = preg_replace('/[^0-9]/', '', $leadId);
                    $lead = $apiClient->leads()->getOne($leadId);

                }
                elseif ($leadGUID)
                {
                    echo 'leadGUID '.$leadGUID;
                    //Ищем лид по GUID
                    $filter = new LeadsFilter();
                    /*$filter->setCustomFieldsValues()*/

//                    $filter->setCustomFieldsValues([690570 => (string)$leadGUID]);
                    $filter->setQuery($leadGUID);
                    $filter->setLimit(1);
//                    $filter->setIds(27614052);
                    $leadsCollection = $apiClient->leads()->get($filter);

                    if (!$leadsCollection->isEmpty())
                    {

                        \Symfony\Component\VarDumper\VarDumper::dump($leadsCollection->toArray());
                        exit();
                        $lead = $leadsCollection->first();
                    }
                }
                else
                {
                    die('Не указаны обязательные параметры: ID сделки из АМО или GUID из 1С');
                }
                $leadId = $lead->getId();
                //Лид не найден
                if (!$leadId)
                {
                    try
                    {
                        $Lead->create();
                    } catch (AmoCRMApiException $e)
                    {
                        printError($e);
                        die;
                    }
                }
                else
                {
                    \Symfony\Component\VarDumper\VarDumper::dump($leadId);
                    die($leadId);
                    try
                    {
                        $Lead->update($leadId);
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
        exit();
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