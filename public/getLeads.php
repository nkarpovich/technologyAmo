<?php
require_once 'bootstrap.php';

use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\LeadModel;
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
$log->pushHandler(new StreamHandler(__DIR__ . '/../logs/getLeadsFromAmo.log', Logger::INFO));

Token::setAccessToken($apiClient, $clientAuth, $log);

$log->info('Start ' . date('d.m.Y H:i:s') . PHP_EOL);

try
{
    $filter = new LeadsFilter();
    $xml = new SimpleXMLElement('<xml/>');
    for ($i = 7; $i < 1000; $i++)
    {
        //Максимум - 250
        $filter->setLimit(250);
        $filter->setPage($i);
        $leadsCollection = $apiClient->leads()->get($filter, [LeadModel::CONTACTS]);
        if (!$leadsCollection->getNextPageLink())
            break;
        if (!$leadsCollection->isEmpty())
        {
            $leadsIterator = $leadsCollection->getIterator();
            //Перебираем сделки
            while ($leadsIterator->valid())
            {
                $curLeadModel = $leadsIterator->current();

                $leadXml = $xml->addChild('lead');
                $leadXml->addChild('id', $curLeadModel->id);

                $leadCustomFieldsValues = $curLeadModel->getCustomFieldsValues();
                if ($leadCustomFieldsValues)
                {
                    $roistatField = $leadCustomFieldsValues->getBy('fieldId', 654205);
                    if ($roistatField)
                    {
                        $roistatFieldValues = $roistatField->getValues();
                        $roistatFieldValue = $roistatField->getValues()->first()->value;
                        $leadXml->addChild('roistat', $roistatFieldValue);
                    }
                }
                $leadContacts = $curLeadModel->getContacts();
                $contactCustomFieldValues = null;
                if ($leadContacts)
                {
                    $leadMainContact = $leadContacts->getBy('isMain', true);
                    $res = $leadMainContact->id;
                    $contact = $apiClient->contacts()->getOne($res);
                    $customFields = $contact->getCustomFieldsValues();
                    if($customFields)
                    {
                        //Получим значение поля по его коду
                        $phoneField = $customFields->getBy('fieldCode', 'PHONE');
                        if ($phoneField)
                        {
                            $phone = $phoneField->getValues()->first()->value;
                            $leadXml->addChild('phone', $phone);
//                            \Symfony\Component\VarDumper\VarDumper::dump($phone);
                        }
                    }
                    $name = $contact->getName();
                    if ($name)
                    {
                        $leadXml->addChild('name', $name);
                    }

                }
//                echo $leadXml->asXML();
                $leadsIterator->next();
            }
        }
    }
    $xml->saveXML($pathToExportedLeadsFile);
} catch (AmoCRMApiException $e)
{
    if ($e->getCode() != '204')
    {
        $log->error($e->getMessage());
        printError($e);
        die;
    }
    else
    {
        $leadId = false;
    }
}

$log->info('success' . PHP_EOL);
echo 'success' . PHP_EOL;