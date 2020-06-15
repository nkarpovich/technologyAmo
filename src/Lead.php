<?php


namespace TechnoAmo;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultiselectCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\MultiSelectCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultiSelectCustomFieldValueCollection;
use AmoCRM\Models\LeadModel;


class Lead extends BaseAmoEntity
{
    /**
     * Название дома
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
     * Источник
     */
    const RESOURCE__CHECKBOX__FIELD_ID = 520183;
    /**
     * Начало строительства
     */
    const BUILDING_START__DATE__FIELD_ID = 267815;
    /**
     * Окончание строительства
     */
    const BUILDING_END__DATE__FIELD_ID = 267827;
    /**
     * Теги
     */
    const TAGS = ['из 1С'];

    const FIELD_NAMES = [
        'ПочтаМенеджера', 'Телефон', 'Имя', 'ДеньРожденияКлиента', 'Бюджет' , 'Предоплата',
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
        foreach (self::FIELD_NAMES as $FIELD_NAME){
            $this->dataFromXml[$FIELD_NAME] = Helper::xmlAttributeToString($xmlObject, $FIELD_NAME);
        }
    }

    /**
     * Создать нового лида на основе данных, полученных XML документа
     * @throws AmoCRMApiException
     */
    public function create()
    {
        $User = new User($this->apiClient);
        $Contact = new Contact($this->apiClient);

        //Находим id ответственного менеджера для нового контакта и лида. Менеджер - это сущность User.
        if ($this->dataFromXml['ПочтаМенеджера'])
        {
            $managerId = $User->getIdByLogin($this->dataFromXml['ПочтаМенеджера']);
        }

        //Находим id клиента по для нового лида. Клиент - это сущность Contact.
        if ($this->dataFromXml['Телефон'])
        {
            $contactId = $Contact->getIdByPhone($this->dataFromXml['Телефон']);
            if (!$contactId)
                $contactId = $Contact->create($this->dataFromXml['Телефон'], $this->dataFromXml['Имя'], $this->dataFromXml['ДеньРожденияКлиента']);
        }

        $newLead = new LeadModel();
        $newLead->setName('Тестовая сделка - '.$this->dataFromXml['GUID']);
        $leadCustomFieldsValues = new CustomFieldsValuesCollection();
        $multiSelectCustomFieldValuesModel = new MultiSelectCustomFieldValuesModel();
        $multiSelectCustomFieldValuesModel->setFieldId(self::HOUSE_NAME__CHECKBOX__FIELD_ID);
        $multiSelectCustomFieldValuesModel->setValues(
            (new MultiSelectCustomFieldValueCollection())
                ->add((new MultiSelectCustomFieldValueModel())->setEnumId(511229))
        );
        $leadCustomFieldsValues->add($multiSelectCustomFieldValuesModel);
        $newLead->setCustomFieldsValues($leadCustomFieldsValues);
        try
        {
            $leadsService = $this->apiClient->leads();
            $newLead = $leadsService->addOne($newLead);
        } catch (AmoCRMApiException $e)
        {
            throw new AmoCRMApiException($e);
        }
        //Получим контакт по ID, сделку и привяжем контакт к сделке
        /*try
        {
            $contact = $this->apiClient->contacts()->getOne(7143559);
        } catch (AmoCRMApiException $e)
        {
            printError($e);
            die;
        }

        $links = new LinksCollection();
        $links->add($contact);
        try
        {
            $this->apiClient->leads()->link($lead, $links);
        } catch (AmoCRMApiException $e)
        {
            printError($e);
            die;
        }*/
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