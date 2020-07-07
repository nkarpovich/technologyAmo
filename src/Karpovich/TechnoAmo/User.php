<?php


namespace Karpovich\TechnoAmo;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Exceptions\AmoCRMApiException;
use Symfony\Component\VarDumper\VarDumper;

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
    public function getIdByLogin(string $userLogin):int
    {
        $userId = false;
        //Получаем и обходим всех пользователей аккаунта (фильтр по пользователям в AMO, к сожалению, не реализован)
        $usersService = $this->apiClient->users();
        try {
            $usersCollection = $usersService->get();
            $arUsers = $usersCollection->toArray();
//            VarDumper::dump($arUsers);
        } catch (AmoCRMApiException $e) {
            ErrorPrinter::printError($e);
            die;
        }
        foreach ($arUsers as $arUser) {
            echo $arUser['email'].PHP_EOL;
            if (strtoupper($arUser['email']) === strtoupper($userLogin)) {
                $userId = $arUser['id'];
            }
        }
        return $userId;
    }
}
