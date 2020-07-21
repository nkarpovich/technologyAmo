<?php


namespace Karpovich\TechnoAmo;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\UrlCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\UrlCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\TagModel;
use Karpovich\Helper;
use SimpleXMLElement;
use Symfony\Component\VarDumper\VarDumper;

class Lead extends BaseAmoEntity
{
    /**
     * Поля ссылок на КП
     */
    const AMO_CF_DOCUMENTS = [513439, 556683, 556689, 556691, 556697];

    /**
     * ID полей дат платежей
     */
    /*const PAYMENT_DATES__DATE__FIELDS_ID = [
        1 => 501317,
        2 => 501323,
        3 => 501331,
        4 => 501345,
        5 => 551185,
        6 => 580943,
        7 => 580947,
        8 => 651719,
    ];*/
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
    const PREPAYMENT__TEXT__FIELD_ID = 691618;
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
        echo 'creation started...' . PHP_EOL;
        $responsibleUserId = null;

        //Создаем новый лид
        $leadModel = new LeadModel();

        //Устанавливаем данные для лида
        $this->setLeadObjectData($leadModel);

        //Устанавливаем теги для лида
        $tagsCollection = new TagsCollection();
        $tagModel = new TagModel();
        $tagModel->setName('Добавлено из 1С');
        $tagsCollection->add($tagModel);
        $tagsService = $this->apiClient->tags(EntityTypesInterface::LEADS);
        try {
            $tagsService->add($tagsCollection);
        } catch (AmoCRMApiException $e) {
            ErrorPrinter::printError($e);
        }

        //Добавляем теги
        $leadModel->setTags($tagsCollection);

        //Добавляем подготовленный лид
        $leadsService = $this->apiClient->leads();
        $leadModel = $leadsService->addOne($leadModel);

        //Привязываем контакт к сделке
        if ($this->contactId) {
            $this->attachContactToLead($leadModel, $this->contactId);
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

        try {
            $leadModel = $this->apiClient->leads()->getOne($leadId);
            $this->setLeadObjectData($leadModel);

//            $tagsCollection = $leadModel->getTags();
//            $tagModel = new TagModel();
//            $tagModel->setName('Обновлено из 1С');
//            $tagsCollection->add($tagModel);
            /*$tagsService = $this->apiClient->tags(EntityTypesInterface::LEADS);
            $tagsService->add($tagsCollection);*/

            //Добавляем теги
//            $leadModel->setTags($tagsCollection);

            //Обновляем подготовленный лид
            $this->apiClient->leads()->updateOne($leadModel);

            //Привязываем контакт к сделке
            if ($this->contactId) {
                $this->attachContactToLead($leadModel, $this->contactId);
            }
        } catch (\Exception  $e) {
            echo  $e->getTraceAsString();
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
        $leadModel = $this->apiClient->leads()->getOne($leadId);

        $this->setLeadObjectDataPayment($leadModel);

        //Обновляем подготовленный лид
        $this->apiClient->leads()->updateOne($leadModel);
    }

    /**
     * Привязать контакт к лиду
     * @param LeadModel $leadModel
     * @param int $contactId
     */
    public function attachContactToLead(LeadModel $leadModel, int $contactId): void
    {
        try {
            $contact = $this->apiClient->contacts()->getOne($contactId);
            $links = new LinksCollection();
            $links->add($contact);
            $this->apiClient->leads()->link($leadModel, $links);
        } catch (AmoCRMApiException $e) {
            ErrorPrinter::printError($e);
        }
    }

    /**
     * Наполнить лид данными
     * @param LeadModel $leadModel
     * @throws Exceptions\BaseAmoEntityException
     */
    public function setLeadObjectData(LeadModel $leadModel)
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
            $leadModel->setResponsibleUserId($responsibleUserId);
        }

        $leadModel->setUpdatedAt(time());

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
        $leadModel->setName('Сделка ' . $this->dataFromXml['Телефон']);

        //Костыль - устанавливаем LossReason в null, иначе Амо иногда выкидивает ошибку.
        $leadModel->setLossReasonId(null);

        //Устанавливаем стоимость
        if ($this->dataFromXml['Бюджет']) {
            $price = Helper::formatInt($this->dataFromXml['Бюджет']);
            $leadModel->setPrice($price);
        }

