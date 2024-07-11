<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class paginationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function __construct($data,$total)
    {
        $this->data = $data;
    }
    public function toArray($request)
    {
        return [
            $this->data,
            // 'total' => $this->total(),
            // 'count' => $this->count(),
            // 'per_page' => $this->perPage(),
            // 'current_page' => $this->currentPage(),
            // 'total_pages' => $this->lastPage(),
        ];
    }
}
