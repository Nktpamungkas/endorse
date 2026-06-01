<?php

namespace App\Console\Commands;

use App\Models\Endorsement;
use Illuminate\Console\Command;

class FixEndorsementPaymentStatusCommand extends Command
{
    protected $signature = 'endorse:fix-payment-status
                            {--dry-run : Tampilkan data yang akan diubah tanpa benar-benar menyimpan}';

    protected $description = 'Normalisasi payment_status endorsement yang inkonsisten dengan status workflow';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('Mode DRY RUN — tidak ada data yang disimpan.');
            $this->newLine();
        }

        // Kasus 1: status=selesai tapi payment_status bukan lunas
        $case1 = Endorsement::withTrashed()
            ->where('status', 'selesai')
            ->where('payment_status', '!=', 'lunas')
            ->get();

        $this->info("Kasus 1 — status=selesai, payment_status!=lunas: {$case1->count()} data");

        foreach ($case1 as $endorsement) {
            $this->line("  [{$endorsement->id}] {$endorsement->brand_name} | payment_status={$endorsement->payment_status}");
        }

        // Kasus 2: payment_received_date terisi tapi payment_status bukan lunas
        $case2 = Endorsement::withTrashed()
            ->whereNotNull('payment_received_date')
            ->where('payment_status', '!=', 'lunas')
            ->get();

        $this->newLine();
        $this->info("Kasus 2 — payment_received_date terisi, payment_status!=lunas: {$case2->count()} data");

        foreach ($case2 as $endorsement) {
            $this->line("  [{$endorsement->id}] {$endorsement->brand_name} | payment_status={$endorsement->payment_status}");
        }

        $total = $case1->merge($case2)->unique('id')->count();

        if ($total === 0) {
            $this->newLine();
            $this->info('Semua data sudah konsisten. Tidak ada yang perlu diperbaiki.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn("Total data yang perlu diupdate: {$total}");

        if ($isDryRun) {
            $this->newLine();
            $this->line('Jalankan tanpa --dry-run untuk menyimpan perubahan.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Lanjutkan update?')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        // Update kasus 1
        $updated1 = Endorsement::withTrashed()
            ->where('status', 'selesai')
            ->where('payment_status', '!=', 'lunas')
            ->update(['payment_status' => 'lunas']);

        // Update kasus 2 (yang belum tertangkap kasus 1)
        $updated2 = Endorsement::withTrashed()
            ->whereNotNull('payment_received_date')
            ->where('payment_status', '!=', 'lunas')
            ->update(['payment_status' => 'lunas']);

        $this->newLine();
        $this->info("Selesai. {$updated1} data diupdate dari kasus 1, {$updated2} dari kasus 2.");

        return self::SUCCESS;
    }
}
