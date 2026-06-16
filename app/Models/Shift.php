<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'date', 'login_time', 'logoff_time'];

    // Relación con las actividades del turno (Probablemente ya la tienes)
    public function activities()
    {
        return $this->hasMany(ShiftActivity::class);
    }

    // NUEVA RELACIÓN: Un turno pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
