@extends('layouts.migration')

@section('title', 'Conflitos | ' . config('app.name'))

@section('content')
    @if (session('status'))
        <div class="flash-stack">
            <div class="flash success">
                <strong>{{ session('status') }}</strong>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="flash-stack">
            @foreach ($errors->all() as $erro)
                <div class="flash error">
                    <strong>{{ $erro }}</strong>
                </div>
            @endforeach
        </div>
    @endif

    <section class="conflitos" aria-labelledby="conflitos-titulo">
        <h1 id="conflitos-titulo">Conflitos de sincronização</h1>

        @if ($conflitos->isEmpty())
            <p>Nenhum conflito pendente. Todas as mudanças foram aplicadas.</p>
        @else
            <p>Estes dados foram mudados no servidor e no notebook ao mesmo tempo. Escolha qual valor deve ficar.</p>

            @foreach ($conflitos as $conflito)
                <article class="conflito">
                    <h2>{{ $conflito['tabela'] }} · registro {{ $conflito['chave'] }}</h2>
                    <p>{{ $conflito['motivo'] }}</p>

                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Campo</th>
                                <th scope="col">No servidor</th>
                                <th scope="col">No notebook</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($conflito['campos'] as $campo)
                                <tr>
                                    <td data-label="Campo">{{ $campo['campo'] }}</td>
                                    <td data-label="No servidor">{{ $campo['servidor'] ?? '—' }}</td>
                                    <td data-label="No notebook">{{ $campo['notebook'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="conflito-acoes">
                        <form method="POST" action="{{ route('migration.conflitos.notebook', $conflito['id']) }}">
                            @csrf
                            <button class="btn primary" type="submit">Manter o valor do notebook</button>
                        </form>

                        <form method="POST" action="{{ route('migration.conflitos.servidor', $conflito['id']) }}">
                            @csrf
                            <button class="btn" type="submit">Manter o valor do servidor</button>
                        </form>
                    </div>
                </article>
            @endforeach
        @endif
    </section>
@endsection
