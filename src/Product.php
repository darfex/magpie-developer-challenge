<?php

namespace App;

class Product
{
    public string $title;
    public float $price;
    public string $imageUrl;
    public string $capacityMB;
    public string $colour;
    public string $availabilityText;
    public bool $isAvailable;
    public string $shippingText;
    public ?string $shippingDate;

    public function __construct($title, $price, $imageUrl, $capacityMB, $colour, $availabilityText, $isAvailable, $shippingText, $shippingDate)
    {
        $this->title = $title;
        $this->price = $price;
        $this->imageUrl = $imageUrl;
        $this->capacityMB = $capacityMB;
        $this->colour = $colour;
        $this->availabilityText = $availabilityText;
        $this->isAvailable = $isAvailable;
        $this->shippingText = $shippingText;
        $this->shippingDate = $shippingDate;
    }
}
