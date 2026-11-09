<?php

namespace App\Models;

use Database\Factories\RestaurantTableFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\URL;

class RestaurantTable extends Model
{
    /** @use HasFactory<RestaurantTableFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'table_number',
        'qr_token',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'vacant',
    ];

    /**
     * Orders placed at this restaurant table.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Current ongoing (unsettled, non-cancelled) order for this table.
     *
     * @return HasOne<Order, $this>
     */
    public function activeOrder(): HasOne
    {
        return $this->hasOne(Order::class)
            ->whereNotIn('order_status', ['settled', 'cancelled'])
            ->latestOfMany();
    }

    /**
     * Scope for vacant tables.
     *
     * @param  Builder<RestaurantTable>  $query
     * @return Builder<RestaurantTable>
     */
    public function scopeVacant(Builder $query): Builder
    {
        return $query->where('status', 'vacant');
    }

    /**
     * Scope for occupied tables.
     *
     * @param  Builder<RestaurantTable>  $query
     * @return Builder<RestaurantTable>
     */
    public function scopeOccupied(Builder $query): Builder
    {
        return $query->where('status', 'occupied');
    }

    /**
     * Generate the tamper-proof signed QR entry URL for this table.
     */
    public function signedQrUrl(): string
    {
        return URL::signedRoute('table.entry', ['qr_token' => $this->qr_token]);
    }

    /**
     * Generate a LAN-reachable signed QR URL so physical smartphones on Wi-Fi
     * can scan the QR code even when the dashboard is viewed on localhost/127.0.0.1.
     */
    public function scannableQrUrl(): string
    {
        $currentRoot = request()->getSchemeAndHttpHost();

        if (in_array(request()->getHost(), ['127.0.0.1', 'localhost'], true)) {
            $lanUrl = rtrim((string) config('hotel.lan_url', $currentRoot), '/');
            URL::forceRootUrl($lanUrl);
            $signed = URL::signedRoute('table.entry', ['qr_token' => $this->qr_token]);
            URL::forceRootUrl($currentRoot);

            return $signed;
        }

        return $this->signedQrUrl();
    }
}
