<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 
        'price', 
        'image_url', 
        'platform',
        'source_url',
        'product_id'
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
    ];
}
