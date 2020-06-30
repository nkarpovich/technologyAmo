<?php


namespace Karpovich\TechnoAmo;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\CheckboxCustomFieldValuesModel;
//use AmoCRM\Models\CustomFieldsValues\DateCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\MultiselectCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\SelectCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\CheckboxCustomFieldValueCollection;
//use AmoCRM\Models\CustomFieldsValues\ValueCollections\DateCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultiselectCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\SelectCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\CheckboxCustomFieldValueModel;
//use AmoCRM\Models\CustomFieldsValues\ValueModels\DateCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultiselectCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\SelectCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;

/**
 * Базовый класс для всех сущностей АМО
 * В основном содержит методы для наполнения полей сущности значениями
 * Class BaseAmoEntity
 * @package Karpovich\TechnoAmo
 */
class BaseAmoEntity
{
    /**
     * @var AmoCRMApiClient
     */
    protected $apiClient;

    /**
     * @param AmoCRMApiClient $apiClient
     */
    public function __construct(AmoCRMApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Добавить значение мультиселекта в коллекцию CustomFieldsValuesCollection
     * @param CustomFieldsValuesCollection $customFieldsValuesCollection
     * @param int $fieldId
     * @param string $value
     * @param int|null $enumId
     * @return bool
     */
    public function setMultiSelectCustomField(
        CustomFieldsValuesCollection $customFieldsValuesCollection,
        int $fieldId,
        string $value = '',
        int $enumId = null
    ): bool {
        $multiSelectCustomFieldValuesModel = new MultiSelectCustomFieldValuesModel();
        $multiSelectCustomFieldValuesModel->setFieldId($fieldId);
        if ($enumId) {
            $multiSelectCustomFieldValuesModel->setValues(
                (new MultiSelectCustomFieldValueCollection())
                    ->add((new MultiSelectCustomFieldValueModel())->setEnumId($enumId))
            );
        } elseif ($value) {
            $multiSelectCustomFieldValuesModel->setValues(
                (new MultiSelectCustomFieldValueCollection())
                    ->add((new MultiSelectCustomFieldValueModel())->setValue($value))
            );
        } else {
            return false;
        }
        $customFieldsValuesCollection->add($multiSelectCustomFieldValuesModel);
        return true;
    }

    /**
     * Добавить значение селекта в коллекцию CustomFieldsValuesCollection
     * @param CustomFieldsValuesCollection $customFieldsValuesCollection
     * @param int $fieldId
     * @param string $value
     * @param int|null $enumId
     * @return bool
     */
    public function setSelectCustomField(
        CustomFieldsValuesCollection $customFieldsValuesCollection,
        int $fieldId,
        string $value = '',
        int $enumId = null
    ): bool {
        $selectCustomFieldValuesModel = new SelectCustomFieldValuesModel();
        $selectCustomFieldValuesModel->setFieldId($fieldId);
        if ($enumId) {
            $selectCustomFieldValuesModel->setValues(
                (new SelectCustomFieldValueCollection())
                    ->add((new SelectCustomFieldValueModel())->setEnumId($enumId))
            );
        } elseif ($value) {
            $selectCustomFieldValuesModel->setValues(
                (new MultiSelectCustomFieldValueCollection())
                    ->add((new MultiSelectCustomFieldValueModel())->setValue($value))
            );
        } else {
            return false;
        }
        $customFieldsValuesCollection->add($selectCustomFieldValuesModel);
        return true;
    }

    /**
     * Добавить текстовое значение в коллекцию CustomFieldsValuesCollection
     * @param CustomFieldsValuesCollection $customFieldsValuesCollection
     * @param int $fieldId
     * @param string $value
     * @return void
     */
    public function setTextCustomField(
        CustomFieldsValuesCollection $customFieldsValuesCollection,
        int $fieldId,
        string $value
    ): void {
        $textCustomFieldValueModel = new TextCustomFieldValuesModel();
        $textCustomFieldValueModel->setFieldId($fieldId);
        $textCustomFieldValueModel->setValues(
            (new TextCustomFieldValueCollection())
                ->add((new TextCustomFieldValueModel())->setValue($value))
        );
        $customFieldsValuesCollection->add($textCustomFieldValueModel);
    }

    /**
     * Установить значение true в поле с ID $fieldId в коллекцию CustomFieldsValuesCollection
     * @param CustomFieldsValuesCollection $customFieldsValuesCollection
     * @param int $fieldId
     * @return void
     */
    public function setCheckboxCustomField(
        CustomFieldsValuesCollection $customFieldsValuesCollection,
        int $fieldId
    ): void {
        $checkboxCustomFieldValueModel = new CheckboxCustomFieldValuesModel();
        $checkboxCustomFieldValueModel->setFieldId($fieldId);
        $checkboxCustomFieldValueModel->setValues(
            (new CheckboxCustomFieldValueCollection())
                ->add((new CheckboxCustomFieldValueModel())->setValue(true))
        );
        $customFieldsValuesCollection->add($checkboxCustomFieldValueModel);
    }

    /** Добавить числовое значение в коллекцию CustomFieldsValuesCollection
     * @param CustomFieldsValuesCollection $customFieldsValuesCollection
     * @param int $fieldId
     * @param int $value
     */
    public function setNumericCustomField(
        CustomFieldsValuesCollection $customFieldsValuesCollection,
        int $fieldId,
        int $value
    ): void {
        $numericCustomFieldValueModel = new NumericCustomFieldValuesModel();
        $numericCustomFieldValueModel->setFieldId($fieldId);
        $numericCustomFieldValueModel->setValues(
            (new NumericCustomFieldValueCollection())
                ->add((new NumericCustomFieldValueModel())->setValue($value))
        );
        $customFieldsValuesCollection->add($numericCustomFieldValueModel);
    }

    /**
     * Добавить дату в коллекцию CustomFieldsValuesCollection
     * !!!На 17.06.2020 НЕ работает, AMO ожидает на вход int, а в этом методе параметр приводится к строке вида
     * 'YYY-MM-DD'
     * @param CustomFieldsValuesCollection $customFieldsValuesCollection
     * @param int $fieldId
     */
    /*public function setDateCustomField(
        CustomFieldsValuesCollection $customFieldsValuesCollection,
        int $fieldId
    ): void {
        $dateCustomFieldValuesModel = new DateCustomFieldValuesModel();
        $dateCustomFieldValuesModel->setFieldId($fieldId);
        $dateCustomFieldValuesModel->setValues(
            (new DateCustomFieldValueCollection())
                ->add((new DateCustomFieldValueModel())->setValue(time()))
        );
        $customFieldsValuesCollection->add($dateCustomFieldValuesModel);
    }*/
}
