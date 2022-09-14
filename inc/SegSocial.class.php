<?php
# Dependencies
require 'vendor/autoload.php';


use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class SegSocial
{
    private $URL_BASE = 'https://www.seg-social.es';
    public $POST_URL = '';
    private $headers = [
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36 ',
        'Content-Type' => 'application/x-www-form-urlencoded'
    ];

    public function __construct()
    {
        $this->client_calendario_laboral = new Client(
            [
                'base_uri' => $this->URL_BASE,
                'cookies' => true,
                'timeout' => 10,
                'verify' => false
            ]
        );
    }

    public function getProvinces()
    {
        $response =  $this->client_calendario_laboral->request('GET', '/wps/portal/wss/internet/CalendarioLaboral', [
            'headers' => $this->headers
        ]);

        if ($response->getStatusCode() != 200) return [];

        $contents = $response->getBody()->getContents();

        $crawler = new Crawler($contents);


        $this->POST_URL = str_replace($this->URL_BASE, '', $crawler->filter('head > base')->attr('href'));
        $this->POST_URL .=  $crawler->filter('#provinciasLocalidades')->attr('action');

        $data = $crawler->filter('#Provincia > option');
        $data_provincias = [];
        $data_provincias = $data->each(function ($node) {
            $tmp_array = [];
            $tmp_array['ID'] =  $node->attr('value');
            $tmp_array['name'] = $node->text();
            return  $tmp_array;
        });

        unset($data_provincias[0]);
        return $data_provincias ?? [];
    }

    public function getLocations(array $provinces = [])
    {
        $locations_array = [];

        foreach ($provinces as $province) {
            $response =  $this->client_calendario_laboral->request('POST', $this->POST_URL, [
                'form_params' => [
                    'Provincia' => $province['ID']
                ]
            ]);

            if ($response->getStatusCode() != 200) return [];

            $contents = $response->getBody()->getContents();
            $crawler = new Crawler($contents);

            $this->POST_URL = str_replace($this->URL_BASE, '', $crawler->filter('head > base')->attr('href'));
            $this->POST_URL .=  $crawler->filter('#provinciasLocalidades')->attr('action');

            $data = $crawler->filter('#Localidades > option');
            $locations_array[$province['ID']] = $data->each(function ($node) {
                $tmp_array = [];
                $tmp_array['ID'] =  $node->attr('value');
                $tmp_array['name'] = $node->text();
                return  $tmp_array;
            });

            // file_put_contents("prueba.html", $contents);
        }
        return $locations_array;
    }

    public function getHolidays(string $province = '00', string $location = '000')
    {

        if (empty($this->POST_URL)) {
            $this->getProvinces();
            $this->getLocations([['ID' => $province]]);
        }

        $response =  $this->client_calendario_laboral->request('POST', $this->POST_URL, [
            'form_params' => [
                'Provincia' => $province,
                'Localidades' => $location
            ]
        ]);

        if ($response->getStatusCode() != 200) return [];

        $contents = $response->getBody()->getContents();

        $crawler = new Crawler($contents);

        // file_put_contents("prueba.html", $contents);

        $data = $crawler->filter('.table-responsive > table');

        $array_holidays = $data->each(function ($node) {
            $month = $node->filter('caption')->text();
            $tmp_array = [];
            $tmp_array[$month] = $node->filter('tr > td')->each(function ($node) {
                $day = $node->text();
                $holiday_description = $node->attr('aria-label');
                $holiday_type =  $this->clearString(['datepicker-day ', 'fest-auto ', ' ', 'fest-loc4'], $node->attr('class'));

                $tmp_array = [];

                $types_holiday = [
                    'public-holiday-loc' => 'local',
                    'public-holiday-nac' => 'national',
                    'public-holiday-auto' => 'regional'
                ];

                if (!empty($holiday_description)) {
                    $tmp_array['day'] = $day;
                    $tmp_array['type'] =  $types_holiday[$holiday_type];
                    $tmp_array['holiday'] = $this->clearString(['Festividad Autonómica:  ', 'Festividad Nacional:  ', 'Festividad Local: '], $holiday_description);
                }
                return $tmp_array;
            });

            return $tmp_array;
        });

        $array_holidays_return = [];
        foreach ($array_holidays as $mes) {
            foreach ($mes as $key => $data_mes) {
                foreach ($data_mes as $value) {
                    // if (count($value) > 0) $array_holidays_return[$key][] = $value;
                    if (count($value) > 0) {
                        $month_array = $this->month();
                        $day = str_pad($value['day'], 2, '0', STR_PAD_LEFT);
                        $month = $month_array[strtolower($key)];
                        $year = date('Y');
                        $value['day'] = ("{$year}-{$month}-{$day}");
                        $array_holidays_return[] = $value;
                    }
                }
            }
        }

        return $array_holidays_return;
    }

    private function clearString(array $search = [], string $string = '')
    {

        $string_replace = $string;
        foreach ($search as $value) {
            $string_replace = str_replace($value, '', $string_replace);
        }
        return $string_replace;
    }


    public function isHoliday($date = NULL, array $holidays = [])
    {
        $date = $date ?? date("Y-m-d");
        $input_date_check = new DateTime($date);

        foreach ($holidays as $holiday) {
            $tmp_date_check = new DateTime($holiday['day']);
            if ($tmp_date_check == $input_date_check) return true;
        }

        return false;
    }


    private function changeDayFormat($text, $year)
    {
        $month_array = $this->month();
        $array_text = explode(' ', $text);
        $day = str_pad($array_text[0], 2, '0', STR_PAD_LEFT);
        $month = $month_array[strtolower($array_text[1])];

        return ("{$year}-{$month}-{$day}");
    }

    private function month()
    {
        return [
            'enero' => '01',
            'febrero' => '02',
            'marzo' => '03',
            'abril' => '04',
            'mayo' => '05',
            'junio' => '06',
            'julio' => '07',
            'agosto' => '08',
            'septiembre' => '09',
            'octubre' => '10',
            'noviembre' => '11',
            'diciembre' => '12',
        ];
    }
}
