<?php


namespace TechnoAmo;


class BaseAmoEntity
{
    /**
     * @var \AmoCRM\Client\AmoCRMApiClient
     */
    protected $apiClient;
    /**
     * @param \AmoCRM\Client\AmoCRMApiClient $apiClient
     */
    public function __construct(\AmoCRM\Client\AmoCRMApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }
}