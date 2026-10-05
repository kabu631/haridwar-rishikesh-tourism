<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Manifest of a public image: intrinsic size (prevents layout shift) and the
 * optimised WebP/AVIF variants generated for it.
 */
#[Fillable(['path', 'width', 'height', 'bytes', 'variants', 'alt'])]
class Media extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'bytes' => 'integer',
            'variants' => 'array',
        ];
    }
}
