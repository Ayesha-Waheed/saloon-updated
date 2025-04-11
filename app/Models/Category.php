<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function serviceProviders()
    {
        return $this->belongsToMany(ServiceProvider::class, 'sp_category');
    }
}
