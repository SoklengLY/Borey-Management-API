<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserinfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'username' => $this->username,
            'fullname' => $this->fullname,
            'email' => $this->email,
            'path' => $this->path,
            'dob' => $this->dob,
            'gender' => $this->gender,
            'phonenumber' => $this->phonenumber,
            'house_type' => $this->house_type,
            'house_number' => $this->house_number,
            'street_number' => $this->street_number,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
