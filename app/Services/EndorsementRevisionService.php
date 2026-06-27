<?php

namespace App\Services;

use App\Models\Endorsement;
use App\Models\EndorsementRevision;

class EndorsementRevisionService
{
    public function store(Endorsement $endorsement, array $payload): void
    {
        $endorsement->revisions()->create($payload);

        if ($payload['uploaded_to_drive']) {
            $endorsement->update(['drive_uploaded' => true]);
        }

        if ($payload['is_approved']) {
            $endorsement->update([
                'approved_at' => $payload['revision_date'],
                'status' => in_array($endorsement->status, ['revisi', 'menunggu_draft_ok'], true)
                    ? 'menunggu_posting'
                    : $endorsement->status,
            ]);
        }
    }

    public function delete(Endorsement $endorsement, EndorsementRevision $revision): void
    {
        abort_if($revision->endorsement_id !== $endorsement->id, 404);
        $revision->delete();
    }
}
