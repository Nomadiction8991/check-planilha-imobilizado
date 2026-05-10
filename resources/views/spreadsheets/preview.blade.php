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
        .hero {
            margin-top: 40px;
            margin-bottom: 32px;
        }

        .spreadsheet-warning-banner {
            margin-top: 24px;
            position: relative;
            overflow: hidden;
            display: grid;
            gap: 12px;
            padding: 24px 28px;
            margin-bottom: 32px;
            border: 1px solid rgba(46, 196, 182, 0.2);
            border-left: 6px solid #2ec4b6;
            border-radius: 24px;
            background: linear-gradient(135deg, #0f2a28, #071514);
            color: #f0fdfa;
            box-shadow: 0 20px 48px rgba(0, 0, 0, 0.25);
            transition: transform 0.3s ease;
        }

        .spreadsheet-warning-banner__eyebrow {
            display: inline-flex;
            align-self: start;
            width: fit-content;
            padding: 5px 12px;
            border-radius: 999px;
            background: rgba(46, 196, 182, 0.15);
            color: #2ec4b6;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .spreadsheet-warning-banner strong {
            display: block;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .spreadsheet-warning-banner p {
            margin: 0;
            max-width: 80ch;
            font-size: 15px;
            line-height: 1.6;
            color: rgba(240, 253, 250, 0.85);
        }

        /* UX Polish: Tabelas e Interações */
        .table-shell {
            border: 1px solid var(--line);
            background: var(--surface);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            transition: box-shadow 0.3s ease;
        }

        table {
            border-spacing: 0;
            width: 100%;
        }

        th {
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 0.05em;
            padding: 16px 20px;
            background: rgba(0, 0, 0, 0.02);
            border-bottom: 2px solid var(--line);
        }

        td {
            padding: 16px 20px;
            vertical-align: middle;
            transition: background-color 0.2s ease;
        }

        .table-shell tbody tr:hover td,
        .dependency-table tbody tr:hover td {
            background: transparent !important;
        }

        tr.dependency-header-row th {
            background: rgba(31, 111, 95, 0.03);
            border-top: 1px solid var(--line);
            padding: 12px 20px;
            font-size: 10px;
            color: var(--muted);
        }

        .dependency-row td {
            padding: 0 0 20px 40px;
            background: transparent;
            font-size: 13px;
        }

        .dependency-panel {
            border-left: 2px solid var(--line);
            padding-left: 20px;
        }

        .dependency-panel__title {
            margin: 0 0 10px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .dependency-table {
            width: 100%;
            border: none;
            border-collapse: collapse;
            background: transparent;
        }

        .dependency-table thead tr {
            border-bottom: 1px solid var(--line);
        }

        .dependency-table th {
            padding: 8px;
            font-size: 11px;
            color: var(--muted);
            background: transparent;
            border-bottom: 0;
        }

        .dependency-table td {
            padding: 6px 8px;
            font-size: 12px;
        }

        .dependency-table .dependency-total,
        .dependency-table .dependency-new,
        .dependency-table .dependency-update,
        .dependency-table .dependency-error {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .dependency-table .dependency-new {
            color: #4ade80;
        }

        .dependency-table .dependency-update {
            color: #60a5fa;
        }

        .dependency-table .dependency-error {
            color: #f87171;
        }

        /* Micro-interações em botões e selects */
        select.church-action-select,
        select.dependency-action-select {
            cursor: pointer;
            border-radius: 10px;
            padding: 8px 12px;
            font-weight: 600;
            font-size: 13px;
            border: 1px solid var(--line);
            background: var(--surface-strong);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        select.church-action-select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
        }

        select.dependency-action-select {
            font-size: 12px;
            padding: 4px 8px;
        }

        select.dependency-action-select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
        }

        /* Animação de expansão */
        .dependency-container {
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .spreadsheet-warning-banner {
            animation: banner-entry 0.6s cubic-bezier(0.23, 1, 0.32, 1) both;
        }

        @keyframes banner-entry {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .is-hidden {
            display: none !important;
            opacity: 0;
            transform: translateY(-10px);
        }

        /* Estilização Premium das Capsules */
        .capsule {
            font-weight: 700;
            letter-spacing: 0.02em;
            padding: 4px 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            border: 1px solid transparent;
        }

        .capsule.accent {
            background: rgba(31, 111, 95, 0.1);
            border-color: rgba(31, 111, 95, 0.15);
        }

        .capsule.warn {
            background: rgba(139, 61, 25, 0.08);
            border-color: rgba(139, 61, 25, 0.12);
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
                                <td>{{ $churchCode !== '' ? $churchCode : 'Nenhum' }}</td>
                                <td>
                                    <strong>{{ $churchDescription !== '' ? $churchDescription : 'Sem descrição' }}</strong>
                                </td>
                                <td>{{ number_format((int) ($igreja['total'] ?? 0), 0, ',', '.') }}</td>
                                <td>
                                    @if ($churchStatus === 'com_erro')
                                        <span class="capsule warn">Com erros</span>
                                    @elseif ($churchStatus === 'com_alteracoes')
                                        <span class="capsule accent">Com alterações</span>
                                    @else
                                        <span class="capsule">Sem alterações</span>
                                    @endif
                                </td>
                                <td>{{ number_format((int) ($igreja['novos'] ?? 0), 0, ',', '.') }}</td>
                                <td>{{ number_format((int) ($igreja['atualizar'] ?? 0), 0, ',', '.') }}</td>
                                <td>{{ number_format((int) ($igreja['exclusoes'] ?? 0), 0, ',', '.') }}</td>
                                <td>{{ number_format((int) ($igreja['erros'] ?? 0), 0, ',', '.') }}</td>
                                <td>
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
                                                            <td>{{ $depName }}</td>
                                                            <td class="dependency-total">{{ $depSummary['total'] }}</td>
                                                            <td class="dependency-new">{{ $depSummary['novos'] }}</td>
                                                            <td class="dependency-update">{{ $depSummary['atualizar'] }}</td>
                                                            <td class="dependency-error">{{ $depSummary['erros'] }}</td>
                                                            <td>
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
