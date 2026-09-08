<?php

namespace App\Http\Controllers;


use App\Models\WeightLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller;

class FetchWeightLocationController extends Controller
{
    public function getWeightLocationData($godownLocationId)
    {
        $ipAddress = WeightLocation::where('id', $godownLocationId)->value('ip_address');

        if (!$ipAddress) {
            return response()->json(['error' => 'IP Address not found'], 404);
        }

        // Direct URL to weight machine
        $url = "http://{$ipAddress}:8732/WEIGHT/";

        try {
            $response = Http::timeout(5)->withoutVerifying()->get($url);

            if ($response->successful()) {

                // Weight machine error detect
                if (str_contains($response->body(), "No route to host")) {
                    return response()->json(['error' => 'Machine Not Reachable'], 404);
                }

                // XML parse
                $xml = new \SimpleXMLElement($response->body());
                $value = (string) $xml;

                return response()->json(['value' => $value]);
            }

            return response()->json(['error' => 'API request failed'], 404);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Machine not responding'], 500);
        }
    }

}