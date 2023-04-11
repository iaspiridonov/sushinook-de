<?php

namespace Src\Integrations\GoogleSheets\DataTransferObject;

use Src\Helpers\UTM;

class OrderDTO
{
    private ?string $id_order;
    private ?string $name;
    private ?string $phone;
    private ?string $products;
    private ?string $revenue;
    private ?string $payment_method;
    private ?string $delivery;
    private ?string $street;
    private ?string $house;
    private ?string $apartment;
    private ?string $floor;
    private ?string $comment;
    private ?string $date_time;

    public function __construct(
        ?string $id_order,
        ?string $name,
        ?string $phone,
        ?string $products,
        ?string $revenue,
        ?string $payment_method,
        ?string $delivery,
        ?string $street,
        ?string $house,
        ?string $apartment,
        ?string $floor,
        ?string $comment,
        ?string $date_time
    )
    {
        $this->id_order = $id_order;
        $this->name = $name;
        $this->phone = $phone;
        $this->products = $products;
        $this->revenue = $revenue;
        $this->payment_method = $payment_method;
        $this->delivery = $delivery;
        $this->street = $street;
        $this->house = $house;
        $this->apartment = $apartment;
        $this->floor = $floor;
        $this->comment = $comment;
        $this->date_time = $date_time;
    }

    public function asArray(): array
    {
        return array_merge(
            [
                $this->id_order,
                $this->name,
                $this->phone,
                $this->products,
                $this->revenue,
                $this->payment_method,
                $this->delivery,
                $this->street,
                $this->house,
                $this->apartment,
                $this->floor,
                $this->comment,
                $this->date_time,
            ],
            UTM::getSavedUTMWithoutKeys(),
        );
    }
}