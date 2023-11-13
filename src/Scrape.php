<?php

namespace App;

use Symfony\Component\DomCrawler\Crawler;

require 'vendor/autoload.php';

class Scrape
{
    private array $products = [];

    private const URL = 'https://www.magpiehq.com/developer-challenge/smartphones';
    private const MEGABYTES_IN_GIGABYTE = 1000;
    private const FILE_NAME = 'output.json';
    private const PAGE_SELECTOR = '#pages a';
    private const IN_STOCK_TEXT = 'In Stock';

    public function run(): void
    {
        $pages = $this->fetchPages();
        $this->crawlPages($pages);

        file_put_contents(
            self::FILE_NAME,
            json_encode($this->products, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
    }

    public function fetchPages()
    {
        $document = ScrapeHelper::fetchDocument(self::URL);

        $pages = $document->filter(self::PAGE_SELECTOR);

        return $pages->each(function (Crawler $node){
             return $node->text();
        });
    }

    public function crawlPages($pages)
    {
        foreach($pages as $page)
        {
            $url = self::URL . '/?page=' . $page;
            $document = ScrapeHelper::fetchDocument($url);
            $document->filter('div .product')
                ->each(function (Crawler $node){
                    $this->extractColorVariants($node);
                });
        }
    }

    /**
     * Extract products colourvariants
     */
    public function extractColorVariants($node)
    {
        $coloursVariants = $node->filter('span[data-colour]')
            ->each(function(Crawler $node){
                return $node->attr('data-colour');
            });

        foreach($coloursVariants as $color)
        {      
            $this->extractProductData($node, $color);
        }
    }

    /**
     * Extract data for each product
     */
    public function extractProductData($node, $color)
    {
        $node->each(function (Crawler $node) use ($color){
            $productName = $node->filter('.product-name')->text();
            $productPrice = ltrim($node->filter('.my-8.block.text-center.text-lg')->text(), '£');
            $productCapacity = $node->filter('.product-capacity')->text();
            $formattedCapacity = $this->formatCapacity($productCapacity);
            $productAvailability = $node->filter('.product .bg-white div:nth-child(5)')->text();
            $availabilityText = $this->setAvailabilityText($productAvailability);
            $isAvailable = (bool) strpos($productAvailability, self::IN_STOCK_TEXT);
            $shippingText = $node->filter('.my-4.text-sm.block.text-center')->eq(1)->text('');
            $shippingDate = $this->formatShippingDate($shippingText);
            $imageUrl = $node->filter('img')->image()->getUri();

            // Check for duplicates before adding the product
            if($this->isDuplicate($productName, $formattedCapacity, $color)){
                return; // skip duplicates
            }
            
            // Instantiate the Product object and add it to the products array
            $this->products[] = new Product(
                    $productName,
                    $productPrice,
                    $imageUrl,
                    $formattedCapacity,
                    $color,
                    $availabilityText,
                    $isAvailable,
                    $shippingText,
                    $shippingDate
            );
            
        });
    }

    private function formatCapacity($capacity)
    {
        // check if capacity is in MB
        if( strpos($capacity, "MB") !== false ){
            return (int) trim(str_replace("MB", "", $capacity));
        }

        // convert GB to MB
        $capacityInGB = (int) trim(str_replace("GB", "", $capacity));
        return $capacityInGB * self::MEGABYTES_IN_GIGABYTE;
    }

    private function setAvailabilityText(string $availabilityText)
    {
        return trim(str_replace("Availability:", "", $availabilityText));
    }

    private function formatShippingDate($shippingDetail)
    {
        // Check different date formats
        if ($shippingDetail && preg_match("/(\d{1,2}) (\w+) (\d{4})/", $shippingDetail, $result) || preg_match("/\d{4}-\d{2}-\d{2}/", $shippingDetail, $result) || preg_match("/\w+day\s\d{1,2}(st|th|rd|nd)\s\w+ \d{4}/", $shippingDetail, $result) || preg_match("/tomorrow/", $shippingDetail, $result)) {
            return date('Y-m-d', strtotime($result[0]));
        }
        return null;
    }

    private function isDuplicate($title, $capacity, $color): bool
    {
        // Check the current content against all the element that are in the products array
    	foreach($this->products as $product)
    	{
    		if(($title == $product->title) && ($capacity == $product->capacityMB) && ($color == $product->colour))
    		{
    			return true;
    		}		
    	}
    	return false;
    }
}

$scrape = new Scrape();
$scrape->run();
