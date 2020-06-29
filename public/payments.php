<?php
require_once 'bootstrap.php';

use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Exceptions\AmoCRMApiException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Karpovich\Helper;
use Karpovich\TechnoAmo\Lead;

$filesystem = new Filesystem();

// create a log channel
$log = new Logger('payments');
$log->pushHandler(new StreamHandler(__DIR__.'/../logs/payments.log', Logger::INFO));

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

//Получаем все файлы оплат, прилетевших из 1С
$log->info('Start '.date('d.m.Y H:i:s').PHP_EOL);
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
                        $log->error('Не указаны обязательные параметры: ID сделки из АМО или GUID из 1С');
                        die('Не указаны обязательные параметры: ID сделки из АМО или GUID из 1С');
                    }

                    $leadId = $lead->getId();

                    if (!$leadId)
                    {
                        $log->info('Лид не найден');
                        die('Лид не найден');
                    }
                    else
                    {
                        try
                        {
                            $log->info('updating Lead ID ' . $leadId . '...' . PHP_EOL);
                            $Lead->updatePayment($leadId, $xmlPaymentElement);
                        } catch (AmoCRMApiException $e)
                        {
                            $log->error($e->getMessage());
                            printError($e);
                            die;
                        }
                    }
                } catch (AmoCRMApiException $e)
                {
                    $log->error($e->getMessage());
                    printError($e);
                    die;
                }
                $log->info('success' . PHP_EOL);
            }
        }
        else
        {
            $log->error('Не удалось открыть файл ' . $fileName);
            die('Не удалось открыть файл ' . $fileName);
        }
        try
        {
            $filesystem->rename($fileName, $pathToOldPaymentsXml . $file);
        } catch (IOExceptionInterface $exception)
        {
            $log->error("An error occurred while renaming your file at " . $exception->getPath());
            echo "An error occurred while renaming your file at " . $exception->getPath();
        }
    }
}
else
{
    $log->error('Нет ни одного XML файла из 1С в директории ' . $pathToPaymentsXml);
    die('Нет ни одного XML файла из 1С в директории ' . $pathToPaymentsXml);
}