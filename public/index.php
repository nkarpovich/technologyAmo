<?php
require_once __DIR__.'/../public/bootstrap.php';

use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Exceptions\AmoCRMApiException;
use Karpovich\TechnoAmo\ErrorPrinter;
use Karpovich\TechnoAmo\Exceptions\BaseAmoEntityException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Karpovich\Helper;
use Karpovich\TechnoAmo\Lead;
use Karpovich\TechnoAmo\Token;

$filesystem = new Filesystem();

// create a log channel
$log = new Logger('leads');
$log->pushHandler(new StreamHandler(__DIR__.'/../logs/leads.log', Logger::INFO));

//Устанавливаем токен для доступа к API
Token::setAccessToken($apiClient, $clientAuth, $log, $pathToTokenFile);

$log->info('Start '.date('d.m.Y H:i:s').PHP_EOL);

//Получаем все файлы лидов, прилетевших из 1С
$arFiles = Helper::scanDir($pathToLeadsXml);
if ($arFiles) {
    foreach ($arFiles as $file) {
        $fileName = $pathToLeadsXml . $file;
        if (file_exists($fileName)) {
            $xml = simplexml_load_file($fileName);
            $Lead = new Lead($apiClient, $xml);
            $leadId = Helper::xmlAttributeToString($xml, 'ИДАМО');
            $leadGUID = Helper::xmlAttributeToString($xml, 'GUID');
            try {
                if ($leadId) {
                    $leadId = Helper::formatInt($leadId);
                } elseif ($leadGUID) {
                    //Ищем лид по GUID
                    $filter = new LeadsFilter();
                    $filter->setQuery($leadGUID);
                    $filter->setLimit(1);
                    try {
                        $leadsCollection = $apiClient->leads()->get($filter);
                        if (!$leadsCollection->isEmpty()) {
                            $lead = $leadsCollection->first();
                        }
                        $leadId = $lead->getId();
                    } catch (AmoCRMApiException $e) {
                        if ($e->getCode() != '204') {
                            $log->error($e->getMessage());
                            ErrorPrinter::printError($e);
                        } else {
                            $leadId = false;
                        }
                    }
                } else {
                    $log->error('Не указаны обязательные параметры: ID сделки из АМО или GUID из 1С');
                    die('Не указаны обязательные параметры: ID сделки из АМО или GUID из 1С');
                }


                if (!$leadId) {
                    //Лид не найден
                    try {
                        $log->info('creating new Lead GUID '.$leadGUID.PHP_EOL);
                        echo 'creating new Lead GUID '.$leadGUID.PHP_EOL;
                        try {
                            $Lead->create();
                            $log->info('creation completed'.PHP_EOL);
                            echo 'creation completed' . PHP_EOL;
                        } catch (BaseAmoEntityException $e) {
                            $log->error($e->getMessage());
                            echo $e->getMessage();
                            continue;
                        }
                    } catch (AmoCRMApiException $e) {
                        $log->error($e->getMessage());
                        ErrorPrinter::printError($e);
                        continue;
                    }
                } else {
                    //Лид найден
                    $log->info('updating Lead ID '.$leadId.PHP_EOL);
                    echo 'updating Lead ID '.$leadId.PHP_EOL;
                    try {
                        $Lead->update($leadId);
                    } catch (AmoCRMApiException $e) {
                        $log->error($e->getMessage());
                        ErrorPrinter::printError($e);
                        continue;
                    } catch (BaseAmoEntityException $e) {
                        echo $e->getMessage();
                        continue;
                    }
                    $log->info('update completed'.PHP_EOL);
                    echo 'update completed'.PHP_EOL;
                }
            } catch (AmoCRMApiException $e) {
                $log->error($e->getMessage());
                ErrorPrinter::printError($e);
                continue;
            }
        } else {
            $log->error('Не удалось открыть файл ' . $fileName);
        }
        try {
            $filesystem->rename($fileName, $pathToOldLeadsXml . $file, true);
        } catch (IOExceptionInterface $exception) {
            $log->error("An error occurred while renaming your file at " . $exception->getPath());
            echo "An error occurred while renaming your file at " . $exception->getPath();
        }
        $log->info('success'.PHP_EOL);
        echo 'success'.PHP_EOL;
    }
} else {
    $log->error('Нет ни одного XML файла из 1С в директории ' . $pathToLeadsXml);
    die('Нет ни одного XML файла из 1С в директории ' . $pathToLeadsXml);
}
