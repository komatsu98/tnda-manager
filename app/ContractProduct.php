<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContractProduct extends Model
{
    protected $table = 'contract_products';
    protected $fillable = ['contract_id', 'product_code', 'premium', 'premium_term', 'premium_received', 'term_code', 'confirmation'];
    protected $casts = [
    ];

    /**
     * The user that history belonged to.
     */
    public function contract()
    {
        return $this->belongsTo('App\Contract', 'contract_id', 'id');
    }
}
