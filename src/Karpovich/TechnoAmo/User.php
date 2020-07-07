<?php


namespace Karpovich\TechnoAmo;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Exceptions\AmoCRMApiException;

class User extends BaseAmoEntity
{
    public function __construct(AmoCRMApiClient $apiClient)
    {
        parent::__construct($apiClient);
    }

    /**
     * Получить ID пользователя по его логину
     * @param string $userLogin
     * @return bool|mixed
     */
    public function getIdByLogin(string $userLogin):?int
    {
        $userId = null;
        //Получаем и обходим всех пользователей аккаунта (фильтр по пользователям в AMO, к сожалению, не реализован)
        $usersService = $this->apiClient->users();
        try {
            $usersCollection = $usersService->get();
            $arUsers = $usersCollection->toArray();
        } catch (AmoCRMApiException $e) {
            ErrorPrinter::printError($e);
        }
        if ($arUsers) {
            foreach ($arUsers as $arUser) {
                if (strtoupper($arUser['email']) === strtoupper($userLogin)) {
                    $userId = $arUser['id'];
                }
            }
        }
        return $userId;
    }
}
