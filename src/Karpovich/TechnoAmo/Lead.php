<?php


namespace Karpovich\TechnoAmo;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\TagModel;
use Karpovich\Helper;
use SimpleXMLElement;

class Lead extends BaseAmoEntity
{
    /**
     * ID полей дат платежей
     */
    const PAYMENT_DATES__DATE__FIELDS_ID = [
        1 => 501317,
        2 => 501323,
        3 => 501331,
        4 => 501345,
        5 => 551185,
        6 => 580943,
        7 => 580947,
        8 => 651719,
    ];
    /**
     * ID полей платежей
     */
    const PAYMENT__NUMERIC__FIELDS_ID = [
        1 => 489715,
        2 => 489717,
        3 => 489729,
        4 => 489787,
        5 => 551183,
        6 => 580941,
        7 => 580945,
        8 => 651717,
    ];

    /**
     * Статус предоплаты
     */
    const PREPAYMENT_STATUS = 22331134;

    /**
     * Название дома (серия дома)
     */
    const HOUSE_NAME__CHECKBOX__FIELD_ID = 267695;
    /**
     * Тип и размеры
     */
    //const TYPE_SIZE__CHECKBOX__FIELD_ID = 267787;
    /**
     * Комплектация
     */
    const KOMPLECT__CHECKBOX__FIELD_ID = 267813;
    /**
     * Форма оплаты
     */
    const PAYMENT_FORM__CHECKBOX__FIELD_ID = 268095;
    /**
     * Варианты оплаты
     */
//    const PAYMENT_VARIANT__CHECKBOX__FIELD_ID = 268455;
    /**
     * Источник
     */
    const RESOURCE__CHECKBOX__FIELD_ID = 520183;
    /**
     * Выставочная площадка
     */
    const REGION__CHECKBOX__FIELD_ID = 691100;
    /**
     * GUID
     */
    const GUID__TEXT__FIELD_ID = 690570;
    /**
     * Карта лояльности
     */
    const LOYAL_CARD__TEXT__FIELD_ID = 691096;
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
     * Тип - текст
     */
    const BUILDING_ADDRESS__TEXT__FIELD_ID = 691098;

    const FIELD_NAMES = [
        'ПочтаМенеджера', 'Телефон', 'Имя', 'ДеньРожденияКлиента', 'Бюджет', 'Предоплата',
        'ИсточникРекламы', 'ТипДома', 'Размер', 'Комплектация', 'ДатаНачалаМонтажа',
        'ДатаОкончанияМонтажа', 'АдресМонтажа', 'Эффективность', 'ФормаОплаты',
        'ВариантОплаты', 'ДатаИВремяВстречи', 'ДоговорКонтрагента', 'ОбъектСтроительства',
        'НомерВыданнойКартыЛояльности', 'НомерПредъявленнойКартыЛояльности', 'Регион',
        'Себестоимость', 'GUID', 'ИДАМО', 'DataPlatezha', 'Summa', 'IDAMO'
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

    public function __construct(AmoCRMApiClient $apiClient, SimpleXMLElement $xmlObject)
    {
        parent::__construct($apiClient);
        foreach (self::FIELD_NAMES as $FIELD_NAME) {
            $this->dataFromXml[$FIELD_NAME] = Helper::xmlAttributeToString($xmlObject, $FIELD_NAME);
        }
    }

    /**
     * Создать нового лида на основе данных, полученных XML документа
     * @throws AmoCRMApiException
     * @throws Exceptions\BaseAmoEntityException
     */
    public function create()
    {
        echo 'creating in process...' . PHP_EOL;
        $responsibleUserId = null;

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
            ErrorPrinter::printError($e);
        }
        //Добавляем подготовленный лид
        $leadsService = $this->apiClient->leads();
        $LeadModel = $leadsService->addOne($LeadModel);

        //Привязываем контакт к сделке
        if ($this->contactId) {
            $this->attachContactToLead($LeadModel, $this->contactId);
        }
    }


