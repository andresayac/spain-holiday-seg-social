<?php


require 'inc/SegSocial.class.php';
# Dependencies
require 'vendor/autoload.php';

$scraper = new SegSocial();

/*******************
 * get list of provinces available for validation
 */
$provinces = $scraper->getProvinces(); // return array
var_dump($provinces);

/*******************
 * get list of provinces available for validation
 */
$locations = $scraper->getLocations($provinces); // return array
var_dump($locations);

/*******************
 * get list of holidays of the selected province
 */

$holidays = $scraper->getHolidays('08#Barcelona', '081240000 #MOLLET DEL VALLES');
var_dump($holidays);


/*******************
 * Check if it is a holiday
 */
var_dump($scraper->isHoliday('2022-12-26', $holidays)); // return bool;