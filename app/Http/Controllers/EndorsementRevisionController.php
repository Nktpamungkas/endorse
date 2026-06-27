<?php

namespace App\Http\Controllers;

use App\Http\Requests\EndorsementRevisionRequest;
use App\Models\Endorsement;
use App\Models\EndorsementRevision;
use App\Services\EndorsementRevisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class EndorsementRevisionController extends Controller
{
    public function __construct(private readonly EndorsementRevisionService $service) {}

    public function store(EndorsementRevisionRequest $request, Endorsement $endorsement): RedirectResponse
    {
        $this->assertOwnership($endorsement);
        $payload = $request->validated();
        $payload['uploaded_to_drive'] = $request->boolean('uploaded_to_drive');
        $payload['is_approved'] = $request->boolean('is_approved');
        $payload['endorsement_id'] = $endorsement->id;
        $payload['user_id'] = Auth::id();

        $this->service->store($endorsement, $payload);

        return redirect()->route('endorsements.show', $endorsement)->with('success', 'Revisi berhasil disimpan.');
    }

    public function destroy(Endorsement $endorsement, EndorsementRevision $revision): RedirectResponse
    {
        $this->assertOwnership($endorsement);
        $this->service->delete($endorsement, $revision);

        return redirect()->route('endorsements.show', $endorsement)->with('success', 'Revisi berhasil dihapus.');
    }

    private function assertOwnership(Endorsement $endorsement): void
    {
        $user = Auth::user();
        if ($endorsement->user_id === null) {
            $endorsement->update(['user_id' => $user->id]);
            return;
        }
        if ($endorsement->user_id !== $user->id && $user->role !== 'master') {
            redirect()->route('endorsements.index')
                ->withErrors(['access' => 'Data ini milik akun lain.'])
                ->throwResponse();
        }
    }
}
