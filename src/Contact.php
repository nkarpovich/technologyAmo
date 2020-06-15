<?php


namespace TechnoAmo;

use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Models\ContactModel;

class Contact extends BaseAmoEntity
{
    const PHONE__FIELD_ID = 91611;
    const BIRTH_DATE__FIELD_ID = 651317;

    public function __construct(\AmoCRM\Client\AmoCRMApiClient $apiClient)
    {
        parent::__construct($apiClient);
    }

    /**
     * Получить id контакта по его телефону
     * @param string $phone
     * @return bool|mixed
     * @throws \AmoCRM\Exceptions\AmoCRMApiException
     */
    public function getIdByPhone(string $phone)
    {
        $filter = new ContactsFilter();
        $filter->setCustomFieldsValues([91611 => $phone]);
        //Получим сделки по фильтру
        try
        {
            $contacts = $this->apiClient->contacts()->get($filter);
        } catch (AmoCRMApiException $e)
        {
            printError($e);
            die;
        }
        if (!$contacts->isEmpty())
        {
            $Contact = $contacts->first()->toArray();
            return $Contact['id'];
        }
        return false;
    }

    /**
     * Создать контакт
     * @param $phone
     * @param $name
     * @param $birthDate
     * @return int|null
     * @throws \AmoCRM\Exceptions\AmoCRMApiException
     * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
     */
    public function create(string $phone, string $name='', string $birthDate='')
    {
        $contact = new ContactModel();
        $contact->setName($name);
        $contact->setCustomFieldsValues([self::PHONE__FIELD_ID=>$phone,self::BIRTH_DATE__FIELD_ID=>$birthDate]);
        try
        {
            $contactModel = $this->apiClient->contacts()->addOne($contact);
        } catch (AmoCRMApiException $e)
        {
            printError($e);
            die;
        }
        return $contactModel->getId();
    }
}