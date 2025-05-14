<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Classification;
use App\Models\Level;
use App\Services\RankingService;

class Group extends Model
{
    protected $rankingService;

    /** @use HasFactory<\Database\Factories\GroupFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'classification_id',
        'level_id',
        'parent_group_id',
    ];

    protected $hidden = ['deleted_at'];

    public function childrenOnly()
    {
        return $this->hasMany(Group::class, 'parent_group_id');
    }

    public function children()
    {
        return $this->hasMany(Group::class, 'parent_group_id')->with('children', 'classification', 'level');
    }

    public function parent()
    {
        return $this->belongsTo(Group::class, 'parent_group_id');
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function animals()
    {
        return $this->hasMany(Animal::class);
    }

    private function initialiseRankingService()
    {
        if (!$this->rankingService) {
            $this->rankingService = app(RankingService::class);
        }
    }

    public function getComboRank()
    {
        if ($this->classification_id && $this->level_id) {
            return ($this->getClassificationRank() * 100) + $this->getLevelRank();
        }

        return null;
    }

    public function getClassificationRank()
    {
        $this->initialiseRankingService();

        $classificationRanks = $this->rankingService->getClassificationRanks();

        return isset($classificationRanks[$this->classification_id]) ? $classificationRanks[$this->classification_id] : null;
    }

    public function getLevelRank()
    {
        $this->initialiseRankingService();

        $levelRanks = $this->rankingService->getLevelRanks();

        return isset($levelRanks[$this->level_id]) ? $levelRanks[$this->level_id] : null;
    }

    public function getYoungestRankedAncestor()
    {
        if ($this->classification_id && $this->level_id) {
            return $this;
        }

        if ($this->parent) {
            return $this->parent->getYoungestRankedAncestor();
        }

        return null;
    }

    public function getBestRankedDescendant()
    {
        if ($this->classification_id && $this->level_id) {
            return $this;
        }

        $bestDescendant = null;

        foreach ($this->children as $child) {
            $bestDescendantForChild = $child->getBestRankedDescendant();
            if ($bestDescendantForChild !== null) {
                if ($bestDescendant === null || $bestDescendantForChild->getComboRank() < $bestDescendant->getComboRank()) {
                    $bestDescendant = $bestDescendantForChild;
                }
            }
        }

        return $bestDescendant;
    }
}
