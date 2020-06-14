<?php


namespace TechnoAmo;

use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\NullTagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NullCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use TechnoAmo\Helper;

class Lead
{
    private $leadsService;

    public function __construct(\AmoCRM\EntitiesServices\Leads $leadsService)
    {
        $this->leadsService = $leadsService;
    }

    /**
     * Создает нового лида из XML документа
     * @param \SimpleXMLElement $xmlObject
     * @throws AmoCRMApiException
     */
    public function create(\SimpleXMLElement $xmlObject)
    {
        $newLead = new LeadModel();
        $leadCustomFieldsValues = new CustomFieldsValuesCollection();
        $textCustomFieldValueModel = new TextCustomFieldValuesModel();
        $textCustomFieldValueModel->setFieldId(269303);
        $textCustomFieldValueModel->setValues(
            (new TextCustomFieldValueCollection())
                ->add((new TextCustomFieldValueModel())->setValue('Текст'))
        );
        $leadCustomFieldsValues->add($textCustomFieldValueModel);
        $newLead->setCustomFieldsValues($leadCustomFieldsValues);
        $newLead->setName('Example');

        $leadsCollection = new LeadsCollection();
        $leadsCollection->add($newLead);
        try
        {
            $newLead = $this->leadsService->addOne($newLead);
        } catch (AmoCRMApiException $e)
        {
            throw new AmoCRMApiException($e);
        }
    }

    public function update(\SimpleXMLElement $xmlObject, \AmoCRM\Models\LeadModel $lead)
    {
        $this->leadsService->update($lead);
    }
}