<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessAttempt extends Model
{
    use HasFactory;

    public $table = 'access_attempts';
    protected $fillable = array('*');

    protected $casts = [
        'access_granted' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    // Relación: Un intento de acceso pertenece a un empleado (puede ser null)
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

}
