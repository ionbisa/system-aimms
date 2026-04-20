<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryIn extends Model
{
    protected $table = 'inventory_ins'; // SESUAIKAN NAMA TABEL
    protected $fillable = ['barang_id', 'qty'];
}
