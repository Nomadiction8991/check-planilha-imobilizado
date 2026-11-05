@extends('layouts.migration')

@section('title', 'Processando Importação | ' . config('app.name'))

@section('content')
    <style>
        .processing-shell { display: grid; gap: 16px; padding: 16px; }
        .processing-status { display: grid; gap: 12px; }
        .processing-track { overflow: hidden; height: 16px; border: 1px solid var(--line); border-radius: var(--radius-sm); background: var(--surface-soft); }
        .processing-fill { width: 0; height: 100%; background: var(--accent); transition: width 0.2s ease; }
        .processing-shell.is-complete .processing-fill { background: var(--ui-success); }
        html[data-theme='dark'] .processing-track { border-color: var(--line); background: var(--surface-soft); }
        html[data-theme='dark'] .processing-shell.is-complete .processing-track { border-color: var(--accent); background: var(--accent-soft); }
        .processing-actions { margin-top: 24px; display: flex; gap: 12px; flex-wrap: wrap; }
        .processing-success, .processing-error { display: grid; gap: 16px; }
        @media (prefers-reduced-motion: reduce) { .processing-fill { transition: none; } }
    </style>

    <section class="hero">
        <span class="eyebrow">Processamento em andamento</span>
        <h1>Importação em processamento.</h1>
        <p class="hero-copy">Aguarde a conclusão do processamento.</p>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>{{ $importacao['arquivo_nome'] ?? ('Importação #' . $importacaoId) }}</h2>
                <p>O progresso abaixo é atualizado em tempo real.</p>
                <p class="table-note">Administração: {{ $importacao['administracao_label'] ?? 'Sem administração' }}</p>
            </div>
        </div>
    </section>

    @if (session('status'))
        <div class="flash-stack">
            <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                <strong>{{ session('status') }}</strong>
            </div>
        </div>
    @endif

    <section class="section">
        <div class="table-shell processing-shell" id="processing-shell">
            <div class="processing-status">
                <p><strong>Status:</strong> <span id="status-text">Aguardando início...</span></p>
                <div class="processing-track" aria-hidden="true">
                    <div id="progress-bar" class="processing-fill"></div>
                </div>
                <p><strong>Progresso:</strong> <span id="progress-text">0%</span></p>
            </div>
            <div hidden aria-hidden="true">
                <span id="arquivo-nome">{{ $importacao['arquivo_nome'] ?? '' }}</span>
                <span id="total-linhas">{{ (int) ($importacao['total_linhas'] ?? 0) }}</span>
                <span id="linhas-processadas">{{ (int) ($importacao['linhas_processadas'] ?? 0) }}</span>
                <span id="linhas-sucesso">{{ (int) ($importacao['linhas_sucesso'] ?? 0) }}</span>
                <span id="linhas-erro">{{ (int) ($importacao['linhas_erro'] ?? 0) }}</span>
            </div>
        </div>
    </section>

    <section class="section" id="sucesso-container" style="display:none;">
        <div class="flash-stack processing-success">
            <div class="flash success">
                <strong>Processamento concluído. <span id="sucesso-linhas">0</span> linha(s) com sucesso. <span id="sucesso-erros-txt"></span></strong>
            </div>
        </div>
        <div class="processing-actions">
            <a class="btn primary" href="{{ route('migration.products.index') }}">Ir para produtos</a>
            @if (!empty($legacyPermissions['spreadsheets.errors.view']))
                <a class="btn" id="erros-importacao-link" href="{{ route('migration.spreadsheets.errors', ['importacao_id' => $importacaoId]) }}" style="display:none;">Revisar erros desta importação</a>
            @endif
        </div>
    </section>

    <section class="section processing-error" id="erro-container" style="display:none;">
        <div class="flash-stack">
            <div class="flash error">
                <strong id="erro-mensagem">Erro ao processar importação.</strong>
            </div>
        </div>
        <div class="inline-actions">
            <a class="btn" href="{{ route('migration.spreadsheets.preview', ['importacao' => $importacaoId]) }}">Voltar para prévia</a>
        </div>
    </section>

    <script>
        window.importProgressConfig = {
            importacaoId: {{ \Illuminate\Support\Js::from($importacaoId) }},
            startUrl: {{ \Illuminate\Support\Js::from(route('migration.spreadsheets.start', ['importacao' => $importacaoId])) }},
            progressUrl: {{ \Illuminate\Support\Js::from(route('migration.spreadsheets.progress', ['importacao' => $importacaoId])) }},
            csrfToken: {{ \Illuminate\Support\Js::from(csrf_token()) }},
        };
    </script>
    <script src="{{ asset('assets/reports/spreadsheet-import-progress.js') }}?v={{ filemtime(public_path('assets/reports/spreadsheet-import-progress.js')) }}"></script>
@endsection
