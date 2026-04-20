<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryOut extends Model
{
    protected $fillable = ['barang_id', 'qty'];
}
