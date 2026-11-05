@extends('layouts.migration')

@section('title', 'Prévia da Importação | ' . config('app.name'))

@section('content')
    @php
        $resumo = $analise['resumo'] ?? [];
        $igrejasDetectadas = collect($igrejasDetectadas ?? []);
        $savedChurches = $igrejasSalvas ?? [];
        $savedDependencies = $dependenciasSalvas ?? [];
    @endphp

    <style>
        .spreadsheet-warning-banner { display: grid; gap: 8px; margin: 24px 0; padding: 16px; border: 1px solid var(--line); border-left: 4px solid var(--ui-warning); border-radius: var(--radius-md); background: var(--surface); color: var(--ink); line-height: 1.5; }
        .spreadsheet-warning-banner__eyebrow { color: var(--muted); font-weight: 700; }
        .spreadsheet-warning-banner p { margin: 0; }
        .dependency-panel { padding-left: 16px; }
        .dependency-panel__title { color: var(--muted); font-weight: 700; }
        .dependency-table { width: 100%; }
        .dependency-new { color: var(--ui-success); }
        .dependency-update { color: var(--accent); }
        .dependency-error { color: var(--ui-danger); }
        select.church-action-select, select.dependency-action-select { min-height: 44px; font-size: 16px; }
        select.church-action-select:focus-visible, select.dependency-action-select:focus-visible { outline: 2px solid var(--ui-focus); outline-offset: 2px; }
        .is-hidden { display: none !important; }
        .import-errors-panel { border-left: 4px solid var(--ui-warning); }
        .import-errors-panel__head { display: flex; align-items: flex-start; gap: 12px; padding: 16px; border-bottom: 1px solid var(--line); }
        .import-errors-panel__badge { font-weight: 700; color: var(--ui-warning); }
        .import-errors-panel__head p { margin: 4px 0 0; color: var(--muted); }
        @media (max-width: 860px) {
            table:not(.dependency-table, .verification-table) > tbody > tr.dependency-row {
                margin-top: -14px;
                padding: 0 16px 16px;
                border: 0;
                background: transparent;
                box-shadow: none;
            }
            table:not(.dependency-table, .verification-table) > tbody > tr.dependency-row > td::before { display: none; }
            table:not(.dependency-table, .verification-table).import-errors-table > tbody > tr > td[data-card-title]::before {
                display: inline;
                margin-right: 6px;
                font-size: inherit;
                color: inherit;
            }
            table.import-errors-table > tbody > tr > td[data-label="Motivo"] { grid-column: 1 / -1; }
        }
    </style>

    <section class="hero">
        <span class="eyebrow">Análise pronta</span>
        <h1>Escolha as igrejas que devem entrar na importação.</h1>
        <p class="hero-copy">
            A conferência agora é feita por igreja. Cada grupo reúne os produtos detectados e será processado em
            bloco quando você confirmar.
        </p>
        <p class="table-note">
            Administração: {{ $importacao['administracao_label'] ?? 'Sem administração' }}
        </p>
    </section>

    @if (session('status'))
        <div class="flash-stack">
            <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                <strong>{{ session('status') }}</strong>
            </div>
        </div>
    @endif

    <section class="section">
        @php
            $errosPreview = collect($errosPreview ?? []);
            $totalErrosAnalise = (int) ($analise['resumo']['erros'] ?? count($errosPreview));
        @endphp

        @if ($errosPreview->isNotEmpty())
            <div class="table-shell import-errors-panel" role="region" aria-label="Linhas com erro na análise">
                <div class="import-errors-panel__head">
                    <span class="import-errors-panel__badge">{{ $errosPreview->count() }}</span>
                    <div>
                        <strong>Linhas com erro na análise</strong>
                        <p>
                            {{ $errosPreview->count() }} linha(s) com falha. Corrija o CSV e reimporte, ou revise os itens abaixo.
                        </p>
                    </div>
                </div>

                <table class="import-errors-table">
                    <thead>
                        <tr>
                            <th scope="col">Linha</th>
                            <th scope="col">Código</th>
                            <th scope="col">Nome</th>
                            <th scope="col">Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($errosPreview as $erro)
                            <tr>
                                <td data-label="Linha" data-card-title>{{ (int) ($erro['linha'] ?? 0) }}</td>
                                <td data-label="Código">{{ trim((string) ($erro['codigo'] ?? '')) !== '' ? $erro['codigo'] : '—' }}</td>
                                <td data-label="Nome">{{ trim((string) ($erro['nome'] ?? '')) !== '' ? $erro['nome'] : '—' }}</td>
                                <td data-label="Motivo">{{ $erro['mensagem'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="spreadsheet-warning-banner" role="alert" aria-live="polite">
        <span class="spreadsheet-warning-banner__eyebrow">Escopo da importação</span>
        <strong>A importação processa a <strong>igreja inteira</strong> — ao confirmar, todos os setores da(s) igreja(s) selecionada(s) serão importados.</strong>
    </div>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>Igrejas detectadas</h2>
                <p>Escolha quais igrejas serão importadas. O processamento das linhas acontece em bloco por grupo.</p>
            </div>
        </div>

        <div class="table-shell">
            @if ($igrejasDetectadas->isEmpty())
                <div class="empty-state">Nenhuma igreja foi detectada na análise.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Igreja</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Novos</th>
                            <th>Alterações</th>
                            <th>Exclusões</th>
                            <th>Erros</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($igrejasDetectadas as $igreja)
                            @php
                                $churchKey = (string) ($igreja['chave'] ?? ($igreja['codigo'] ?? ''));
                                $churchCode = (string) ($igreja['codigo'] ?? '');
                                $churchDescription = trim((string) ($igreja['descricao'] ?? ''));
                                $churchStatus = (string) ($igreja['status'] ?? 'sem_alteracao');
                                $churchAction = $savedChurches[$churchKey] ?? 'pular';
                            @endphp
                            <tr class="church-row" data-church-key="{{ $churchKey }}">
                                <td data-label="Código">{{ $churchCode !== '' ? $churchCode : 'Nenhum' }}</td>
                                <td data-label="Igreja" data-card-title>
                                    <strong>{{ $churchDescription !== '' ? $churchDescription : 'Sem descrição' }}</strong>
                                </td>
                                <td data-label="Total">{{ number_format((int) ($igreja['total'] ?? 0), 0, ',', '.') }}</td>
                                <td data-label="Status">
                                    @if ($churchStatus === 'com_erro')
                                        <span class="capsule warn">Com erros</span>
                                    @elseif ($churchStatus === 'com_alteracoes')
                                        <span class="capsule accent">Com alterações</span>
                                    @else
                                        <span class="capsule">Sem alterações</span>
                                    @endif
                                </td>
                                <td data-label="Novos">{{ number_format((int) ($igreja['novos'] ?? 0), 0, ',', '.') }}</td>
                                <td data-label="Alterações">{{ number_format((int) ($igreja['atualizar'] ?? 0), 0, ',', '.') }}</td>
                                <td data-label="Exclusões">{{ number_format((int) ($igreja['exclusoes'] ?? 0), 0, ',', '.') }}</td>
                                <td data-label="Erros">{{ number_format((int) ($igreja['erros'] ?? 0), 0, ',', '.') }}</td>
                                <td data-label="Ação" data-card-actions>
                                    <select class="js-preview-church-action church-action-select" data-codigo="{{ $churchKey }}" aria-label="Ação da igreja {{ $churchCode !== '' ? $churchCode : 'sem código' }}">
                                        <option value="pular" @selected($churchAction === 'pular')>Não importar</option>
                                        <option value="importar" @selected($churchAction === 'importar')>Importar tudo</option>
                                        <option value="personalizado" @selected($churchAction === 'personalizado')>Personalizado</option>
                                    </select>
                                </td>
                            </tr>
                            @if(!empty($igreja['dependencias']))
                                @php ksort($igreja['dependencias']); @endphp
                                <tr class="dependency-row {{ $churchAction !== 'personalizado' ? 'is-hidden' : '' }}" data-for-church="{{ $churchKey }}">
                                    <td colspan="9">
                                        <div class="dependency-panel">
                                            <h4 class="dependency-panel__title">Setores / Dependências</h4>
                                            <table class="dependency-table">
                                                <thead>
                                                    <tr>
                                                        <th>Setor</th>
                                                        <th>Total</th>
                                                        <th>Novos</th>
                                                        <th>Alt.</th>
                                                        <th>Erros</th>
                                                        <th>Ação</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($igreja['dependencias'] as $depName => $depSummary)
                                                        @php
                                                            $depKey = $churchKey . ':' . $depName;
                                                            $depAction = $savedDependencies[$depKey] ?? ($churchAction === 'importar' ? 'importar' : 'pular');
                                                        @endphp
                                                        <tr>
                                                            <td data-label="Setor">{{ $depName }}</td>
                                                            <td data-label="Total" class="dependency-total">{{ $depSummary['total'] }}</td>
                                                            <td data-label="Novos" class="dependency-new">{{ $depSummary['novos'] }}</td>
                                                            <td data-label="Alt." class="dependency-update">{{ $depSummary['atualizar'] }}</td>
                                                            <td data-label="Erros" class="dependency-error">{{ $depSummary['erros'] }}</td>
                                                            <td data-label="Ação">
                                                                <select class="js-preview-dependency-action dependency-action-select" data-dep-key="{{ $depKey }}" aria-label="Ação da dependência {{ $depName }}">
                                                                    <option value="pular" @selected($depAction === 'pular')>Pular</option>
                                                                    <option value="importar" @selected($depAction === 'importar')>Importar</option>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>Confirmação final</h2>
                <p>As escolhas são salvas automaticamente. Quando fechar a seleção, confirme para iniciar o processamento.</p>
            </div>
        </div>

        <div class="inline-actions">
            <a class="btn" href="{{ route('migration.spreadsheets.create') }}">Cancelar</a>
            <form method="POST" action="{{ route('migration.spreadsheets.confirm', ['importacao' => $importacaoId]) }}">
                @csrf
                <input type="hidden" name="importar_tudo" id="importar_tudo_flag" value="0">
                <button class="btn primary" type="submit">Confirmar igrejas selecionadas</button>
            </form>
        </div>
    </section>

    <script>
        window.previewImportConfig = {
            saveUrl: {{ \Illuminate\Support\Js::from(route('migration.spreadsheets.preview.actions', ['importacao' => $importacaoId])) }},
            csrfToken: {{ \Illuminate\Support\Js::from(csrf_token()) }},
        };

        // Toggle resiliente para dependências (caso o JS externo esteja em cache)
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('js-preview-church-action')) {
                const churchKey = e.target.dataset.codigo;
                const row = document.querySelector(`.dependency-row[data-for-church="${churchKey}"]`);
                if (row) {
                    if (e.target.value === 'personalizado') {
                        row.classList.remove('is-hidden');
                    } else {
                        row.classList.add('is-hidden');
                    }
                }
            }
        });
    </script>
    <script src="{{ asset('assets/reports/spreadsheet-preview.js?v=' . time()) }}"></script>
@endsection
