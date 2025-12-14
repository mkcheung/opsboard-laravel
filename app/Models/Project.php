<?php

namespace App\Models;

use App\Models\User;
use App\Models\Task;
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
    public function task(){
        return $this->hasMany(Task::class);
    }
}
