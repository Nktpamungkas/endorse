@extends('layouts.app', ['title' => 'Edit Endorse'])

@section('content')
    <div class="mb-3">
        <h1 class="h3 fw-bold mb-1">Edit Endorse</h1>
        <div class="text-muted-soft">{{ $endorsement->brand_name }} - update progress campaign.</div>
    </div>

    <form method="POST" action="{{ route('endorsements.update', $endorsement) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('endorsements.partials.form', ['submitLabel' => 'Update Endorse'])
    </form>
@endsection
