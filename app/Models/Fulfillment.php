<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fulfillment extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['items' => 'array', 'notify_customer' => 'boolean', 'shipped_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function trackingLink(): ?string
    {
        if ($this->tracking_url) {
            return $this->tracking_url;
        }
        if (! $this->tracking_number) {
            return null;
        }
        $zip = urlencode((string) ($this->order->shipping_address['zip'] ?? ''));
        $country = $this->order->shipping_address['country_code'] ?? 'NL';
        $number = urlencode($this->tracking_number);

        return match (strtolower((string) $this->tracking_company)) {
            'postnl' => "https://jouw.postnl.nl/track-and-trace/{$number}-{$country}-{$zip}",
            'dhl', 'dhl parcel' => "https://my.dhlecommerce.nl/home/tracktrace/{$number}/{$zip}",
            'dpd' => "https://tracking.dpd.de/status/nl_NL/parcel/{$number}",
            'gls' => "https://gls-group.com/NL/nl/volg-je-pakket?match={$number}",
            'ups' => "https://www.ups.com/track?tracknum={$number}",
            default => null,
        };
    }
}
