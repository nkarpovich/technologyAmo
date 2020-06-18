<?php


namespace Karpovich\TechnoAmo;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\TagModel;
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
     * данные из XML в виде массива. Заполняются при создании объекта класса.
     * @var array
     */
    private $dataFromXml;

    /**
     * ID Контакта, которого следует привязать к сделке
     * @var int
     */
    private $contactId = 0;

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
        echo 'creating';
        $contactId = null;
        $responsibleUserId = null;
        $User = new User($this->apiClient);
        $Contact = new Contact($this->apiClient);

        //Создаем новый лид
        $LeadModel = new LeadModel();

        //Устанавливаем данные для лида
        $this->setLeadObjectData($LeadModel);

        //Устанавливаем теги для лида
        $TagsCollection = new TagsCollection();
        $TagModel = new TagModel();
        $TagModel->setName('Добавлено из 1С');
        $TagsCollection->add($TagModel);
        $TagsService = $this->apiClient->tags(EntityTypesInterface::LEADS);
        try {
            $TagsService->add($TagsCollection);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }
        //Добавляем подготовленный лид
        try
        {
            $leadsService = $this->apiClient->leads();
            $LeadModel = $leadsService->addOne($LeadModel);
        } catch (AmoCRMApiException $e)
        {
            printError($e);
            die;
        }
        
        //Привязываем контакт к сделке
        if ($contactId)
        {
            $this->attachContactToLead($LeadModel, $contactId);
        }
    }


    /**
     * Обновить лида на основе данных, полученных XML документа
     * @param int $leadId
     * @throws AmoCRMApiException
     */
    public function update(int $leadId)
    {
        echo 'updating '.$leadId;
        //Получим сделку
        try {
            $LeadModel = $this->apiClient->leads()->getOne($leadId);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        $this->setLeadObjectData($LeadModel);

        $TagsCollection = new TagsCollection();
        $TagModel = new TagModel();
        $TagModel->setName('Обновлено из 1С');
        $TagsCollection->add($TagModel);
        $TagsService = $this->apiClient->tags(EntityTypesInterface::LEADS);
        try {
            $TagsService->add($TagsCollection);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        //Обновляем подготовленный лид
        try
        {
            $this->apiClient->leads()->updateOne($LeadModel);;
        } catch (AmoCRMApiException $e)
        {
            printError($e);
            die;
        }

        //Привязываем контакт к сделке
        if ($this->contactId)
        {
            $this->attachContactToLead($LeadModel, $this->contactId);
        }
    }

    /**
     * Привязать контакт к лиду
     * @param LeadModel $LeadModel
     * @param int $contactId
     */
    public function attachContactToLead(\AmoCRM\Models\LeadModel $LeadModel,int $contactId):void {
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
            $this->apiClient->leads()->link($LeadModel, $links);
        } catch (AmoCRMApiException $e)
        {
            printError($e);
            die;
        }
    }

    /**
     * Наполнить лид данными
     * @param LeadModel $LeadModel
     * @throws AmoCRMApiException
     */
    public function setLeadObjectData(\AmoCRM\Models\LeadModel $LeadModel){
        $contactId = null;
        $responsibleUserId = null;
        $User = new User($this->apiClient);
        $Contact = new Contact($this->apiClient);


        //Находим id ответственного пользователя для нового контакта и лида. Пользователь - это сущность User.
        if ($this->dataFromXml['ПочтаМенеджера'])
        {
            $responsibleUserId = $User->getIdByLogin($this->dataFromXml['ПочтаМенеджера']);
        }

        //Устанавливаем ответственного
        if ($responsibleUserId)
            $LeadModel->setResponsibleUserId($responsibleUserId);

        $LeadModel->setUpdatedAt(time());

        //Находим id клиента по номеру телефона для нового лида. Клиент - это сущность Contact.
        if ($this->dataFromXml['Телефон'])
        {
            $contactId = $Contact->getIdByPhone($this->dataFromXml['Телефон']);
            if (!$contactId)
                $this->contactId = $Contact->create(
                    $this->dataFromXml['Телефон'],
                    $this->dataFromXml['Имя'],
                    $this->dataFromXml['ДеньРожденияКлиента'],
                    $this->dataFromXml['НомерПредъявленнойКартыЛояльности']
                );
        }

        //Устанавливаем имя лида
        $LeadModel->setName('Тестовая сделка - ' . $this->dataFromXml['GUID']);

        //Устанавливаем стоимость
        if ($this->dataFromXml['Бюджет'])
        {
            $price = preg_replace('/[^0-9]/', '', $this->dataFromXml['Бюджет']);
            $LeadModel->setPrice($price);
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
            $this->setCheckboxCustomField($leadCustomFieldsValues, self::PREPAYMENT__TEXT__FIELD_ID);
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
            $this->setCheckboxCustomField($leadCustomFieldsValues, self::GUID__TEXT__FIELD_ID);
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

        $LeadModel->setCustomFieldsValues($leadCustomFieldsValues);
    }
}