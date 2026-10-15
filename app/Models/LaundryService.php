<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LaundryService extends Model { protected $fillable = ['name','pricing_mode','unit_price','estimated_days','description','status']; protected function casts(): array { return ['unit_price'=>'decimal:2']; } }
