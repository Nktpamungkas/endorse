@extends('layouts.app', ['title' => 'Edit Endorse'])

@section('content')
    <div class="mb-3">
        <h1 class="h3 fw-bold mb-1">Ubah data endorse</h1>
        <div class="text-muted-soft">{{ $endorsement->brand_name }} - perbarui informasi campaign dengan mudah.</div>
    </div>

    <form method="POST" action="{{ route('endorsements.update', $endorsement) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('endorsements.partials.form', ['submitLabel' => 'Simpan perubahan'])
    </form>
@endsection
