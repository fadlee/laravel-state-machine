<?php

namespace App\Models;

use App\Traits\HasStateTransitions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;
    use HasStateTransitions;

    protected $fillable = [
        'title',
        'status',
        'verification_status',
    ];
}
