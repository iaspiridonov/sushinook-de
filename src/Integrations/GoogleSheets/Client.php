<?php

namespace Src\Integrations\GoogleSheets;
require_once __DIR__ . '/../../../sheet/vendor/autoload.php';
use Core\Service\Location\LocationConfig;
use Src\Integrations\GoogleSheets\DataTransferObject\OrderDTO;

//@see https://pocketadmin.tech/ru/%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D0%B0-%D1%81-4-%D0%B2%D0%B5%D1%80%D1%81%D0%B8%D0%B5%D0%B9-api-google-%D1%82%D0%B0%D0%B1%D0%BB%D0%B8%D1%86%D1%8B-%D0%BD%D0%B0-php/#Trebovania_dla_raboty_s_API_Google_tablicy_na_php

class Client
{
    private const TABLE_ID      = '15vWZrIfrRR1GgRGo53upIYnpk-O2lTU8sABsgtP4j-8';
    private const RANGE         = '!A1:Z';
    private const LIST_NAME     = 'Pirmasens';

    public function setRow(OrderDTO $orderData): void
    {
        $service    = $this->getService();
        $range      = $this->getRange();
        $ValueRange = new \Google_Service_Sheets_ValueRange();
        $ValueRange->setValues([$orderData->asArray()]);

        $options = ['valueInputOption' => 'USER_ENTERED']; // Указываем в опциях обрабатывать пользовательские данные
        $service->spreadsheets_values->append(
            self::TABLE_ID,
            $range,
            $ValueRange,
            $options
        );
    }

    private function getService(): \Google_Service_Sheets
    {
        // Ключ доступа к сервисному аккаунту
        $googleAccountKeyFilePath = __DIR__ . '/../../../service_key.json';
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $googleAccountKeyFilePath);

        // Создаем новый клиент
        $client = new \Google_Client();
        // Устанавливаем полномочия
        $client->useApplicationDefaultCredentials();

        // Добавляем область доступа к чтению, редактированию, созданию и удалению таблиц
        $client->addScope('https://www.googleapis.com/auth/spreadsheets');

        return new \Google_Service_Sheets($client);
    }

    private function getRange(): string
    {
        return self::LIST_NAME . self::RANGE;
    }
}