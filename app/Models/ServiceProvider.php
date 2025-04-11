<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProvider extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'shift_start', 'shift_end', 'is_present_today'];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'sp_category');
    }

    public function bookings()
    {
        return $this->hasMany(CustomerBookingService::class);
    }
}