        //Устанавливаем кастомные свойства лида
        $leadCustomFieldsValuesCollection = new CustomFieldsValuesCollection();

        if ($this->dataFromXml['ТипДома']) {
            $this->setMultiSelectCustomField(
                $leadCustomFieldsValuesCollection,
                self::HOUSE_NAME__CHECKBOX__FIELD_ID,
                $this->dataFromXml['ТипДома']
            );
        }
        if ($this->dataFromXml['Комплектация']) {
            $this->setMultiSelectCustomField(
                $leadCustomFieldsValuesCollection,
                self::KOMPLECT__CHECKBOX__FIELD_ID,
                $this->dataFromXml['Комплектация']
            );
        }
        if ($this->dataFromXml['ИсточникРекламы']) {
            $this->setMultiSelectCustomField(
                $leadCustomFieldsValuesCollection,
                self::RESOURCE__CHECKBOX__FIELD_ID,
                $this->dataFromXml['ИсточникРекламы']
            );
        }
        if ($this->dataFromXml['GUID']) {
            $this->setTextCustomField(
                $leadCustomFieldsValuesCollection,
                self::GUID__TEXT__FIELD_ID,
                $this->dataFromXml['GUID']
            );
        }
        if ($this->dataFromXml['Предоплата']) {
            $this->setCheckboxCustomField($leadCustomFieldsValuesCollection, self::PREPAYMENT__TEXT__FIELD_ID);
        }
        if ($this->dataFromXml['АдресМонтажа']) {
            $this->setTextCustomField(
                $leadCustomFieldsValuesCollection,
                self::BUILDING_ADDRESS__TEXT__FIELD_ID,
                $this->dataFromXml['АдресМонтажа']
            );
        }
        if ($this->dataFromXml['Эффективность']) {
            $this->setTextCustomField(
                $leadCustomFieldsValuesCollection,
                self::EFFICIENCY__TEXT__FIELD_ID,
                Helper::formatInt($this->dataFromXml['Эффективность'])
            );
        }
        if ($this->dataFromXml['ФормаОплаты']) {
            $this->setSelectCustomField(
                $leadCustomFieldsValuesCollection,
                self::PAYMENT_FORM__CHECKBOX__FIELD_ID,
                $this->dataFromXml['ФормаОплаты']
            );
        }
        /*if ($this->dataFromXml['ВариантОплаты']) {
            $this->setSelectCustomField(
                $leadCustomFieldsValuesCollection,
                self::PAYMENT_VARIANT__CHECKBOX__FIELD_ID,
                $this->dataFromXml['ВариантОплаты']
            );
        }*/
        /*if ($this->dataFromXml['ДоговорКонтрагента']) {
            $this->setCheckboxCustomField($leadCustomFieldsValuesCollection, self::);
        }*/
        if ($this->dataFromXml['НомерПредъявленнойКартыЛояльности']) {
            $this->setTextCustomField(
                $leadCustomFieldsValuesCollection,
                self::LOYAL_CARD__TEXT__FIELD_ID,
                $this->dataFromXml['НомерПредъявленнойКартыЛояльности']
            );
        }
        if ($this->dataFromXml['Регион']) {
            $this->setTextCustomField(
                $leadCustomFieldsValuesCollection,
                self::REGION__CHECKBOX__FIELD_ID,
                $this->dataFromXml['Регион']
            );
        }
        if ($this->dataFromXml['Себестоимость']) {
            $this->setTextCustomField(
                $leadCustomFieldsValuesCollection,
                self::INNER_PRICE__TEXT__FIELD_ID,
                Helper::formatInt($this->dataFromXml['Себестоимость'])
            );
        }
        if ($this->dataFromXml['ДатаИВремяВстречи']) {
            if ('01.01.0001 0:00:00' !== $this->dataFromXml['ДатаИВремяВстречи']) {
                $dateStart = substr($this->dataFromXml['ДатаИВремяВстречи'], 0, 10);
                $datetime = explode(".", $dateStart);
                $date = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
                $this->setNumericCustomField($leadCustomFieldsValuesCollection, self::MEETING__DATE__FIELD_ID, $date);
            }
        }
        if ($this->dataFromXml['ДатаНачалаМонтажа']) {
            $dateStart = substr($this->dataFromXml['ДатаНачалаМонтажа'], 0, 10);
            $datetime = explode(".", $dateStart);
            $date = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
            $this->setNumericCustomField(
                $leadCustomFieldsValuesCollection,
                self::BUILDING_START__DATE__FIELD_ID,
                $date
            );
        }
        if ($this->dataFromXml['ДатаОкончанияМонтажа']) {
            $dateFinish = substr($this->dataFromXml['ДатаОкончанияМонтажа'], 0, 10);
            $datetime = explode(".", $dateFinish);
            $date = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
            $this->setNumericCustomField($leadCustomFieldsValuesCollection, self::BUILDING_END__DATE__FIELD_ID, $date);
        }

