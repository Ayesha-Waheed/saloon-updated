<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerBookingService extends Model
{
    use HasFactory;

    // Define the table name explicitly
    protected $table = 'customer_bookings_services';  // Name of the table

    // Set the primary key field if it’s not the default 'id'
    protected $primaryKey = 'bID';  // bID is the primary key in your table

    // Specify which fields are fillable (allow mass assignment)
    protected $fillable = [
        'spID', 
        'sID', 
        'booking_date', 
        'start_time', 
        'end_time', 
        'booking_id',
        'saloon_id'
    ];

    // Define relationships if needed
    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'spID');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'sID');
    }

    public function customerBooking()
    {
        return $this->belongsTo(CustomerBooking::class, 'booking_id');
    }
}
