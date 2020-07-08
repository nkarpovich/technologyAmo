<?php


namespace Karpovich\TechnoAmo;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Models\ContactModel;

class Contact extends BaseAmoEntity
{
    const PHONE__FIELD_ID = 91611;
    const BIRTH_DATE__FIELD_ID = 651317;
    const CARD__FIELD_ID = 677404;

    public function __construct(AmoCRMApiClient $apiClient)
    {
        parent::__construct($apiClient);
    }

    /**
     * Получить id контакта по его телефону
     * @param string $phone
     * @return bool|mixed
     */
    public function getIdByPhone(string $phone)
    {
        $filter = new ContactsFilter();
        $phone = preg_replace('/[^0-9]/im', '', $phone);
//        $filter->setCustomFieldsValues([91611 => $phone]);
        $filter->setQuery($phone);
        $filter->setLimit(1);
        //Получим сделки по фильтру
        try {
            $contacts = $this->apiClient->contacts()->get($filter);
        } catch (AmoCRMApiException $e) {
//            ErrorPrinter::printError($e);
            if ($e->getCode() == 204) {
                return false;
            }
        }
        if (!$contacts->isEmpty()) {
            $Contact = $contacts->first()->toArray();
            return $Contact['id'];

        }
        return false;
    }

    /**
     * Создать контакт
     * @param string $phone
     * @param string $name
     * @param string $birthDate
     * @param string $card
     * @return int|null id созданного контакта|null
     */
    public function create(string $phone, string $name = 'default name', string $birthDate = '', string $card = ''):
    ?int
    {
        $contact = new ContactModel();

        //Имя контакта
        $contact->setName($name);

        //Если передали дефолтное время из XML - сбрасываем его
        if ('01.01.0001 0:00:00' === $birthDate) {
            $birthDate = '';
        }

        //Устанавливаем кастомные свойства контакта
        $contactCustomFieldsValues = new CustomFieldsValuesCollection();
        if ($phone) {
            $this->setTextCustomField($contactCustomFieldsValues, self::PHONE__FIELD_ID, $phone);
        }
        if ($birthDate) {
            $d = substr($birthDate, 0, 10);
            $datetime = explode(".", $d);
            $date = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
            $this->setNumericCustomField($contactCustomFieldsValues, self::CARD__FIELD_ID, $date);
        }
        if ($card) {
            $this->setTextCustomField($contactCustomFieldsValues, self::BIRTH_DATE__FIELD_ID, $card);
        }
        $contact->setCustomFieldsValues($contactCustomFieldsValues);

        try {
            $contactModel = $this->apiClient->contacts()->addOne($contact);
        } catch (AmoCRMApiException $e) {
            ErrorPrinter::printError($e);
            die;
        }
        return $contactModel->getId();
    }
}
