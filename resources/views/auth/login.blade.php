@extends('layouts.auth')

@section('content')
    <div class="auth-shell">
        <section class="auth-card">
            @include('auth.partials.brand')
            @include('auth.partials.alert')
            @include('auth.partials.form')
        </section>
    </div>
@endsection

@push('scripts')
    @include('auth.partials.scripts')
@endpush


