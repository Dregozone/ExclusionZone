<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Skill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key',
        'display_name',
        'description',
    ];

    public function actions(): HasMany
    {
        return $this->hasMany(CityAction::class, 'skill_key', 'key');
    }

    /**
     * The public path of this skill's picture, falling back to the default work scene.
     */
    public function imagePath(): string
    {
        return file_exists(public_path("images/work/{$this->key}.jpg"))
            ? "images/work/{$this->key}.jpg"
            : 'images/work/default.jpg';
    }
}
