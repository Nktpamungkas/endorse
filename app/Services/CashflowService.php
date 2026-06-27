<?php

namespace App\Services;

use App\Repositories\CashflowRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Logika bersama Pemasukan & Pengeluaran. Subclass menyuntik repository spesifiknya.
 */
abstract class CashflowService
{
    public function __construct(protected readonly CashflowRepository $repo) {}

    public function indexData(int $userId, string $keyword, int $perPage, ?int $editId): array
    {
        $editing = $editId ? $this->repo->findForUser($userId, $editId) : null;

        return [
            'items' => $this->repo->paginate($userId, $keyword, $perPage)
                ->through(fn (Model $item) => $this->serialize($item)),
            'summary' => $this->repo->summary($userId, $keyword),
            'filters' => ['q' => $keyword, 'per_page' => $perPage],
            'editing' => $editing ? $this->serialize($editing) : null,
        ];
    }

    public function create(array $data, int $userId): Model
    {
        return $this->repo->create([...$data, 'user_id' => $userId]);
    }

    public function update(Model $model, array $data): Model
    {
        return $this->repo->update($model, $data);
    }

    public function delete(Model $model): void
    {
        $this->repo->delete($model);
    }

    public function serialize(Model $item): array
    {
        return [
            'id' => $item->id,
            'tanggal' => optional($item->tanggal)->format('Y-m-d'),
            'deskripsi' => $item->deskripsi,
            'jumlah' => (float) $item->jumlah,
            'updated_at' => optional($item->updated_at)->toIso8601String(),
        ];
    }
}
