<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StateTransitionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_type',
        'model_id',
        'state_transition_id',
        'status_field',
        'transition_name',
        'from_state',
        'to_state',
    ];

    protected static function booted()
    {
        static::creating(function (self $model) {
            $model->user_id = auth()->id();
            $model->state_transition_id = StateTransition::getTransition($model->model_type, $model->transition_name, $model->status_field)->id;
        });
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function stateTransition(): BelongsTo
    {
        return $this->belongsTo(StateTransition::class);
    }
}
