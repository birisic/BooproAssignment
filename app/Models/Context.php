<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Context extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'owner_username', 'type', 'provider_id'];
}
