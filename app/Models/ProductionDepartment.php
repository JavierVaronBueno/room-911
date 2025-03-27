<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionDepartment extends Model
{
    use HasFactory;
    public $table = 'production_departments';
    protected $fillable = array('*');

    // Relationship: A department has many employees
    public function employees()
    {
        return $this->hasMany(Employee::class, 'production_department_id');
    }
}
