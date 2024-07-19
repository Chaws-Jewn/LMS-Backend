<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Program;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'username',
        'role',
        'password',
        'first_name',
        'middle_name',
        'last_name',
        'ext_name',
        'program_id',
        'gender'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'access' => 'array',
        'roles' => 'array',
    ];

    /**
     * Retrieve the username column name.
     *
     * @return string
     */
    public function username(): string
    {
        return 'username';
    }

    // Find user by username
    public function findForIdentifier($username)
    {
        return $this->where('username', $username)->first();
    }

    // Check if user has a certain role
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function logs() {
        return $this->hasMany(CatalogingLog::class);
    }

    public function student_program() {
        return $this->belongsTo(Program::class, 'program', 'program_short');
    }

    public function patron(){
        return $this->belongsTo(Patron::class);
    }
    
    public function getRolesAttribute($value)
    {
        return json_decode($value, true);
    }

    public function program() {
        return $this->belongsTo(Program::class, 'program', 'program_short');
    }
}
