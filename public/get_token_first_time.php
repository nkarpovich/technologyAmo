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
var_dump($response);
$access_token = $response['access_token']; //Access токен
$refresh_token = $response['refresh_token']; //Refresh токен
$token_type = $response['token_type']; //Тип токена
$expires_in = $response['expires_in']; //Через сколько действие токена истекает

/*saveToken([
    'accessToken' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjRjNGY0MTFlYjU5Zjc0NWE1ZWJiNGIzNWMzMjY4OTE1ZjlkNTczYWZlN2IwMTk2YTRjOWM4NGEyNGRkMmViNjQxODFhOGMwZTIxN2Q4ZDUzIn0.eyJhdWQiOiIzNzU2NTczMS1hYmJkLTQ0ZGYtOWNhYy02MDFiOTFjYjkxZmEiLCJqdGkiOiI0YzRmNDExZWI1OWY3NDVhNWViYjRiMzVjMzI2ODkxNWY5ZDU3M2FmZTdiMDE5NmE0YzljODRhMjRkZDJlYjY0MTgxYThjMGUyMTdkOGQ1MyIsImlhdCI6MTU5MjEyNzk1OCwibmJmIjoxNTkyMTI3OTU4LCJleHAiOjE1OTIyMTQzNTgsInN1YiI6IjI4MTYxMTYiLCJhY2NvdW50X2lkIjoyMTk1MzMxNCwic2NvcGVzIjpbInB1c2hfbm90aWZpY2F0aW9ucyIsImNybSIsIm5vdGlmaWNhdGlvbnMiXX0.ruTd-gCs8Er3_ElWVMIkAE1vYIhOLj3TBkduMaH8ArWTiTSJV6L_yYWfty-jp0jQKZKIixslDevqeO_ZHEGVpdDN3F8rE95SVh7aBYASO6e9yza6sXm1CWI_GbAa53qKoP3il51inF7K4PtRIchv543-XyLkIZyqK_EnoFpQQzDJHJtYkdw_p36wogUL8GH5X93zyRdlgXbbUoiz860oHuh2Amzr2yTJYQ15dOTDSk432D9nZennKB4pJDWKPe3lHD4xZ5A065bVxsTN8r3krBF_BrqGAHa6QqiMyaqjJoTNQLC3ZIhk3iQP4N0S_Tsx8YS5rnCv2nOj0-LTI9D5Sw',
    'refreshToken' => 'def50200bded8881eaf156930433bd180fa6c3ff009b0da91a54cc88b9f4d0c19f38c58aa21a47e8dfe403b73fd7ef9b22a8a7b9905964cfdfcc17d5d8cf1a60e112f6c741a554b1ca02fcc2b52db2eba5398457e0a210b8b2d8c68df35af99d4adbec23a6bddb461c2e1a247720a73adb7d1ca736fd303986deaeaf001656e823ff55abb9beedce42098ab5f2734a0a4232dcfce09a987593a9be1cdfb6d5c8d7b397f1e45a8d8a229678ecfc533aac02593464008c10dc9ff501f8f83f113cd119a220b069bd4baa79b21a486abc8eac4be9e0f3d41263639382843866b1a3eaac0e23f54d22acebde5b94b7e754b50c3daebd92a130e53f3e390db4ca4923d4b0479abb659b7928f66f09aad1b5af82130e07c062196981df68e9902d1c623c6141a217275cd58a622434c683d0794d8d6806398aa81fc9e6af110ba1e77be8194c936a5e5dc7727664e7f48dee0b5ad169d71187830c12aec5a8417180e624d5d2183ab5400a76dada9c507a2b737dcb3a70ceb697ec2ee516c704865d80f8a1c7660256d2beed30899a79a7fe1efc5f853131f9017840e91939ae670251be8e6ebecde09a036959e732de923d480e5245f2',
    'expires' => '86400',
    'baseDomain' => $apiClient->getAccountBaseDomain(),
]);*/