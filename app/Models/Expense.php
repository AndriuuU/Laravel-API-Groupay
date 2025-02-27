<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'amount',
        'date',
        'category',
        'group_id',
        'paid_by'
    ];

    protected $casts = [
        'amount' => 'float',
        'date' => 'date'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'expense_user');
    }
}
