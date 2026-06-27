<?php

namespace App\Services;

use App\Models\KanbanColumn;
use App\Repositories\EndorsementRepository;
use App\Repositories\KanbanColumnRepository;
use Illuminate\Database\Eloquent\Collection;

class KanbanColumnService
{
    public function __construct(
        private readonly KanbanColumnRepository $repo,
        private readonly EndorsementRepository $endorsementRepo,
    ) {}

    public function getOrSeed(int $userId): Collection
    {
        return $this->repo->getOrSeed($userId);
    }

    /** [slug => name] untuk statusOptions di form. */
    public function optionsFor(int $userId): array
    {
        return $this->repo->getOrSeed($userId)->pluck('name', 'slug')->all();
    }

    public function create(int $userId, string $name): KanbanColumn
    {
        $count = $this->repo->forUser($userId)->count();

        return $this->repo->create($userId, $name, $count);
    }

    public function rename(int $userId, string $slug, string $name): ?KanbanColumn
    {
        $col = $this->repo->findBySlug($userId, $slug);
        if (! $col) {
            return null;
        }

        return $this->repo->rename($col, $name);
    }

    public function reorder(int $userId, array $orderedSlugs): void
    {
        $this->repo->reorder($userId, $orderedSlugs);
    }

    public function delete(int $userId, string $slug): bool
    {
        $col = $this->repo->findBySlug($userId, $slug);
        if (! $col) {
            return false;
        }

        // Pindahkan endorsement dari kolom yang dihapus ke kolom pertama yang tersisa.
        $other = $this->repo->forUser($userId)->first(fn ($c) => $c->slug !== $slug);
        if ($other) {
            $this->endorsementRepo->reassignStatus($userId, $slug, $other->slug);
        }

        $this->repo->delete($col);

        return true;
    }
}
