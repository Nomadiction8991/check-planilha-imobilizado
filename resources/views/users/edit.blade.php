@extends('layouts.migration')

@section('title', 'Editar Usuário | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Cadastro de usuários</span>
        <h1>Editar usuário vinculado a uma administração.</h1>
        <p class="hero-copy">
            Esta tela preserva as regras de senha, CPF, RG, estado civil e dados do cônjuge, com validação antes de
            salvar. O vínculo é com a administração associada ao usuário.
        </p>
    </section>

    @include('users._form')
@endsection