    /**
     * Обновить лида на основе данных, полученных XML документа
     * @param int $leadId
     * @throws AmoCRMApiException|Exceptions\BaseAmoEntityException
     */
    public function update(int $leadId)
    {
        echo 'updating ' . $leadId . PHP_EOL;
        //Получим сделку
        $LeadModel = $this->apiClient->leads()->getOne($leadId);

        $this->setLeadObjectData($LeadModel);

        $TagsCollection = new TagsCollection();
        $TagModel = new TagModel();
        $TagModel->setName('Обновлено из 1С');
        $TagsCollection->add($TagModel);
        $TagsService = $this->apiClient->tags(EntityTypesInterface::LEADS);
        $TagsService->add($TagsCollection);

        //Обновляем подготовленный лид
        $this->apiClient->leads()->updateOne($LeadModel);

        //Привязываем контакт к сделке
        if ($this->contactId) {
            $this->attachContactToLead($LeadModel, $this->contactId);
        }
    }

    /**
     * Наполнение лида данными по оплате
     * @param int $leadId
     * @throws AmoCRMApiException
     * @throws AmoCRMoAuthApiException
     */
    public function updatePayment(int $leadId)
    {
        //Получим сделку
        $LeadModel = $this->apiClient->leads()->getOne($leadId);

        $this->setLeadObjectDataPayment($LeadModel);

        //Обновляем подготовленный лид
        $this->apiClient->leads()->updateOne($LeadModel);
    }

    /**
     * Привязать контакт к лиду
     * @param LeadModel $LeadModel
     * @param int $contactId
     * @throws AmoCRMApiException
     * @throws AmoCRMoAuthApiException
     */
    public function attachContactToLead(LeadModel $LeadModel, int $contactId): void
    {
        $contact = $this->apiClient->contacts()->getOne($contactId);
        $links = new LinksCollection();
        $links->add($contact);
        $this->apiClient->leads()->link($LeadModel, $links);
    }

