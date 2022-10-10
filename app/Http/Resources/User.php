<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\QuinRolesHistory as QuinRolesHistoryResource;

class User extends JsonResource
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
        $histories = $this->whenLoaded('connectedQuinRolesHistory');

        $data = [
            'email' => $this->user_email
        ];

        if((array) $quinUser != []) {
            $data['key'] = $quinUser->users_key;
        }
        else {
            $data['key'] = null;
        }

        if((array) $histories != []) {
            $data['roles'] = QuinRolesHistoryResource::collection($histories);
        }
        else {
            $data['roles'] = [];
        }


        return $data;
    }
}
