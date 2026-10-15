<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LaundryOrder extends Model { protected $fillable = ['customer_id','garment_type_id','laundry_service_id','received_by','quantity','unit_price','total','estimated_at','completed_at','status','notes']; protected function casts(): array { return ['quantity'=>'decimal:2','unit_price'=>'decimal:2','total'=>'decimal:2','estimated_at'=>'datetime','completed_at'=>'datetime']; } public function customer(): BelongsTo { return $this->belongsTo(Customer::class); } public function garmentType(): BelongsTo { return $this->belongsTo(GarmentType::class); } public function laundryService(): BelongsTo { return $this->belongsTo(LaundryService::class); } public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); } }
