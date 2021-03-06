<?php

namespace AppBundle\Services;

/**
* Service that provide access to the Google Maps API
*/
class MapsApiService {

    /**
    * @var string
    */
    protected $key;

    /**
    * @var string
    */
    protected  $api = "https://maps.googleapis.com/maps/api";

    /**
    * @var string
    */
    protected $request;


    public function __construct($googleApiKey) {
        $this->key = $googleApiKey;
    }


    /**
     * Build request URL
     * @param $action
     * @param array $params
     */
    private function build($action, $params=array()) {

        $url = $this->api. '/' . $action . "?key=". $this->key;

        foreach ($params as $key => $value) {
            if(is_string($value)) {
                $url .= '&'. $key . '=' . urlencode($value);
            } elseif (is_array($value)) {
                foreach ($value as $v) {
                    $url .= '&'. $key . '[]=' . urlencode($v);
                }
            }
        }


        $this->request = $url;
    }

    /**
     * Exec request URL
     * @return mixed
     * @throws \Exception
     */
    private function exec() {
        $curl = curl_init($this->request);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $res = curl_exec($curl);

        if(curl_errno($curl)){
            throw new \Exception(curl_error($curl));
        }

        curl_close($curl);

        return $res;
    }

    /**
     * @param $origin
     * @param $destinations
     * @return mixed
     * @throws \Exception
     */
    public function getDistanceMatrix($origin, $destinations) {

        $destinations = implode('|', $destinations);

        $params = array(
            "origins" => $origin,
            "destinations" => $destinations,
            "units" => "metric",
        );

        $this->build('distancematrix/json', $params);

        $response = $this->exec($this->request);

        return json_decode(trim($response), true);
    }

    /**
     * @param $origin
     * @param $destinations
     * @param int $distance
     * @return array
     * @throws \Exception
     */
    public function getCitiesByMaxDistance($origin, $destinations, $distance = 50000) {

        $response = $this->getDistanceMatrix($origin, $destinations);

        $data = array();

        foreach ($response['rows'][0]['elements'] as $key => $destination) {
            if(array_key_exists("distance",$destination)) {
                if($destination['distance']['value'] <= $distance) {
                    $result = explode(',',$response['destination_addresses'][$key]);
                    $data["max_distance"][$distance][] = array(
                        "city" => trim(preg_replace("/[0-9]+(.*)/", "$1", $result[0])),
                        "country" => trim($result[1]),
                        "distance" => $destination['distance']['value'],
                    );
                }
            }
        }


        if(isset($data['max_distance'][$distance]) && !empty($data['max_distance'][$distance])) {
            usort($data['max_distance'][$distance], function($a, $b) {
                return $a['distance'] - $b['distance'];
            });
        }

        return $data;
    }

}
