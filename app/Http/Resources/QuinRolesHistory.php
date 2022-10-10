<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuinRolesHistory extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $role = $this->whenLoaded('connectedQuinRoles');

        $data = [];

        if((array) $role != []) {
            $data['role'] = $role->name;
        }
        else {
            $data['role'] = null;
        }

        return $data;
    }
}
