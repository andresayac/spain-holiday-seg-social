<?php


require 'inc/SegSocial.class.php';

$scraper = new SegSocial();

/*******************
 * get list of years available for validation
 */
//$provinces = $scraper->getProvinces(); // return array
//$locations = $scraper->getLocations($provinces); // return array
//var_dump($provinces);
//var_dump($locations);

$holidays = $scraper->getHolidays('28#Madrid', '280920000 #MOSTOLES');
var_dump(json_encode($holidays));
var_dump($scraper->isHoliday('2022-12-26', $holidays)); // return bool;