<?php
// app/Services/GeocodingService.php
namespace App\Services;

use GuzzleHttp\Client;

class GeocodingService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = 'AIzaSyChJsgIE5QHkcMRvLHKFkQ7JQk0BVZNs_o';
    }

    public function getAddress($latitude, $longitude)
    {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitude},{$longitude}&key={$this->apiKey}";

        $response = $this->client->get($url);
        $data = json_decode($response->getBody(), true);

        if ($data['status'] == 'OK' && isset($data['results'][0]['address_components'])) {
            $addressComponents = $data['results'][0]['address_components'];
            $formattedAddressParts = [];

            foreach ($addressComponents as $component) {
                // Check if the component is not of type 'country'
                if (!in_array('country', $component['types'])) {
                    $formattedAddressParts[] = $component['long_name'];
                }
            }

            return implode(', ', $formattedAddressParts);
        }

        return 'Address not found';
    }
}
