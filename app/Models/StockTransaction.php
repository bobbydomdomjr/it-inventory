<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToOffice;

class StockTransaction extends Model { use BelongsToOffice; protected $guarded=[];protected function casts():array{return ['transaction_date'=>'date'];} public function item(){return $this->belongsTo(InventoryItem::class,'inventory_item_id');} public function user(){return $this->belongsTo(User::class);}}
