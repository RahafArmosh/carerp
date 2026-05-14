<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'user_id',
        'pipeline_id',
        'stage_id',
        'sources',
        'products',
        'notes',
        'labels',
        'order',
        'created_by',
        'is_active',
        'date',
        'gclid',
        'lead_id',
        'message',
        'source',
        'source_url',
        'whatsapp',
        'lead_name',
        'country',
        'quantity',
        'payment',
    ];
    /**
     * Parse the payment value from the message column.
     * Looks for 'Payment:' and returns the value after it (until next space or end of string).
     * Example: 'Payment: L/C' => 'L/C'
     */
    public function parsePaymentFromMessage()
    {
        $message = $this->message ?? '';
        if (preg_match('/Payment:\s*([^\n\r]+)/i', $message, $matches)) {
            // Get the value after 'Payment:' up to the next field or end of line
            $value = trim($matches[1]);
            // Optionally, stop at next field (e.g., if more fields follow)
            $value = preg_split('/\s+(?=\w+:)/', $value)[0];
            return $value;
        }
        return null;
    }
    // public static function booted()
    // {
    //     static::saving(function ($lead) {
    //         $lead->quantity = $lead->parseQuantityFromMessage();
    //     });
    // }

    public function labels()
    {
        if ($this->labels) {
            return Label::whereIn('id', explode(',', $this->labels))->get();
        }

        return false;
    }

    public function stage()
    {
        return $this->hasOne('App\Models\LeadStage', 'id', 'stage_id');
    }


    public function files()
    {
        return $this->hasMany('App\Models\LeadFile', 'lead_id', 'id');
    }

    public function pipeline()
    {
        return $this->hasOne('App\Models\Pipeline', 'id', 'pipeline_id');
    }

    public function products()
    {
        if ($this->products) {
            return ProductService::whereIn('id', explode(',', $this->products))->get();
        }

        return [];
    }

    public function sources()
    {
        if ($this->sources) {
            return Source::whereIn('id', explode(',', $this->sources))->get();
        }

        return collect(); // return empty collection instead of []
    }

    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_leads', 'lead_id', 'user_id');
    }

    public function activities()
    {
        return $this->hasMany('App\Models\LeadActivityLog', 'lead_id', 'id')->orderBy('id', 'desc');
    }

    public function discussions()
    {
        return $this->hasMany('App\Models\LeadDiscussion', 'lead_id', 'id')->orderBy('id', 'desc');
    }

    public function calls()
    {
        return $this->hasMany('App\Models\LeadCall', 'lead_id', 'id');
    }

    public function emails()
    {
        return $this->hasMany('App\Models\LeadEmail', 'lead_id', 'id')->orderByDesc('id');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function leadProducts()
    {
        return $this->hasMany(LeadProduct::class);
    }



    public function parseQuantityFromMessage()
    {
        $message = strtolower($this->message ?? '');
    
        // ✅ Natural phrase match - English
        if (preg_match('/how many (units|unit|vehicles|trucks|buses)?[^:]*:\s*(\d+)/i', $message, $matches)) {
            return (int) $matches[2];
        }
    
        // ✅ Natural phrase match - French (e.g., "Combien D’unités Recherchez-vous : 1000")
        if (preg_match('/combien d[’\'`]?(unités|unites|camions|véhicules)[^:]*:\s*(\d+)/iu', $message, $matches)) {
            return (int) $matches[2];
        }
    
        // ✅ Natural phrase match - Arabic (e.g., "كم عدد الوحدات المطلوبة: 3")
        if (preg_match('/كم[^:]{0,20}[:：]\s*(\d+)/u', $message, $matches)) {
            return (int) $matches[1];
        }
    
        // ✅ Fallback: keyword-based detection in various languages
        $keywords = [
            // English
            'qty', 'quantity', 'unit', 'units',
            // French
            'qté', 'quantité', 'unité', 'unités',
            // Portuguese
            'qtd', 'quantidade', 'unidade',
            // Arabic
            'كمية', 'عدد', 'وحدة',
        ];
    
        $keywordsPattern = implode('|', array_map('preg_quote', $keywords));
    
        if (preg_match('/(' . $keywordsPattern . ')[^\d]{0,10}(\d+)/iu', $message, $matches)) {
            return (int) $matches[2];
        }
    
        return null;
    }
    

    /**
     * Get the display value for quantity (number or 'bulk')
     */
    public function getDisplayQuantityAttribute()
    {
        if ($this->quantity === null) {
            return '-';
        }
        // Accept both string and int for 1 and 2
        $q = strtolower(trim($this->quantity));
        if ($q === '1' || $q === '2' || $q === 1 || $q === 2) {
            return $q;
        }
        return 'bulk';
    }
}
