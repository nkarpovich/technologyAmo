<?php


namespace Karpovich\TechnoAmo;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\LeadModel;
use Karpovich\Helper;


class Lead extends BaseAmoEntity
{
    /**
     * Название дома (серия дома)
     */
    const HOUSE_NAME__CHECKBOX__FIELD_ID = 267695;
    /**
     * Тип и размеры
     */
    const TYPE_SIZE__CHECKBOX__FIELD_ID = 267787;
    /**
     * Комплектация
     */
    const KOMPLECT__CHECKBOX__FIELD_ID = 267813;
    /**
     * Форма оплаты
     */
    const PAYMENT_FORM__CHECKBOX__FIELD_ID = 267813;
    /**
     * Варианты оплаты
     */
    const PAYMENT_VARIANT__CHECKBOX__FIELD_ID = 268455;
    /**
     * Источник
     */
    const RESOURCE__CHECKBOX__FIELD_ID = 520183;
    /**
     * GUID
     */
    const GUID__TEXT__FIELD_ID = 690570;
    /**
     * Себетоимость
     */
    const INNER_PRICE__TEXT__FIELD_ID = 690772;
    /**
     * Предоплата
     */
    const PREPAYMENT__TEXT__FIELD_ID = 690772;
    /**
     * Эффективность
     */
    const EFFICIENCY__TEXT__FIELD_ID = 690774;
    /**
     * Дата встречи
     */
    const MEETING__DATE__FIELD_ID = 298577;
    /**
     * Начало строительства
     */
    const BUILDING_START__DATE__FIELD_ID = 267815;
    /**
     * Окончание строительства
     */
    const BUILDING_END__DATE__FIELD_ID = 267827;
    /**
     * Адрес строительства (монтажа)
     */
    const BUILDING_ADDRESS__ADDRESS__FIELD_ID = 299749;
    /**
     * Теги
     */
    const TAGS = ['из 1С'];

    const FIELD_NAMES = [
        'ПочтаМенеджера', 'Телефон', 'Имя', 'ДеньРожденияКлиента', 'Бюджет', 'Предоплата',
        'ИсточникРекламы', 'ТипДома', 'Размер', 'Комплектация', 'ДатаНачалаМонтажа',
        'ДатаОкончанияМонтажа', 'АдресМонтажа', 'Эффективность', 'ФормаОплаты',
        'ВариантОплаты', 'ДатаИВремяВстречи', 'ДоговорКонтрагента', 'ОбъектСтроительства',
        'НомерВыданнойКартыЛояльности', 'НомерПредъявленнойКартыЛояльности', 'Регион',
        'Себестоимость', 'GUID', 'ИДАМО'
    ];

    /**
     * данные из XML в виде массива
     * @var array
     */
    private $dataFromXml;

    public function __construct(AmoCRMApiClient $apiClient, \SimpleXMLElement $xmlObject)
    {
        parent::__construct($apiClient);
        //TODO: проверять наличие аттрибута в XML-ке
        foreach (self::FIELD_NAMES as $FIELD_NAME)
        {
            $this->dataFromXml[$FIELD_NAME] = Helper::xmlAttributeToString($xmlObject, $FIELD_NAME);
        }
    }

