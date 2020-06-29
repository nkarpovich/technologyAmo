<?php
require_once 'bootstrap.php';

use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Exceptions\AmoCRMApiException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
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
$log->info('Start '.date('d.m.Y H:i:s').PHP_EOL);
//Получаем все файлы лидов, прилетевших из 1С
$arFiles = \Karpovich\Helper::scanDir($pathToLeadsXml);
if ($arFiles)
{
    foreach ($arFiles as $file)
    {
        $fileName = $pathToLeadsXml . $file;
        if (file_exists($fileName))
        {
            $xml = simplexml_load_file($fileName);
            $Lead = new Lead($apiClient, $xml);
            $leadId = Helper::xmlAttributeToString($xml, 'ИДАМО');;
            $leadGUID = Helper::xmlAttributeToString($xml, 'GUID');
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
                    try
                    {
                        $leadsCollection = $apiClient->leads()->get($filter);
                        if (!$leadsCollection->isEmpty())
                        {
                            //                        \Symfony\Component\VarDumper\VarDumper::dump($leadsCollection->toArray());
                            $lead = $leadsCollection->first();
                        }
                        $leadId = $lead->getId();
                    }catch (AmoCRMApiException $e)
                    {
                        if($e->getCode() != '204')
                        {
                            $log->error($e->getMessage());
                            printError($e);
                            die;
                        }else{
                            $leadId = false;
                        }
                    }
                }
                else
                {
                    $log->error('Не указаны обязательные параметры: ID сделки из АМО или GUID из 1С');
                    die('Не указаны обязательные параметры: ID сделки из АМО или GUID из 1С');
                }

                //Лид не найден
                if (!$leadId)
                {
                    try
                    {
                        $log->info('creating new Lead GUID '.$leadGUID.PHP_EOL);
                        echo 'creating new Lead GUID '.$leadGUID.PHP_EOL;
                        $Lead->create();
                    } catch (AmoCRMApiException $e)
                    {
                        $log->error($e->getMessage());
                        printError($e);
                        die;
                    }
                }
                else
                {
                    try
                    {
                        $Lead->update($leadId);
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
        }
        else
        {
            $log->error('Не удалось открыть файл ' . $fileName);
        }
        try
        {
            $filesystem->rename($fileName, $pathToOldLeadsXml . $file);
        } catch (IOExceptionInterface $exception)
        {
            $log->error("An error occurred while renaming your file at " . $exception->getPath());
            echo "An error occurred while renaming your file at " . $exception->getPath();
        }
        $log->info('success'.PHP_EOL);
        echo 'success'.PHP_EOL;
    }
}
else
{
    $log->error('Нет ни одного XML файла из 1С в директории ' . $pathToLeadsXml);
    die('Нет ни одного XML файла из 1С в директории ' . $pathToLeadsXml);
}