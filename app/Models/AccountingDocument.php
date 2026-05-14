<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vendor;
class AccountingDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        // 'user_id', // Foreign key to link the document to a user (customer/vendor)
        'type',    // Type of document (e.g., invoice, receipt, contract)
        'path',    // Path to the stored document file
        'name',    // Original name of the document file
        // Any other fields you want to include
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
