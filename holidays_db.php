<?php
# Dependencies
require 'vendor/autoload.php';
require 'inc/SegSocial.class.php';


$database = new PDO('sqlite:holidays_spain.sqlite');

$scraper = new SegSocial();

$provinces = $scraper->getProvinces(); // return array

foreach ($provinces as  $province) {
    $database->query("INSERT INTO provinces (id, name, code) VALUES({$province['id']}, '{$province['name']}', '{$province['code']}');");
}


foreach ($provinces as $province) {
    $locations = $scraper->getLocations([$province]); // return array
    foreach ($locations  as $key => $location) {
        $defaul_province = ['id' => $province['id'], 'code' => '000', 'name' => 'Default'];
        array_unshift($location, $defaul_province);
        foreach ($location as $location_data) {
            $database->query("INSERT INTO locations (id, name, code, province_id) VALUES({$location_data['id']}, '{$location_data['name']}', '{$location_data['code']}', {$province['id']});");
            $holidays_data = $scraper->getHolidays($province['code'], $location_data['code']);

            foreach ($holidays_data as $holiday_data) {
                $database->query("INSERT INTO holidays ('day', 'type', 'holiday', 'location_id') VALUES('{$holiday_data['day']}', '{$holiday_data['type']}', '{$holiday_data['holiday']}', {$location_data['id']});");
            }
        }
    }
}