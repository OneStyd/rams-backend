<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeoJSONResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "type" => "Feature",
            "properties" => $this->makeHidden('geojson'),
            "geometry" => json_decode($this->geojson),
        ];
    }
}
