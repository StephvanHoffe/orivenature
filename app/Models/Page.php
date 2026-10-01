<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_published' => 'boolean'];

    public const TEMPLATES = ['default' => 'Standaard', 'contact' => 'Met contactformulier', 'wholesale' => 'Met aanvraagformulier voor retailers'];

    public function url(): string
    {
        return '/pages/'.$this->handle;
    }
}
