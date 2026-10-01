<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['data' => 'array', 'handled_at' => 'datetime'];

    public const TYPES = ['contact' => 'Contactformulier', 'wholesale' => 'Retailer-aanvraag', 'order' => 'Vraag over bestelling'];

    /** Extra velden om te tonen (zonder interne sleutels). */
    public function extraFields(): array
    {
        return collect($this->data ?? [])->except(['order_id'])->all();
    }
}
