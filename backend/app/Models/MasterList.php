<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A shared suggestion row: allergy | disease | visit_reason. */
class MasterList extends Model
{
    public const TYPES = ['allergy', 'disease', 'visit_reason'];

    protected $fillable = ['type', 'name'];
}
