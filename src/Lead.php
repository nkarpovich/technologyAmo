<?php


namespace TechnoAmo;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultiselectCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\DateCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\MultiSelectCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\DateCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultiSelectCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\DateCustomFieldValueCollection;
use AmoCRM\Models\LeadModel;
use Symfony\Component\VarDumper\VarDumper;


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
     * Источник
     */
    const RESOURCE__CHECKBOX__FIELD_ID = 520183;
    /**
     * GUID
     */
    const GUID__TEXT__FIELD_ID = 690570;
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
                $contactId = $Contact->create($this->dataFromXml['Телефон'], $this->dataFromXml['Имя'], $this->dataFromXml['ДеньРожденияКлиента']);
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
        $this->setMultiSelectCustomField($leadCustomFieldsValues, self::HOUSE_NAME__CHECKBOX__FIELD_ID,'',511229);
        $this->setMultiSelectCustomField($leadCustomFieldsValues, self::KOMPLECT__CHECKBOX__FIELD_ID,'Зима рынок [3]');
        if($this->dataFromXml['ИсточникРекламы'])
        {
            $this->setMultiSelectCustomField($leadCustomFieldsValues, self::RESOURCE__CHECKBOX__FIELD_ID, $this->dataFromXml['ИсточникРекламы']);
        }
        if($this->dataFromXml['GUID'])
        {
            $this->setTextCustomField($leadCustomFieldsValues, self::GUID__TEXT__FIELD_ID, $this->dataFromXml['GUID']);
        }
        if($this->dataFromXml['ДатаНачалаМонтажа'])
        {
            $dateStart = substr($this->dataFromXml['ДатаНачалаМонтажа'],0,10);
            $datetime = explode(".", $dateStart);
            $dateS = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
            $this->setNumericCustomField($leadCustomFieldsValues, self::BUILDING_START__DATE__FIELD_ID, $dateS);
        }
        if($this->dataFromXml['ДатаОкончанияМонтажа'])
        {
            $dateFinish = substr($this->dataFromXml['ДатаОкончанияМонтажа'],0,10);
            $datetime = explode(".", $dateFinish);
            $dateF = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
            $this->setNumericCustomField($leadCustomFieldsValues, self::BUILDING_END__DATE__FIELD_ID, $dateF);
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

    /**
     * Добавляет значение мультиселекта в коллекцию CustomFieldsValuesCollection
     * @param CustomFieldsValuesCollection $customFieldsValuesCollection
     * @param int $fieldId
     * @param string $value
     * @param int|null $enumId
     * @return bool
     */
    public function setMultiSelectCustomField(\AmoCRM\Collections\CustomFieldsValuesCollection $customFieldsValuesCollection, int $fieldId, string $value='', int $enumId=null): bool
    {
        $multiSelectCustomFieldValuesModel = new MultiSelectCustomFieldValuesModel();
        $multiSelectCustomFieldValuesModel->setFieldId($fieldId);
        if($enumId)
        {
            $multiSelectCustomFieldValuesModel->setValues(
                (new MultiSelectCustomFieldValueCollection())
                    ->add((new MultiSelectCustomFieldValueModel())->setEnumId($enumId))
            );
        }elseif($value){
            $multiSelectCustomFieldValuesModel->setValues(
                (new MultiSelectCustomFieldValueCollection())
                    ->add((new MultiSelectCustomFieldValueModel())->setValue($value))
            );
        }else{
            return false;
        }
        $customFieldsValuesCollection->add($multiSelectCustomFieldValuesModel);
        return true;
    }

    /**
     * Добавляет текстовое значение в коллекцию CustomFieldsValuesCollection
     * @param CustomFieldsValuesCollection $customFieldsValuesCollection
     * @param int $fieldId
     * @param string $value
     * @return void
     */
    public function setTextCustomField(\AmoCRM\Collections\CustomFieldsValuesCollection $customFieldsValuesCollection, int $fieldId, string $value): void
    {
        $textCustomFieldValueModel = new TextCustomFieldValuesModel();
        $textCustomFieldValueModel->setFieldId($fieldId);
        $textCustomFieldValueModel->setValues(
            (new TextCustomFieldValueCollection())
                ->add((new TextCustomFieldValueModel())->setValue($value))
        );
        $customFieldsValuesCollection->add($textCustomFieldValueModel);
    }

    public function setNumericCustomField(\AmoCRM\Collections\CustomFieldsValuesCollection $customFieldsValuesCollection, int $fieldId, int $value): void
    {
        $numericCustomFieldValueModel = new NumericCustomFieldValuesModel();
        $numericCustomFieldValueModel->setFieldId($fieldId);
        $numericCustomFieldValueModel->setValues(
            (new NumericCustomFieldValueCollection())
                ->add((new NumericCustomFieldValueModel())->setValue($value))
        );
        $customFieldsValuesCollection->add($numericCustomFieldValueModel);
    }

    /**
     * Добавляет дату в коллекцию CustomFieldsValuesCollection
     * !!!На 17.06.2020 НЕ работает, AMO ожидает на вход int, а в этом методе параметр приводится к строке вида 'YYY-MM-DD'
     * @param CustomFieldsValuesCollection $customFieldsValuesCollection
     * @param int $fieldId
     * @param string $value
     */
    public function setDateCustomField(\AmoCRM\Collections\CustomFieldsValuesCollection $customFieldsValuesCollection, int $fieldId, string $value): void
    {
        $dateCustomFieldValuesModel = new DateCustomFieldValuesModel();
        $dateCustomFieldValuesModel->setFieldId($fieldId);
        $dateCustomFieldValuesModel->setValues(
            (new DateCustomFieldValueCollection())
                ->add((new DateCustomFieldValueModel())->setValue(time()))
        );
        $customFieldsValuesCollection->add($dateCustomFieldValuesModel);
    }

}