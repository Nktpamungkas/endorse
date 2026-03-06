@extends('layouts.app', ['title' => 'Tambah Endorse'])

@section('content')
    <div class="mb-3">
        <h1 class="h3 fw-bold mb-1">Tambah Endorse</h1>
        <div class="text-muted-soft">Isi data campaign baru sesuai alur kerja endorse.</div>
    </div>

    <form method="POST" action="{{ route('endorsements.store') }}" enctype="multipart/form-data">
        @csrf
        @include('endorsements.partials.form', ['submitLabel' => 'Simpan Endorse'])
    </form>
@endsection
