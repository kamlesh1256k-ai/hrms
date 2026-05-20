<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitionApproval extends Model
{
    protected $table = 'recruitment_requisition_approvals';

    protected $fillable = [
        'requisition_id',
        'actor_user_id',
        'actor_role',
        'action',
        'comments',
        'created_by',
    ];

    public function requisition()
    {
        return $this->belongsTo(ManpowerRequisition::class, 'requisition_id');
    }

    public function actor()
    {
        return $this->belongsTo(\App\Models\User::class, 'actor_user_id');
    }
}
