<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontEvent extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = ['date' => 'date'];
}
