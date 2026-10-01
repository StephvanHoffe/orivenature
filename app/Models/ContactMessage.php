<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['data' => 'array', 'handled_at' => 'datetime'];

    public const TYPES = ['contact' => 'Contactformulier', 'wholesale' => 'Retailer-aanvraag'];
}
