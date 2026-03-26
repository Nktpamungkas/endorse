<?php

namespace Tests\Feature;

use App\Models\Endorsement;
use App\Models\EndorsementFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndorsementFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_download_and_delete_endorsement_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'username' => 'filetester',
            'role' => 'paid',
            'active' => true,
            'session_code' => 'session-files',
            'trial_ends_at' => now()->addDay(),
        ]);

        $endorsement = Endorsement::create([
            'user_id' => $user->id,
            'brand_name' => 'Top Coffee',
            'campaign_name' => 'Ramadan Batch',
            'platform' => 'instagram',
            'content_type' => 'video',
            'status' => 'deal_masuk',
            'financial_mode' => 'reimburse_duluan',
            'payment_status' => 'belum_bayar',
            'fee_amount' => 100000,
            'reimburse_amount' => 0,
            'product_cost' => 25000,
            'other_cost' => 10000,
        ]);

        $this->actingAs($user);
        $this->withSession(['user_session_code' => 'session-files']);

        $uploadResponse = $this->post(route('endorsement-files.store', $endorsement), [
            'files' => [
                UploadedFile::fake()->create('draft-final.mp4', 2048, 'video/mp4'),
                UploadedFile::fake()->create('brief.pdf', 512, 'application/pdf'),
            ],
        ]);

        $uploadResponse->assertRedirect();
        $this->assertDatabaseCount('endorsement_files', 2);
        $this->assertDatabaseHas('endorsement_files', [
            'endorsement_id' => $endorsement->id,
            'original_name' => 'draft-final.mp4',
            'category' => 'video',
        ]);

        $storedFile = EndorsementFile::where('original_name', 'draft-final.mp4')->firstOrFail();
        Storage::disk('local')->assertExists($storedFile->directory.'/'.$storedFile->stored_name);

        $this->get(route('endorsement-files.download', $storedFile))
            ->assertOk()
            ->assertDownload('draft-final.mp4');

        $deleteResponse = $this->delete(route('endorsement-files.destroy', $storedFile));
        $deleteResponse->assertRedirect();

        $this->assertDatabaseMissing('endorsement_files', [
            'id' => $storedFile->id,
        ]);
        Storage::disk('local')->assertMissing($storedFile->directory.'/'.$storedFile->stored_name);
    }
}
