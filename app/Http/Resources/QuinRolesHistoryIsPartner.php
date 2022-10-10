<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuinRolesHistoryIsPartner extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $quinUser = $this->whenLoaded('connectedQuinUser');
        $role = $this->whenLoaded('connectedQuinRoles');

        $data = [
            'roles' => $this->roles
        ];

        if((array) $quinUser != []) {
            $data['key'] = $quinUser->users_key;
        }
        else {
            $data['key'] = null;
        }

        if((array) $role != []) {
            $data['primary'] = $role->primary_color;
            $data['secondary'] = $role->secondary_color;
        }
        else {
            $data['primary'] = null;
            $data['secondary'] = null;
        }

        return $data;
    }
}
