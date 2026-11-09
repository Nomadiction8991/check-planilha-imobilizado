@extends('layouts.migration')

@section('title', 'Formulário ' . $previa['formulario'] . ' | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Formulário {{ $previa['formulario'] }}</span>
        <h1>{{ $tipo === 'saida' ? 'Saída de bens' : 'Entrada de bens' }} nº {{ $previa['numero'] }}</h1>
        <p class="hero-copy">
            {{ $previa['total_paginas'] }} folha(s). Confira os dados e imprima. As assinaturas ficam em branco.
        </p>
    </section>

    <style>{!! $previa['style_content'] !!}</style>

    @foreach ($previa['paginas'] as $pagina)
        <section class="transfer-form-page" aria-label="Folha {{ $pagina['numero'] }}">
            {!! $pagina['html'] !!}
        </section>
    @endforeach
@endsection
