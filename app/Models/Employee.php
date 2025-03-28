<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    public $table = 'employees';
    protected $fillable = [
        'photo_path',
        'internal_id',
        'first_name',
        'last_name',
        'production_department_id',
        'has_room_911_access',
    ];

    // Relationship: An employee belongs to a department
    public function productionDepartment()
    {
        return $this->belongsTo(ProductionDepartment::class, 'production_department_id');
    }

    // Relationship: One employee has many access attempts
    public function accessAttempts()
    {
        return $this->hasMany(AccessAttempt::class, 'employee_id');
    }
}
