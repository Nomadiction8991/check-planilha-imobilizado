@extends('layouts.migration')

@section('title', 'Erros de Importação | ' . config('app.name'))

@php
    $contextLabel = 'Visão geral';
    $downloadParams = [];
    $backUrl = route('migration.spreadsheets.create');
    $backLabel = 'Nova importação';

    if ($modo === 'comum' && is_array($comum)) {
        $contextLabel = ($comum['codigo'] ?? '') . ' - ' . ($comum['descricao'] ?? 'Igreja');
        $downloadParams = ['comum_id' => $comumId];
        $backUrl = route('migration.products.index', ['comum_id' => $comumId]);
        $backLabel = 'Voltar para produtos';
    } elseif ($modo === 'administracao' && is_array($administracao ?? null)) {
        $administrationIds = array_values(array_filter(
            (array) ($administracao['ids'] ?? []),
            static fn (mixed $value): bool => (int) $value > 0,
        ));
        $contextLabel = (count($administrationIds) > 1 ? 'Administrações' : 'Administração')
            . ': '
            . ($administracao['descricao'] ?? 'Administração atual');
    } elseif ($modo === 'importacao' && is_array($importacao)) {
        $contextLabel = trim(
            ($importacao['arquivo_nome'] ?? ('Importação #' . $importacaoId))
            . ' - Administração: '
            . ($importacao['administracao_label'] ?? 'Sem administração')
        );
        $downloadParams = ['importacao_id' => $importacaoId];
    }
@endphp

@section('content')
    <section class="hero">
        <span class="eyebrow">Importação assistida</span>
        <h1>Erros de importação podem ser tratados aqui.</h1>
        <p class="hero-copy">
            Esta tela concentra os itens pendentes, permite baixar um CSV de correção e marcar erros como resolvidos.
        </p>
        @if ($contextLabel !== 'Visão geral')
            <p class="table-note">{{ $contextLabel }}</p>
        @endif
        <div class="inline-actions">
            <a class="btn" href="{{ $backUrl }}">{{ $backLabel }}</a>
            @if ($resumo['pendentes'] > 0 && ($modo === 'comum' || $modo === 'administracao' || $modo === 'importacao'))
                <a class="btn primary" href="{{ route('migration.spreadsheets.errors.download', $downloadParams) }}">
                    Baixar CSV de correção
                </a>
            @endif
        </div>
    </section>

    @if (session('status'))
        <div class="flash-stack">
            <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                <strong>{{ session('status') }}</strong>
            </div>
        </div>
    @endif

    @if ($erros->isEmpty())
        <section class="section">
            <div class="empty-state">
                Nenhum registro de erro encontrado para o filtro atual.
            </div>
        </section>
    @else
        <section class="section">
            <div class="section-head">
                <div>
                    <h2>Itens com falha</h2>
                    <p class="table-note">
                        Página {{ $erros->currentPage() }} de {{ $erros->lastPage() }}. Total de {{ $erros->total() }} registro(s).
                    </p>
                </div>
            </div>
            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição CSV</th>
                            <th>Erro</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($erros as $erro)
                            @php
                                $isResolved = (int) ($erro->resolvido ?? 0) === 1;
                            @endphp
                            <tr id="erro-row-{{ $erro->id }}">
                                <td data-label="Código">
                                    <strong class="mono">{{ $erro->codigo ?: 'Nenhum' }}</strong>
                                    @if (!empty($erro->linha_csv))
                                        <div class="table-note">Linha {{ $erro->linha_csv }}</div>
                                    @endif
                                </td>
                                <td data-label="Descrição CSV">
                                    {{ $erro->descricao_csv ?: trim(($erro->bem ?? '') . ' ' . ($erro->complemento ?? '')) }}
                                </td>
                                <td data-label="Erro">
                                    {{ $erro->mensagem_erro }}
                                </td>
                                <td data-label="Situação">
                                    @if (!empty($legacyPermissions['spreadsheets.errors.resolve']))
                                        <label style="display:flex;align-items:center;gap:8px;color:inherit;">
                                            <input
                                                class="js-resolve-error"
                                                type="checkbox"
                                                data-id="{{ $erro->id }}"
                                                @checked($isResolved)
                                            >
                                            <span id="erro-status-{{ $erro->id }}">{{ $isResolved ? 'Resolvido' : 'Pendente' }}</span>
                                        </label>
                                    @else
                                        <span id="erro-status-{{ $erro->id }}">{{ $isResolved ? 'Resolvido' : 'Pendente' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($erros->lastPage() > 1)
                <div class="pagination">
                    {{ $erros->appends(request()->query())->links() }}
                </div>
            @endif
        </section>
    @endif

    <script>
        window.importErrorsConfig = {
            resolveUrlBase: {{ \Illuminate\Support\Js::from(url('/spreadsheets/errors')) }},
            csrfToken: {{ \Illuminate\Support\Js::from(csrf_token()) }},
            canResolve: {{ \Illuminate\Support\Js::from(!empty($legacyPermissions['spreadsheets.errors.resolve'])) }},
        };
    </script>
    <script src="{{ asset('assets/reports/spreadsheet-errors.js') }}"></script>
@endsection