    /**
     * Наполнить лид данными
     * @param LeadModel $LeadModel
     * @throws Exceptions\BaseAmoEntityException
     */
    public function setLeadObjectData(LeadModel $LeadModel)
    {
        $responsibleUserId = null;
        $User = new User($this->apiClient);
        $Contact = new Contact($this->apiClient);


        //Находим id ответственного пользователя для нового контакта и лида. Пользователь - это сущность User.
        if ($this->dataFromXml['ПочтаМенеджера']) {
            $responsibleUserId = $User->getIdByLogin($this->dataFromXml['ПочтаМенеджера']);
        }

        //Устанавливаем ответственного
        if ($responsibleUserId) {
            $LeadModel->setResponsibleUserId($responsibleUserId);
        }

        $LeadModel->setUpdatedAt(time());

        //Находим id клиента по номеру телефона для нового лида. Клиент - это сущность Contact.
        if ($this->dataFromXml['Телефон']) {
            $this->contactId = $Contact->getIdByPhone($this->dataFromXml['Телефон']);
            if (!$this->contactId) {
                $this->contactId = $Contact->create(
                    $this->dataFromXml['Телефон'],
                    $this->dataFromXml['Имя'],
                    $this->dataFromXml['ДеньРожденияКлиента'],
                    $this->dataFromXml['НомерПредъявленнойКартыЛояльности']
                );
            }
        }

        //Устанавливаем имя лида
        $LeadModel->setName('Тестовая сделка - ' . $this->dataFromXml['GUID']);

        //Устанавливаем стоимость
        if ($this->dataFromXml['Бюджет']) {
            $price = preg_replace('/[^0-9]/', '', $this->dataFromXml['Бюджет']);
            $LeadModel->setPrice($price);
        }

        //Устанавливаем кастомные свойства лида
        $leadCustomFieldsValues = new CustomFieldsValuesCollection();

        if ($this->dataFromXml['ТипДома']) {
            $this->setMultiSelectCustomField(
                $leadCustomFieldsValues,
                self::HOUSE_NAME__CHECKBOX__FIELD_ID,
                $this->dataFromXml['ТипДома']
            );
        }
        if ($this->dataFromXml['Комплектация']) {
            $this->setMultiSelectCustomField(
                $leadCustomFieldsValues,
                self::KOMPLECT__CHECKBOX__FIELD_ID,
                $this->dataFromXml['Комплектация']
            );
        }
        if ($this->dataFromXml['ИсточникРекламы']) {
            $this->setMultiSelectCustomField(
                $leadCustomFieldsValues,
                self::RESOURCE__CHECKBOX__FIELD_ID,
                $this->dataFromXml['ИсточникРекламы']
            );
        }
        if ($this->dataFromXml['GUID']) {
            $this->setTextCustomField(
                $leadCustomFieldsValues,
                self::GUID__TEXT__FIELD_ID,
                $this->dataFromXml['GUID']
            );
        }
        if ($this->dataFromXml['Предоплата']) {
            $this->setCheckboxCustomField($leadCustomFieldsValues, self::PREPAYMENT__TEXT__FIELD_ID);
        }
        if ($this->dataFromXml['АдресМонтажа']) {
            $this->setTextCustomField(
                $leadCustomFieldsValues,
                self::BUILDING_ADDRESS__TEXT__FIELD_ID,
                $this->dataFromXml['GUID']
            );
        }
        if ($this->dataFromXml['Эффективность']) {
            $this->setTextCustomField(
                $leadCustomFieldsValues,
                self::EFFICIENCY__TEXT__FIELD_ID,
                $this->dataFromXml['Эффективность']
            );
        }
        if ($this->dataFromXml['ФормаОплаты']) {
            $this->setSelectCustomField(
                $leadCustomFieldsValues,
                self::PAYMENT_FORM__CHECKBOX__FIELD_ID,
                $this->dataFromXml['ФормаОплаты']
            );
        }
        /*if ($this->dataFromXml['ВариантОплаты']) {
            $this->setSelectCustomField(
                $leadCustomFieldsValues,
                self::PAYMENT_VARIANT__CHECKBOX__FIELD_ID,
                $this->dataFromXml['ВариантОплаты']
            );
        }*/
        /*if ($this->dataFromXml['ДоговорКонтрагента']) {
            $this->setCheckboxCustomField($leadCustomFieldsValues, self::);
        }*/
        if ($this->dataFromXml['НомерПредъявленнойКартыЛояльности']) {
            $this->setTextCustomField(
                $leadCustomFieldsValues,
                self::LOYAL_CARD__TEXT__FIELD_ID,
                $this->dataFromXml['НомерПредъявленнойКартыЛояльности']
            );
        }
        if ($this->dataFromXml['Регион']) {
            $this->setTextCustomField(
                $leadCustomFieldsValues,
                self::REGION__CHECKBOX__FIELD_ID,
                $this->dataFromXml['Регион']
            );
        }
        if ($this->dataFromXml['Себестоимость']) {
            $this->setTextCustomField(
                $leadCustomFieldsValues,
                self::INNER_PRICE__TEXT__FIELD_ID,
                $price = preg_replace('/[^0-9]/', '', $this->dataFromXml['Себестоимость'])
            );
        }
        if ($this->dataFromXml['ДатаИВремяВстречи']) {
            $dateStart = substr($this->dataFromXml['ДатаИВремяВстречи'], 0, 10);
            $datetime = explode(".", $dateStart);
            $date = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
            $this->setNumericCustomField($leadCustomFieldsValues, self::MEETING__DATE__FIELD_ID, $date);
        }
        if ($this->dataFromXml['ДатаНачалаМонтажа']) {
            $dateStart = substr($this->dataFromXml['ДатаНачалаМонтажа'], 0, 10);
            $datetime = explode(".", $dateStart);
            $date = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
            $this->setNumericCustomField($leadCustomFieldsValues, self::BUILDING_START__DATE__FIELD_ID, $date);
        }
        if ($this->dataFromXml['ДатаОкончанияМонтажа']) {
            $dateFinish = substr($this->dataFromXml['ДатаОкончанияМонтажа'], 0, 10);
            $datetime = explode(".", $dateFinish);
            $date = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
            $this->setNumericCustomField($leadCustomFieldsValues, self::BUILDING_END__DATE__FIELD_ID, $date);
        }

        $LeadModel->setCustomFieldsValues($leadCustomFieldsValues);
    }

