<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StructureComponent extends Model
{
    protected $table = 'structure_components';

    protected $fillable = [
        'structure_id',
        'component_id',
        'priority',
    ];
}

