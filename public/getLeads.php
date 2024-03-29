<?php
require_once __DIR__.'/../public/bootstrap.php';

use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\LeadModel;
use Karpovich\TechnoAmo\ErrorPrinter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Filesystem\Filesystem;
use Karpovich\TechnoAmo\Token;
use Symfony\Component\VarDumper\VarDumper;

$filesystem = new Filesystem();

$log = new Logger('leads');
$log->pushHandler(new StreamHandler(__DIR__ . '/../logs/getLeadsFromAmo.log', Logger::INFO));

//Устанавливаем токен для доступа к API
Token::setAccessToken($apiClient, $clientAuth, $log, $pathToTokenFile);

$log->info('Start ' . date('d.m.Y H:i:s') . PHP_EOL);

try {
    $filter = new LeadsFilter();
    $xml = new SimpleXMLElement('<xml/>');
    for ($i = 1; $i < 1000; $i++) {
        if ($i===8) {
            continue;
        }
        /*if ($i===30) {
            break;
        }*/
        //Максимум - 250
        $filter->setLimit(250);
        $filter->setPage($i);
        $leadsCollection = $apiClient->leads()->get($filter, [LeadModel::CONTACTS]);
        if (!$leadsCollection->getNextPageLink()) {
            break;
        }
        if (!$leadsCollection->isEmpty()) {
            $leadsIterator = $leadsCollection->getIterator();
            //Перебираем сделки
            while ($leadsIterator->valid()) {
                $curLeadModel = $leadsIterator->current();

                $leadXml = $xml->addChild('lead');
                $leadXml->addChild('id', $curLeadModel->id);

                $leadCustomFieldsValues = $curLeadModel->getCustomFieldsValues();
                if ($leadCustomFieldsValues) {
                    $roistatField = $leadCustomFieldsValues->getBy('fieldId', 654205);
                    if ($roistatField) {
                        $roistatFieldValues = $roistatField->getValues();
                        $roistatFieldValue = $roistatField->getValues()->first()->value;
                        $leadXml->addChild('roistat', $roistatFieldValue);
                    }
                }
                $leadContacts = $curLeadModel->getContacts();
                $contactCustomFieldValues = null;
                if ($leadContacts) {
                    $leadMainContact = $leadContacts->getBy('isMain', true);
                    $res = $leadMainContact->id;
                    $contact = $apiClient->contacts()->getOne($res);
                    $customFields = $contact->getCustomFieldsValues();
                    if ($customFields) {
                        //Получим значение поля по его коду
                        $phoneField = $customFields->getBy('fieldCode', 'PHONE');

                        if ($phoneField) {
                            $phone = $phoneField->getValues()->first()->value;
                            $leadXml->addChild('phone', $phone);
                        }
                    }
                    $name = $contact->getName();
                    if ($name) {
                        $leadXml->addChild('name', $name);
                    }
                }
                sleep(0.5);
                $leadsIterator->next();
            }
        }
    }
    $xml->saveXML($pathToExportedLeadsFile);
} catch (AmoCRMApiException $e) {
    if ($e->getCode() != '204') {
        $log->error($e->getMessage());
        ErrorPrinter::printError($e);
        die;
    } else {
        $leadId = false;
    }
}

$log->info('success' . PHP_EOL);
echo 'success' . PHP_EOL;
