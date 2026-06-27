<?php

namespace App\Repositories;

use App\Models\Endorsement;
use App\Models\KanbanColumn;
use Illuminate\Database\Eloquent\Collection;

class KanbanColumnRepository
{
    private const ACCENTS = ['#6366f1', '#0ea5e9', '#a855f7', '#f59e0b', '#10b981', '#ec4899', '#14b8a6', '#ef4444'];

    public function forUser(int $userId): Collection
    {
        return KanbanColumn::where('user_id', $userId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function getOrSeed(int $userId): Collection
    {
        $cols = $this->forUser($userId);

        return $cols->isNotEmpty() ? $cols : $this->seed($userId);
    }

    public function seed(int $userId): Collection
    {
        $i = 0;
        foreach (Endorsement::STATUS_OPTIONS as $slug => $name) {
            KanbanColumn::create([
                'user_id' => $userId,
                'slug' => $slug,
                'name' => $name,
                'accent' => self::ACCENTS[$i % count(self::ACCENTS)],
                'position' => $i,
            ]);
            $i++;
        }

        return $this->forUser($userId);
    }

    public function create(int $userId, string $name, int $position): KanbanColumn
    {
        $slug = 'col_'.substr(md5(uniqid((string) $userId, true)), 0, 8);
        $accent = self::ACCENTS[$position % count(self::ACCENTS)];

        return KanbanColumn::create([
            'user_id' => $userId,
            'slug' => $slug,
            'name' => $name,
            'accent' => $accent,
            'position' => $position,
        ]);
    }

    public function rename(KanbanColumn $col, string $name): KanbanColumn
    {
        $col->update(['name' => $name]);

        return $col;
    }

    public function reorder(int $userId, array $orderedSlugs): void
    {
        foreach ($orderedSlugs as $pos => $slug) {
            KanbanColumn::where('user_id', $userId)
                ->where('slug', $slug)
                ->update(['position' => $pos]);
        }
    }

    public function delete(KanbanColumn $col): void
    {
        $col->delete();
    }

    public function findBySlug(int $userId, string $slug): ?KanbanColumn
    {
        return KanbanColumn::where('user_id', $userId)->where('slug', $slug)->first();
    }
}