    /**
     * Проверяем оплаты - находим последнюю свободную ячейку для оплаты. В нее записываем оплату.
     * Если оплат раньше не было - меняем статус у лида на 'Внесена предоплата'.
     * @param LeadModel $LeadModel
     */
    public function setLeadObjectDataPayment(LeadModel $LeadModel)
    {
        //Флаг, есть ли оплаты у лида
        $hasPayments = true;

        //Номер последней оплаты в АМО. Если он не пустой, то нужно записать следующую за ним оплату.
        $numOfLastPayment = null;

        //Получаем кастомные свойства лида
        $leadCustomFieldsValues = $LeadModel->getCustomFieldsValues();

        //Хак - в Amo URL хранятся с пробельными символами, если в таком же виде отправить назад - будет ошибка.
        //Заменяем пробелы на соответствующий символ %20
        $doc1Field = $leadCustomFieldsValues->getBy('fieldId', 513439);
        if ($doc1Field) {
            $doc1FieldValues = $doc1Field->getValues();
            $doc1FieldValue = $doc1FieldValues->first();
            if (!empty($doc1FieldValue->value)) {
                $doc1Field->setValues(
                    (new TextCustomFieldValueCollection())
                        ->add(
                            (new TextCustomFieldValueModel())
                                ->setValue(urlencode(' ', '%20', $doc1FieldValue->value))
                        )
                );
            }
        }

        //смотрим есть ли оплаты
        for ($i = 1; $i <= 8; $i++) {
            $amountFieldId = self::PAYMENT__NUMERIC__FIELDS_ID[$i];
            $dateFieldId = self::PAYMENT_DATES__DATE__FIELDS_ID[$i];
            $amountField = $leadCustomFieldsValues->getBy('fieldId', $amountFieldId);
            if (!empty($amountField)) {
                //Поле платежа уже заполнено у лида, переходим к следующему полю платежа
                continue;
            } else {
                //Если на первом шаге у лида не заполнен платеж - значит платежей еще не было
                if ($i === 1) {
                    $hasPayments = false;
                }

                //Первое найденное незаполненное поле платежа у лида. Заполняем значениями.
                if ($this->dataFromXml['Summa']) {
                    $numericCustomFieldValueModel = new NumericCustomFieldValuesModel();
                    $numericCustomFieldValueModel->setFieldId($amountFieldId);
                    $numericCustomFieldValueModel->setValues(
                        (new numericCustomFieldValueCollection())
                            ->add((new numericCustomFieldValueModel())->
                            setValue(preg_replace('/[^0-9]/', '', $this->dataFromXml['Summa'])))
                    );
                    $leadCustomFieldsValues->add($numericCustomFieldValueModel);
                }
                if ($this->dataFromXml['DataPlatezha']) {
                    $payDate = substr($this->dataFromXml['DataPlatezha'], 0, 10);
                    $datetime = explode(".", $payDate);
                    $date = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
                    $numericCustomFieldValueModel = new NumericCustomFieldValuesModel();
                    $numericCustomFieldValueModel->setFieldId($dateFieldId);
                    $numericCustomFieldValueModel->setValues(
                        (new numericCustomFieldValueCollection())
                            ->add((new numericCustomFieldValueModel())->setValue($date))
                    );
                    $leadCustomFieldsValues->add($numericCustomFieldValueModel);
                }
                break;
            }
        }
        if (!$hasPayments) {
            //Оплат еще не было - переводим в статус 'Внесена предоплата'
            $LeadModel->setStatusId(self::PREPAYMENT_STATUS);
        }

        //Установим время апдейта
        $LeadModel->setUpdatedAt(time());

        //Сохраняем кастомные свойства у лида
        $LeadModel->setCustomFieldsValues($leadCustomFieldsValues);
    }
}
