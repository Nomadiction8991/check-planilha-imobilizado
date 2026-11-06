@extends('layouts.migration')

@section('title', 'Conexão | ' . config('app.name'))

@section('content')
    @if (session('status') || $errors->any())
        <div class="flash-stack">
            @if (session('status'))
                <div class="flash success">
                    <strong>{{ session('status') }}</strong>
                </div>
            @endif

            @foreach ($errors->all() as $erro)
                <div class="flash error">
                    <strong>{{ $erro }}</strong>
                </div>
            @endforeach
        </div>
    @endif

    <section aria-labelledby="conexao-titulo">
        <h1 id="conexao-titulo">Conexão para celulares</h1>

        @if ($status['ligado'])
            <p>O modo offline está ligado. Os celulares se conectam à rede <strong>{{ $status['rede'] }}</strong>, mesmo sem internet.</p>

            <div class="conexao-qr">
                <figure>
                    {!! $qrRede !!}
                    <figcaption>1. Entrar na rede Wi-Fi</figcaption>
                </figure>

                @if ($qrEndereco !== null)
                    <figure>
                        {!! $qrEndereco !!}
                        <figcaption>2. Abrir o sistema</figcaption>
                    </figure>
                @endif
            </div>

            <p>Senha da rede: <strong>{{ $status['senha'] }}</strong></p>

            @if ($status['endereco_sistema'] !== null)
                <p>Endereço para digitar no navegador do celular: <strong>{{ $status['endereco_sistema'] }}</strong></p>
            @else
                <p>Não foi possível descobrir o endereço deste computador na rede. Confira o ponto de acesso.</p>
            @endif

            <form method="POST" action="{{ route('migration.conexao.desligar') }}">
                @csrf
                <button class="btn" type="submit">Desligar modo offline</button>
            </form>
        @else
            <p>O modo offline está desligado. Ative para que os celulares se conectem a este computador, mesmo sem internet.</p>

            <form method="POST" action="{{ route('migration.conexao.ligar') }}">
                @csrf
                <button class="btn primary" type="submit">Ativar modo offline</button>
            </form>
        @endif
    </section>
@endsection
