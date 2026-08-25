<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashFlowForecast extends Model
{
    protected $fillable = [
        'forecast_date', 'type', 'source', 'source_id',
        'description', 'amount', 'status',
    ];

    protected $casts = [
        'amount' => 'integer',
        'forecast_date' => 'date',
    ];

    public function sourceable(): MorphTo
    {
        return $this->morphTo('source', 'source_id');
    }

    public function scopeInflow($q) { return $q->where('type', 'inflow'); }
    public function scopeOutflow($q) { return $q->where('type', 'outflow'); }
    public function scopeProjected($q) { return $q->where('status', 'projected'); }
    public function scopeConfirmed($q) { return $q->where('status', 'confirmed'); }
    public function scopeBetweenDates($q, $from, $to) {
        return $q->whereBetween('forecast_date', [$from, $to]);
    }
}