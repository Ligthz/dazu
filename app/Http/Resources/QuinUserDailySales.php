<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\QuinRolesHistory as QuinRolesHistoryResources;

class QuinUserDailySales extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $avatar = $this->whenLoaded('connectedFile');
        $roles = $this->whenLoaded('connectedQuinRolesHistories');
        $personalSales = $this->whenLoaded('connectedPersonalSales');

        $groupOverview = $this->whenLoaded('connectedGroupOverview');

        $data = [
            'referral_code' => $this->referral_code,
            'first_name' => $this->fname,
            'last_name' => $this->lname,
            'phone' => $this->contact,
            'joined_at' => $this->partner_joined_at,
            'mentor' => $this->mentor_id
        ];

        if((array) $avatar != []) {
            $data['avatar_name'] = $avatar->name;
            $data['avatar_path'] = asset('/uploads/' . $avatar->path);
        }
        else {
            $data['avatar_name'] = null;
            $data['avatar_path'] = null;
        }

        if((array) $roles != []) {
            $roleRecords = QuinRolesHistoryResources::collection($roles);
            $roleRecord = $roleRecords->sortByDesc('created_at')->take(1);
            $data['roles'] = $roleRecord[0];
        }
        else {
            $data['roles'] = null;
        }

        if((array) $personalSales != []) {
            $sales = [
                'personal' => $personalSales->sum('personal_sales'),
                'referral' => $personalSales->sum('referral_sales')
            ];

            $data['personal_sales'] = $sales;
        }
        else {
            $data['personal_sales'] = 0;
        }

        if((array) $groupOverview != []) {
            $groupRecord = $groupOverview->sortByDesc('date')->take(1);

            $group = [
                'num_of_ba' => $groupRecord[0]->grp_ba_count,
                'ba_sales' => $groupRecord[0]->grp_ba_sales,
                'num_of_be' => $groupRecord[0]->grp_be_count,
                'be_sales' => $groupRecord[0]->grp_be_sales,
                'num_of_bm' => $groupRecord[0]->grp_bm_count,
                'bm_sales' => $groupRecord[0]->grp_bm_sales
            ];

            $data['group_overview'] = $group;
        }
        else {
            $data['group_overview'] = null;
        }

        return $data;
    }
}
