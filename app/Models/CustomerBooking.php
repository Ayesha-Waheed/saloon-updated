<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerBooking extends Model
{
    use HasFactory;

    // Define the table name explicitly (Laravel will assume 'customer_bookings' by default, so this is optional)
    protected $table = 'customer_bookings';  

    // Set the primary key field if it’s not the default 'id'
    protected $primaryKey = 'booking_id';  // 'booking_id' is the primary key in your table

    // Specify which fields are fillable (allow mass assignment)
    protected $fillable = [
        'csID', 
        'booking_date', 
        'start_time', 
        'end_time'
    ];

    // Define the relationship with the 'Customer' model
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'csID');  // 'csID' is the foreign key to the 'customers' table
    }

    // Optional: Define additional relationships if there are other related tables
}
