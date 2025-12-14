<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
