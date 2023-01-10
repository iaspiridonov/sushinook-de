<?php

namespace Src\Integrations\ERP;

use Core\Service\Registry;
use Src\Controller\Cart;

class Client
{
    public static function getBonuses(string $phone)
    {
        return new Connector('site/clients/client?phone=' . $phone, 'GET');
    }

    public static function checkPromo(string $code)
    {
        if(!$code) {
            $request = Registry::get('http.request.body');
            $code = $request['code'];
        }
        $cartController = new Cart();

        $productsCart = Registry::get('session.combo');
        if (!$productsCart) $productsCart = [];
        $sessionCart = Registry::get('session.cart');
        if (!$sessionCart) $sessionCart = [];

        $nomenclatures = [];

        foreach ($productsCart as $productCart) {
            $nomenclatures[] = [
                'id' => $productCart['code'],
                'amount' => $productCart['count'] * 1000,
                'promotional' => false
            ];
        }

        foreach ($sessionCart as $item) {
            $nomenclatures[] = [
                'id' => $cartController->getArticleIdByName($item['name']),
                'amount' => $item['count'] * 1000,
                'promotional' => false
            ];

            if ($item['ingsAdd']) {
                $ings = Cart::getIngsByCartItem($item);
                foreach ($ings as $ing) {
                    $nomenclatures[] = [
                        'id' => Cart::getArticleIdByName($ing->name),
                        'amount' => 1000,
                        'promotional' => false
                    ];
                }
            }
        }

        $nowDateTime = (new \DateTime('now', new \DateTimeZone('Europe/Berlin')))->format('Y-m-d H:i');
        $requestData = [
            "date" => $nowDateTime,
            "client_id" => null,
            "certificate" => $code,
            "nomenclatures" => $nomenclatures,
            "trade_point_id" => 4,
            "organization_id" => 1,
        ];

        return new Connector('site/orders/preview-order-certificate', 'POST', $requestData);
    }

