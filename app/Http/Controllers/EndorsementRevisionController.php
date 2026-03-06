<?php

namespace App\Http\Controllers;

use App\Http\Requests\EndorsementRevisionRequest;
use App\Models\Endorsement;
use App\Models\EndorsementRevision;
use Illuminate\Http\RedirectResponse;

class EndorsementRevisionController extends Controller
{
    public function store(EndorsementRevisionRequest $request, Endorsement $endorsement): RedirectResponse
    {
        $payload = $request->validated();
        $payload['uploaded_to_drive'] = $request->boolean('uploaded_to_drive');
        $payload['is_approved'] = $request->boolean('is_approved');
        $payload['endorsement_id'] = $endorsement->id;

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

        return redirect()->route('endorsements.show', $endorsement)->with('success', 'Revisi berhasil disimpan.');
    }

    public function destroy(Endorsement $endorsement, EndorsementRevision $revision): RedirectResponse
    {
        abort_if($revision->endorsement_id !== $endorsement->id, 404);
        $revision->delete();

        return redirect()->route('endorsements.show', $endorsement)->with('success', 'Revisi berhasil dihapus.');
    }
}
