<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    
    protected $fillable = ['user_id', 'phone_number', 'address', 'joining_date', 'date_of_birth', 'gender', 'profile_picture', 'full_name'];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
