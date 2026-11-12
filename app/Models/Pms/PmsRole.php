<?php

namespace App\Models\Pms;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PmsRole extends Model
{
    protected $fillable = ['code', 'name', 'description'];

    public function categories(): HasMany
    {
        return $this->hasMany(PmsCategory::class)->orderBy('sort_order');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(PmsPlan::class);
    }
}
