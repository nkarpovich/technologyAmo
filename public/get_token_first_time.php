<?php
$subdomain = 'technologyamo'; //Поддомен нужного аккаунта
$link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса

/** Соберем данные для запроса */
$data = [
    'client_id' => '37565731-abbd-44df-9cac-601b91cb91fa',
    'client_secret' => 'qkKpSvmobDmKrA5e8r9uRiMVrqmVuHT37P65pVhLjjORsaWmr5uaNR2PGOsmaYJL',
    'grant_type' => 'authorization_code',
    'code' => 'def50200f0ee0159e5aab21f5d282584909c0e966a07c4a1c23b4965517030ada1ccb35a28b272856646f3d4c86c2821d086fb43c4ecf62a63f4295baf993b5b6cefae7f8b50620edc4f897aff30b7a28a4f374dcf72deebbb2808debf04eee9582358beedf85c70d9405d196fa5b6753e0d15b718f474584c15420fb2ed55b2c8bf1742b34b72acc75c2449a9242856fb0df269b77c6cb805f95e8eee5f98f71117a59aa3d07a8a7131e199332a338c9daf9ff9b2cff0be1b7e440f4e86fcc295d9ff2bd889718f1ba3a3185d4c0f9bba2058d42861adb4c33d6218eb1853251e6ba61195aa86e0fd9bc2bd3f909aef961c9e63e18b8213d87c76279cbd294a5b08f7c9615475594fa9ce345adc2f41a5bee42642877d49ad2237bf2e3a441a2a2da5cbd874981eeec8876d2decb9ec6900333e570c852c8bf697ef0725f6ab400bafae341097295abe6e3ec362d746cba32574df33a1ec8849113d9dd3c3e15477645c20e9d65ca59caf1e3cb20e2155dc5437ec37c382e83cb77d04853fc5074095afe43051f42d910d33b2f510922dbd1f346e6c480c7e3de30fa6b56e4bd30147ce89f85e612764987c8a1c61ae35d9f291193dbf7414814d3659804b50c74d72b21e20554bf8cfe2',
    'redirect_uri' => 'https://technologyamo.amocrm.ru/',
];

/**
 * Нам необходимо инициировать запрос к серверу.
 * Воспользуемся библиотекой cURL (поставляется в составе PHP).
 * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
 */
$curl = curl_init(); //Сохраняем дескриптор сеанса cURL
/** Устанавливаем необходимые опции для сеанса cURL  */
curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
curl_setopt($curl,CURLOPT_URL, $link);
curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
curl_setopt($curl,CURLOPT_HEADER, false);
curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
$out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
/** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
$code = (int)$code;
$errors = [
    400 => 'Bad request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Not found',
    500 => 'Internal server error',
    502 => 'Bad gateway',
    503 => 'Service unavailable',
];

try
{
    /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
    if ($code < 200 || $code > 204) {
        throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
    }
}
catch(\Exception $e)
{
    die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
}

/**
 * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
 * нам придётся перевести ответ в формат, понятный PHP
 */
$response = json_decode($out, true);
//var_dump($response);
$access_token = $response['access_token']; //Access токен
$refresh_token = $response['refresh_token']; //Refresh токен
$token_type = $response['token_type']; //Тип токена
$expires_in = $response['expires_in']; //Через сколько действие токена истекает

saveToken([
    'accessToken' => $access_token,
    'refreshToken' => $refresh_token,
    'expires' => '86400',
    'baseDomain' => $apiClient->getAccountBaseDomain(),
]);