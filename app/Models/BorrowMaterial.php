<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class BorrowMaterial extends Model
{
    use HasFactory;
    protected $fillable = [
        
        'user_id',
        'accession',
        'borrow_date',
        'borrow_expiration',
        'fine',
        'status',
        

    ];

        public function material() {
            return $this->belongsTo(material::class, 'book_id', 'accession', 'title');
        }

        public function user(){
            return $this->belongsTo(User::class, 'user_id');
        }

        // public function program() {
        //     return $this->belongsTo(Program::class,  'program_short', 'department_short');
        // }

        public function student_program() {
            return $this->belongsTo(Program::class, 'program', 'program_short');
        }
}


