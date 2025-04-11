<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'duration', 'cID'];

    public function category()
    {
        return $this->belongsTo(Category::class, 'cID');
    }

    public function bookings()
    {
        return $this->hasMany(CustomerBookingService::class);
    }
}

