<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchProvider extends Model
{
    use HasFactory;

    protected $table = 'providers';
    protected $fillable = ['name'];

    public static function getProviderId(string $providerName): int {
        return SearchProvider::where("name", $providerName)->value("id");
    }
}
