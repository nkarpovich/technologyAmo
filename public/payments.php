<?php
require_once __DIR__.'/../public/bootstrap.php';

use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Exceptions\AmoCRMApiException;
use Karpovich\TechnoAmo\ErrorPrinter;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Karpovich\Helper;
use Karpovich\TechnoAmo\Lead;
use Karpovich\TechnoAmo\Token;

$filesystem = new Filesystem();

// create a log channel
$log = new Logger('payments');
$log->pushHandler(new StreamHandler(__DIR__.'/../logs/payments.log', Logger::INFO));

Token::setAccessToken($apiClient, $clientAuth, $log, $pathToTokenFile);

//Получаем все файлы оплат, прилетевших из 1С
$log->info('Start '.date('d.m.Y H:i:s').PHP_EOL);
$arFiles = Helper::scanDir($pathToPaymentsXml);
if ($arFiles) {
    foreach ($arFiles as $file) {
        $fileName = $pathToPaymentsXml . $file;
        if (file_exists($fileName)) {
            $xml = simplexml_load_file($fileName);
            echo 'Processing file '.$fileName.PHP_EOL;
            foreach ($xml->children() as $xmlPaymentElement) {
                $Lead = new Lead($apiClient, $xmlPaymentElement);
                $leadId = Helper::xmlAttributeToString($xmlPaymentElement, 'IDAMO');
                $leadGUID = Helper::xmlAttributeToString($xmlPaymentElement, 'GUID');
                try {
                    if ($leadId) {
                        //Ищем лид по ID
                        $leadId = Helper::formatInt($leadId);
                        $lead = $apiClient->leads()->getOne($leadId);
                    } elseif ($leadGUID) {
                        //Ищем лид по GUID
                        $filter = new LeadsFilter();
                        $filter->setQuery($leadGUID);
                        $filter->setLimit(1);
                        try {
                            $leadsCollection = $apiClient->leads()->get($filter);
                        } catch (Exception $e) {
                            echo $e->getMessage();
                        }
                        if (isset($leadsCollection) && !$leadsCollection->isEmpty()) {
                            $lead = $leadsCollection->first();
                        }
                    } else {
                        $log->error('Не указаны обязательные параметры: ID сделки из АМО или GUID из 1С');
                        echo ('Не указаны обязательные параметры: ID сделки из АМО или GUID из 1С');
                        continue;
                    }

                    if ($lead) {
                        $leadId = $lead->getId();
                    }

                    if (!$leadId) {
                        $log->info('Лид не найден'.PHP_EOL);
                        echo ('Лид не найден'.PHP_EOL);
                        continue;
                    } else {
                        $log->info('updating Lead ID ' . $leadId . '...' . PHP_EOL);
                        echo('updating Lead ID ' . $leadId . '...' . PHP_EOL);
                        $Lead->updatePayment($leadId);
                    }
                } catch (AmoCRMApiException $e) {
                    $log->error($e->getMessage().PHP_EOL);
                    ErrorPrinter::printError($e);
                    continue;
                }
                $log->info('success' . PHP_EOL);
            }
        } else {
            $log->error('Не удалось открыть файл ' . $fileName.PHP_EOL);
            die('Не удалось открыть файл ' . $fileName.PHP_EOL);
        }
        try {
            $filesystem->rename($fileName, $pathToOldPaymentsXml . $file, true);
        } catch (IOExceptionInterface $exception) {
            $log->error("An error occurred while renaming your file at " . $exception->getPath().PHP_EOL);
            echo "An error occurred while renaming your file at " . $exception->getPath().PHP_EOL;
        }
    }
    $log->info('success'.PHP_EOL);
    echo 'success'.PHP_EOL;
} else {
    $log->error('Нет ни одного XML файла из 1С в директории ' . $pathToPaymentsXml.PHP_EOL);
    die('Нет ни одного XML файла из 1С в директории ' . $pathToPaymentsXml.PHP_EOL);
}
