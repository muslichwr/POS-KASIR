<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Supplier extends Model
{
    protected $fillable = ['name', 'contact_info'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    protected $appends = ['contact_info_formatted'];

    public function getContactInfoFormattedAttribute(): string
    {
        return collect(explode("\n", $this->contact_info))
            ->map(function ($line) {
                $trimmedLine = trim($line);
                
                // Auto-convert URLs to markdown links
                if (Str::startsWith($trimmedLine, ['http://', 'https://'])) {
                    return "[{$trimmedLine}]({$trimmedLine})";
                }
                
                // Auto-convert emails to mailto links
                if (filter_var($trimmedLine, FILTER_VALIDATE_EMAIL)) {
                    return "[{$trimmedLine}](mailto:{$trimmedLine})";
                }
                
                // Format phone numbers
                if (preg_match('/^(\+\d{1,3}[\s-]?)?\(?\d{3}\)?[\s-]?\d{3}[\s-]?\d{4}$/', $trimmedLine)) {
                    return "📞 {$trimmedLine}";
                }
                
                return $trimmedLine;
            })
            ->join("\n\n");
    }
}