    public static function sendOrder() {
        if ($_POST['cod_sms'] !== $_POST['phone_code']) {
            return 'code_error';
        }

        $cartController = new Cart();

        $info = $cartController->rycleinfo();
        $certificateId = Registry::get('session.certificateId') ?? null;
        $productsCart = Registry::get('session.combo');
        $nomenclatures = [];

        $sessionCart = Registry::get('session.cart');
        if (!$sessionCart) $sessionCart = [];

        foreach ($sessionCart as $item) {
            $nomenclatures[] = [
                'id' => $cartController->getArticleIdByName($item['name']),
                'amount' => $item['count'] * 1000,
                'promotional' => false,
                "title" => $item['name'],
                "is_service" => false,
                "color" => "#BDC3C7",
                "category_id" => 5,
                "vat_id" => 1,
                "vat_percent" => 7,
                "vat_title" => "7%",
                "free" => false,
                "vat_sum" => 34,
                "disabled" => false
            ];

            if ($item['ingsAdd']) {
                $ings = Cart::getIngsByCartItem($item);
                foreach ($ings as $ing) {
                    $nomenclatures[] = [
                        'id' => Cart::getArticleIdByName($ing->name),
                        'amount' => 1000,
                        'promotional' => false,
                        "title" => $ing->name,
                        "is_service" => false,
                        "color" => "#BDC3C7",
                        "category_id" => 5,
                        "vat_id" => 1,
                        "vat_percent" => 7,
                        "vat_title" => "7%",
                        "free" => false,
                        "vat_sum" => 34,
                        "disabled" => false
                    ];
                }
            }
        }

        if (!$productsCart) $productsCart = [];
        foreach ($productsCart as $productCart) {
            $nomenclatures[] = [
                'id' => $productCart['code'],
                'amount' => $productCart['count'] * 1000,
                'promotional' => false,
                "title" => $productCart['name'],
                "is_service" => false,
                "color" => "#BDC3C7",
                "category_id" => 5,
                "vat_id" => 1,
                "vat_percent" => 7,
                "vat_title" => "7%",
                "free" => false,
                "vat_sum" => 34,
                "disabled" => false
            ];
        }

        $needDelivery = $_POST['delivery'] !== 'Самовывоз';

        $nomenclatures[] = [
            'id' => $needDelivery ? '130' : '136',
            'amount' => 1000,
            'promotional' => false,
            'title' => $needDelivery ? 'kostenlose Lieferung Pirmasens' : 'Selbstabhollung Hauptstraße 45',
            "is_service" => false,
            "color" => "#BDC3C7",
            "category_id" => 5,
            "vat_id" => 1,
            "vat_percent" => 7,
            "vat_title" => "7%",
            "free" => false,
            "vat_sum" => 34,
            "disabled" => false
        ];

        $preOrder = str_replace('von', 'с', $_POST['preorder']);
        $preOrder = str_replace('bis', 'до', $preOrder);
        $preOrder = str_replace('Uhr', 'часов', $preOrder);

        $comment = null;
        $comment .= $_POST['preorder'] != false ?  'Предзаказ на ' . $preOrder . ' ' : '';
        $comment .= $_POST['surrender'] != false ? "Сдача с " . $_POST['surrender'] . ' ' : '';
        $comment .= $_POST['message'] != false ? "Дополнительный комментарий: " . $_POST['message'] . ' ' : '';

        try {
            $bonuses = (int)$_POST['count_bonus'] * 100;
            $bonusesForUse = (int)$_POST['count_bonus_for_use'] * 100;
        } catch (\Throwable $ex) {
            $bonuses = 0;
            $bonusesForUse = 0;
        }

        if ($bonusesForUse > $bonuses) {
            return null;
        }

        try {
            $branch = $_POST['branch'];
        } catch (\Throwable $ex) {
            $branch = '';
        }

        $sum = (int) $info['sum'] - (int) $info['promoamount'] - $bonusesForUse;

        if ($sum < 1) {
            $payments[] = [
                'id' => 6,
                'sum' => (int) $info['sum'] - (int) $info['promoamount'],
                'payment_type' => 'bonuses',
            ];
        } else {
            if ($bonuses && $bonusesForUse) {
                $payments[] = [
                    'id' => 6,
                    'sum' => $bonusesForUse,
                    'payment_type' => 'bonuses',
                ];
            }

            $payments[] = [
                "id" => 1,
                "title" => $_POST['delivery'],
                "payment_type" => "cash",
                "sum" => $sum,
                "disabled" => false
            ];
        }

        $requestData = [
            "date" => (new \DateTime('now', new \DateTimeZone('Europe/Berlin')))->format('Y-m-d H:i'),
            "uuid" => $cartController->generateUniqueGuid(),
            "discount_id" => null,
            "price_margin_id" => null,
            "order_tags" => [],
            "client_id" => null,
            "payments" => $payments,
            "sales_channel_id" => null,
            "is_fiscal" => false,
            "comment" => $comment,
            "table_number" => null,
            "certificate_id" => $certificateId,
            "details" => [
                "user_card" => "",
                "phone" => $_POST['phone'],
                "name" => $_POST['name'],
                "street" => $needDelivery ? $_POST['street'] : null,
                "city" => [
                    "id" => 10,
                    "title" => "Pirmasens"
                ],
                "building" => $needDelivery ? $_POST['house'] : null,
                "entrance" => null,
                "floor" => $needDelivery ? $_POST['level'] : null,
                "room" => $needDelivery ? $_POST['flat'] : null,
                "comment" => $comment,
                "coordinates" => [
                    "latitude" => "",
                    "longitude" => ""
                ],
                "selectedOrganization" => [
                    "id" => null,
                    "title" => $branch
                ],
                "selectedTradepoint" => [
                    "id" => 9,
                    "title" => "Pirmasens-1",
                    "table_count" => 7,
                    "city" => [
                        "id" => 10,
                        "title" => "Pirmasens"
                    ],
                    "cashbox" => [
                        "id" => 84,
                        "title" => "Bargeld Pirmasens-1"
                    ],
                    "stock" => [
                        "id" => 13,
                        "title" => "Pirmasens-1"
                    ],
                    "zones_count" => 0,
                    "printer_id" => 9,
                    "delivery_service" => [
                        "id" => null,
                        "title" => null,
                        "category_id" => null
                    ]
                ],
                "addresses" => [],
                "city_id" => 10,
                "client_name" => $_POST['name']
            ],
            "nomenclatures" => $nomenclatures,
            "trade_point_id" => 9,
            "organization_id" => 18
        ];

        dump($requestData);
        dump(new Connector('site/orders/add-order', 'POST', $requestData));
        exit;
        return new Connector('site/orders/add-order', 'POST', $requestData);
    }
}