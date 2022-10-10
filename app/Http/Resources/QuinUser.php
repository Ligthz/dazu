<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\QuinRolesHistory as QuinRolesHistoryResource;

class QuinUser extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = $this->whenLoaded('connectedUsers');
        $avatar = $this->whenLoaded('connectedFile');
        $bank = $this->whenLoaded('connectedBank');
        $roleHistories = $this->whenLoaded('connectedQuinRolesHistories');

        $data = [
            'referral_code' => $this->referral_code,
            'bank_name' => $this->bank_account_name,
            'bank_account' => maskNumber($this->bank_account_no),
            'address' => $this->address,
            'first_name' => $this->fname,
            'last_name' => $this->lname,
            'ic' => $this->ic_passport_no,
            'dob' => $this->dob,
            'phone' => $this->contact,
            'gender' => $this->gender,
            'race' => $this->race,
            'marital_status' => $this->marital_status,
            'joined_at' => $this->partner_joined_at
        ];

        if((array) $bank != []) {
            $data['bank'] = $bank->bank_name;
            $data['bank_id'] = $bank->bank_id;
        }
        else {
            $data['bank'] = null;
            $data['bank_id'] = null;
        }

        if((array) $user != []) {
            $data['email'] = $user->user_email;
            $data['username'] = $user->user_login;
        }
        else {
            $data['email'] = null;
            $data['username'] = null;
        }

        if((array) $avatar != []) {
            $data['avatar_name'] = $avatar->name;
            $data['avatar_path'] = asset('/uploads/' . $avatar->path);
        }
        else {
            $data['avatar_name'] = null;
            $data['avatar_path'] = null;
        }

        if((array) $roleHistories != []) {
            //$sortedRoleHistories = $roleHistories->sortDesc('created_at');

            //$data['role'] = new QuinRolesHistoryResource($sortedRoleHistories[0]);
            $data['role'] = QuinRolesHistoryResource::collection($roleHistories)->sortBy(['created_at', 'desc'])[sizeof($roleHistories) - 1];
        }
        else {
            $data['role'] = null;
        }



        return $data;
    }
}
