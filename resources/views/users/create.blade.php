@extends('layouts.migration')

@section('title', 'Novo Usuário | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Cadastro de usuários</span>
        <h1>Novo usuário vinculado a uma administração.</h1>
        <p class="hero-copy">
            Esta tela salva o cadastro principal de usuários, mantendo validação de senha, CPF, estado civil e dados
            do cônjuge. O vínculo agora é com a administração, não com uma igreja específica.
        </p>
    </section>

    @include('users._form')
@endsection
