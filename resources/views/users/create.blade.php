@extends('layouts.migration')

@section('title', 'Novo Usuário | ' . config('app.name'))

@section('content')
    <style>
        .users-form-page { color: var(--ui-text); font-size: 16px; line-height: 1.5; }
        .users-form-page h1 { font-size: 24px; line-height: 1.2; }
        .users-form-page .btn.primary { background: var(--product-primary); border-color: var(--product-primary); color: white; }
        .users-form-page .btn.primary:hover { background: var(--product-primary-hover); }
        .users-form-page :is(input, select, button, a):focus-visible { outline: 3px solid var(--ui-focus); outline-offset: 2px; }
        .users-form-page .field-error { color: var(--ui-danger); }
        @media (min-width: 640px) { .users-form-page h1 { font-size: 30px; } }
    </style>
    <div class="users-form-page">
    <section class="hero">
        <span class="eyebrow">Cadastro de usuários</span>
        <h1>Novo usuário vinculado a uma administração.</h1>
        <p class="hero-copy">
            Esta tela salva o cadastro principal de usuários, mantendo validação de senha, CPF, estado civil e dados
            do cônjuge. O vínculo agora é com a administração, não com uma igreja específica.
        </p>
    </section>

    @include('users._form')
    </div>
@endsection
