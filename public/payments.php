<?php
require_once 'bootstrap.php';

use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Exceptions\AmoCRMApiException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Karpovich\Helper;
use Karpovich\TechnoAmo\Lead;

$filesystem = new Filesystem();

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

//Получаем все файлы оплат, прилетевших из 1С
$arFiles = \Karpovich\Helper::scanDir($pathToPaymentsXml);
if ($arFiles)
{
    foreach ($arFiles as $file)
    {
        $fileName = $pathToPaymentsXml . $file;
        if (file_exists($fileName))
        {
            $xml = simplexml_load_file($fileName);
            foreach ($xml->children() as $xmlPaymentElement)
            {
                $Lead = new Lead($apiClient, $xmlPaymentElement);
                $leadId = Helper::xmlAttributeToString($xmlPaymentElement, 'IDAMO');;
                $leadGUID = Helper::xmlAttributeToString($xmlPaymentElement, 'GUID');
                try
                {
                    if ($leadId)
                    {
                        //Ищем лид по ID
                        $leadId = preg_replace('/[^0-9]/', '', $leadId);
                        $lead = $apiClient->leads()->getOne($leadId);

                    }
                    elseif ($leadGUID)
                    {
                        //Ищем лид по GUID
                        $filter = new LeadsFilter();
                        $filter->setQuery($leadGUID);
                        $filter->setLimit(1);
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
                    $leadId = $lead->getId();

                    if (!$leadId)
                    {
                        die('Лид не найден');
                    }
                    else
                    {
                        try
                        {
                            echo 'updating Lead ID '.$leadId.'...'.PHP_EOL;
                            $Lead->updatePayment($leadId, $xmlPaymentElement);
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
                echo 'success'.PHP_EOL;
            }
        }
        else
        {
            die('Не удалось открыть файл ' . $fileName);
        }
        try
        {
            $filesystem->rename($fileName, $pathToOldPaymentsXml . $file);
        } catch (IOExceptionInterface $exception)
        {
            echo "An error occurred while renaming your file at " . $exception->getPath();
        }
    }
}
else
{
    die('Нет ни одного XML файла из 1С в директории ' . $pathToPaymentsXml);
}