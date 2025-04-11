<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpCategory extends Model
{
    use HasFactory;

    protected $table = 'sp_category'; // You can specify the table name here if it differs
    public $timestamps = false; // Since this is a pivot table, we typically don't need timestamps

    protected $fillable = ['spID', 'cID'];
}
