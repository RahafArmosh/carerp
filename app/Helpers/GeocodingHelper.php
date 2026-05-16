<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class GeocodingHelper
{
    private static function getGeocodingData($latitude, $longitude)
    {
        $apiKey = 'AIzaSyChJsgIE5QHkcMRvLHKFkQ7JQk0BVZNs_o'; // Make sure to set your API key in .env file
        $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
            'latlng' => "{$latitude},{$longitude}",
            'key' => $apiKey,
        ]);
        return $response->json();
    }

    public static function getLocationName($latitude, $longitude)
    {
        $data = self::getGeocodingData($latitude, $longitude);
        if ($data['status'] === 'OK' && isset($data['results'][0]['address_components'])) {
            $addressComponents = $data['results'][0]['address_components'];
            $formattedAddressParts = [];
            foreach ($addressComponents as $component) {
                if (!in_array('country', $component['types'])) {
                    // Add to the formatted address if it's not a country
                    $formattedAddressParts[] = $component['long_name'];
                }
            }

            return implode(', ', $formattedAddressParts);
        }

        return 'Location not found';
    }
}