        $leadModel->setCustomFieldsValues($leadCustomFieldsValuesCollection);
    }

    /**
     * Проверяем оплаты - находим последнюю свободную ячейку для оплаты. В нее записываем оплату.
     * Если оплат раньше не было - меняем статус у лида на 'Внесена предоплата'.
     * @param LeadModel $leadModel
     */
    public function setLeadObjectDataPayment(LeadModel $leadModel)
    {

        //Флаг, есть ли оплаты у лида
        $hasPayments = true;

        //Номер последней оплаты в АМО. Если он не пустой, то нужно записать следующую за ним оплату.
        $numOfLastPayment = null;

        //Получаем кастомные свойства лида
        $leadCustomFieldsValuesCollection = $leadModel->getCustomFieldsValues();

        //Хак - в Amo URL хранятся с пробельными символами, если в таком же виде отправить назад - будет ошибка.
        //Заменяем пробелы на соответствующий символ %20. Urlencode не использовал, потому что шаблон ссылок везде
        // одинаковый
        if ($leadCustomFieldsValuesCollection) {
            foreach (self::AMO_CF_DOCUMENTS as $docId) {
                $doc1Field = $leadCustomFieldsValuesCollection->getBy('fieldId', $docId);
                if ($doc1Field) {
                    $doc1FieldValues = $doc1Field->getValues();
                    $doc1FieldValue = $doc1FieldValues->first();
                    if ($doc1FieldValue) {
                        $url = str_replace(' ', '%20', $doc1FieldValue->value);
                        $doc1Field->setValues(
                            (new UrlCustomFieldValueCollection())
                                ->add(
                                    (new UrlCustomFieldValueModel())
                                        ->setValue($url)
                                )
                        );
                    }
                }
            }


            //смотрим есть ли оплаты
            for ($i = 1; $i <= 8; $i++) {
                $amountFieldId = self::PAYMENT__NUMERIC__FIELDS_ID[$i];
                //                $dateFieldId = self::PAYMENT_DATES__DATE__FIELDS_ID[$i];
                $amountField = $leadCustomFieldsValuesCollection->getBy('fieldId', $amountFieldId);
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
                        $this->setTextCustomField(
                            $leadCustomFieldsValuesCollection,
                            $amountFieldId,
                            Helper::formatInt($this->dataFromXml['Summa'])
                        );
                    }
                    if ($this->dataFromXml['DataPlatezha'] && strlen($this->dataFromXml['DataPlatezha']) > 3) {
                        $payDate = substr($this->dataFromXml['DataPlatezha'], 0, 10);
                        $datetime = explode(".", $payDate);
                        $date = mktime(0, 0, 0, $datetime[1], $datetime[0], $datetime[2]);
                        $this->setNumericCustomField(
                            $leadCustomFieldsValuesCollection,
                            self::BUILDING_END__DATE__FIELD_ID,
                            $date
                        );
                    }
                    break;
                }
            }
        }
        if (!$hasPayments) {
            //Оплат еще не было - переводим в статус 'Внесена предоплата'
            $leadModel->setStatusId(self::PREPAYMENT_STATUS);
        }

        //Установим время апдейта
        $leadModel->setUpdatedAt(time());

        //Сохраняем кастомные свойства у лида
        $leadModel->setCustomFieldsValues($leadCustomFieldsValuesCollection);
    }
}