    /**
     * Создать нового лида на основе данных, полученных XML документа
     * @throws AmoCRMApiException
     */
    public function create()
    {
        $contactId = null;
        $responsibleUserId = null;
        $User = new User($this->apiClient);
        $Contact = new Contact($this->apiClient);

        //Создаем новый лид
        $newLead = new LeadModel();

        //Находим id ответственного пользователя для нового контакта и лида. Пользователь - это сущность User.
        if ($this->dataFromXml['ПочтаМенеджера'])
        {
            $responsibleUserId = $User->getIdByLogin($this->dataFromXml['ПочтаМенеджера']);
        }

        //Устанавливаем ответственного
        if ($responsibleUserId)
            $newLead->setResponsibleUserId($responsibleUserId);

        //Находим id клиента по номеру телефона для нового лида. Клиент - это сущность Contact.
        if ($this->dataFromXml['Телефон'])
        {
            $contactId = $Contact->getIdByPhone($this->dataFromXml['Телефон']);
            if (!$contactId)
                $contactId = $Contact->create(
                    $this->dataFromXml['Телефон'],
                    $this->dataFromXml['Имя'],
                    $this->dataFromXml['ДеньРожденияКлиента'],
                    $this->dataFromXml['НомерПредъявленнойКартыЛояльности']
                );
        }

        //Устанавливаем имя лида
        $newLead->setName('Тестовая сделка - ' . $this->dataFromXml['GUID']);

        //Устанавливаем стоимость
        if ($this->dataFromXml['Бюджет'])
        {
            $price = preg_replace('/[^0-9]/', '', $this->dataFromXml['Бюджет']);
            $newLead->setPrice($price);
        }

        //Устанавливаем кастомные свойства лида
        $leadCustomFieldsValues = new CustomFieldsValuesCollection();

        if($this->dataFromXml['ТипДома'])
        {
            $this->setMultiSelectCustomField($leadCustomFieldsValues, self::HOUSE_NAME__CHECKBOX__FIELD_ID, $this->dataFromXml['ТипДома']);
        }
        if($this->dataFromXml['Комплектация'])
        {
            $this->setMultiSelectCustomField($leadCustomFieldsValues, self::KOMPLECT__CHECKBOX__FIELD_ID, $this->dataFromXml['Комплектация']);
        }
        if($this->dataFromXml['ИсточникРекламы'])
        {
            $this->setMultiSelectCustomField($leadCustomFieldsValues, self::RESOURCE__CHECKBOX__FIELD_ID, $this->dataFromXml['ИсточникРекламы']);
        }
        if($this->dataFromXml['GUID'])
        {
            $this->setTextCustomField($leadCustomFieldsValues, self::GUID__TEXT__FIELD_ID, $this->dataFromXml['GUID']);
        }
        if($this->dataFromXml['Предоплата'])
        {
            $this->setCheckboxCustomField($leadCustomFieldsValues, self::PREPAYMENT__TEXT__FIELD_ID, true);
        }
       /* if($this->dataFromXml['АдресМонтажа'])
        {
            $this->setTextCustomField($leadCustomFieldsValues, self::BUILDING_ADDRESS__ADDRESS__FIELD_ID, $this->dataFromXml['GUID']);
        }*/
        if($this->dataFromXml['Эффективность'])
        {
            $this->setTextCustomField($leadCustomFieldsValues, self::EFFICIENCY__TEXT__FIELD_ID, $this->dataFromXml['Эффективность']);
        }
        if($this->dataFromXml['ФормаОплаты'])
        {
            $this->setSelectCustomField($leadCustomFieldsValues, self::PAYMENT_FORM__CHECKBOX__FIELD_ID, $this->dataFromXml['ФормаОплаты']);
        }
        if($this->dataFromXml['ВариантОплаты'])
        {
            $this->setTextCustomField($leadCustomFieldsValues, self::PAYMENT_VARIANT__CHECKBOX__FIELD_ID, $this->dataFromXml['GUID']);
        }
        if($this->dataFromXml['ДоговорКонтрагента'])
        {
            $this->setCheckboxCustomField($leadCustomFieldsValues, self::GUID__TEXT__FIELD_ID, true);
        }
        /*if($this->dataFromXml['НомерПредъявленнойКартыЛояльности'])
        {
            $this->setTextCustomField($leadCustomFieldsValues, self::GUID__TEXT__FIELD_ID, $this->dataFromXml['GUID']);
        }*/
        /*if($this->dataFromXml['Регион'])
        {
            $this->setTextCustomField($leadCustomFieldsValues, self::GUID__TEXT__FIELD_ID, $this->dataFromXml['GUID']);
        }*/
        if($this->dataFromXml['Себестоимость'])
        {
            $this->setTextCustomField($leadCustomFieldsValues, self::INNER_PRICE__TEXT__FIELD_ID, $price = preg_replace('/[^0-9]/', '', $this->dataFromXml['Себестоимость']));
        }
        if($this->dataFromXml['ДатаИВремяВстречи'])
        {
            $dateStart = substr($this->dataFromXml['ДатаИВремяВстречи'],0,10);
            $datetime = explode(".", $dateStart);
            $date = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
            $this->setNumericCustomField($leadCustomFieldsValues, self::MEETING__DATE__FIELD_ID, $date);
        }
        if($this->dataFromXml['ДатаНачалаМонтажа'])
        {
            $dateStart = substr($this->dataFromXml['ДатаНачалаМонтажа'],0,10);
            $datetime = explode(".", $dateStart);
            $date = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
            $this->setNumericCustomField($leadCustomFieldsValues, self::BUILDING_START__DATE__FIELD_ID, $date);
        }
        if($this->dataFromXml['ДатаОкончанияМонтажа'])
        {
            $dateFinish = substr($this->dataFromXml['ДатаОкончанияМонтажа'],0,10);
            $datetime = explode(".", $dateFinish);
            $date = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
            $this->setNumericCustomField($leadCustomFieldsValues, self::BUILDING_END__DATE__FIELD_ID, $date);
        }

        $newLead->setCustomFieldsValues($leadCustomFieldsValues);

        //Добавляем подготовленный лид
        try
        {
            $leadsService = $this->apiClient->leads();
            $newLead = $leadsService->addOne($newLead);
        } catch (AmoCRMApiException $e)
        {
            printError($e);
            die;
        }

        //Привязываем контакт к сделке
        if ($contactId)
        {
            try
            {
                $contact = $this->apiClient->contacts()->getOne($contactId);
            } catch (AmoCRMApiException $e)
            {
                printError($e);
                die;
            }

            $links = new LinksCollection();
            $links->add($contact);
            try
            {
                $this->apiClient->leads()->link($newLead, $links);
            } catch (AmoCRMApiException $e)
            {
                printError($e);
                die;
            }
        }
    }


    /**
     * Обновить лида на основе данных, полученных XML документа
     */
    public function update()
    {
        die();
        $leadsService = $this->apiClient->leads();
        $this->leadsService->update($lead);
    }

}