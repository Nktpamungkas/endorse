<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Akses data bersama untuk Pemasukan & Pengeluaran (struktur tabel identik).
 * Subclass cukup menentukan model lewat model().
 */
abstract class CashflowRepository
{
    /** @return class-string<Model> */
    abstract protected function model(): string;

    private function query()
    {
        return $this->model()::query();
    }

    public function paginate(int $userId, string $keyword, int $perPage): LengthAwarePaginator
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->orderByDesc('tanggal')
            ->orderByDesc('updated_at');

        if ($keyword !== '') {
            $query->where('deskripsi', 'like', '%'.$keyword.'%');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function summary(int $userId, string $keyword): array
    {
        $query = $this->query()->where('user_id', $userId);

        if ($keyword !== '') {
            $query->where('deskripsi', 'like', '%'.$keyword.'%');
        }

        return [
            'total_items' => (clone $query)->count(),
            'total_amount' => (float) (clone $query)->sum('jumlah'),
        ];
    }

    public function findForUser(int $userId, int $id): ?Model
    {
        return $this->query()->where('user_id', $userId)->find($id);
    }

    public function create(array $data): Model
    {
        return $this->model()::create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model;
    }

    public function delete(Model $model): void
    {
        $model->delete();
    }

    public function sumAll(int $userId): float
    {
        return (float) $this->query()->where('user_id', $userId)->sum('jumlah');
    }

    public function recent(int $userId, int $limit = 5): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->orderByDesc('tanggal')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    public function sumBefore(int $userId, Carbon $date): float
    {
        return (float) $this->query()
            ->where('user_id', $userId)
            ->where('tanggal', '<', $date)
            ->sum('jumlah');
    }

    public function inPeriod(int $userId, int $bulan, int $tahun): Collection
    {
        $query = $this->query()->where('user_id', $userId);

        if ($tahun !== 0) {
            if ($bulan > 0) {
                $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
            } else {
                $query->whereYear('tanggal', $tahun);
            }
        }

        return $query->get();
    }
}
