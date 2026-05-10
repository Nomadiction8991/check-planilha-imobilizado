@extends('layouts.migration')

@section('title', 'Processando Importação | ' . config('app.name'))

@section('content')
    <style>
        .processing-shell {
            display: grid;
            gap: 14px;
            padding: 24px;
        }

        .processing-status {
            display: grid;
            gap: 10px;
        }

        .processing-track {
            overflow: hidden;
            height: 22px;
            border: 1px solid rgba(24, 21, 17, 0.16);
            border-radius: 999px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.72), rgba(255, 255, 255, 0.52)),
                rgba(31, 111, 95, 0.06);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.32),
                inset 0 -1px 0 rgba(24, 21, 17, 0.05);
        }

        .processing-fill {
            width: 0;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--accent) 0%, #8fdacb 100%);
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.08) inset,
                0 6px 18px rgba(31, 111, 95, 0.20);
            transition: width 0.2s ease, background 0.2s ease;
        }

        .processing-shell.is-complete .processing-track {
            border-color: rgba(31, 111, 95, 0.28);
            background:
                linear-gradient(180deg, rgba(31, 111, 95, 0.14), rgba(31, 111, 95, 0.08)),
                rgba(31, 111, 95, 0.08);
        }

        .processing-shell.is-complete .processing-fill {
            background: linear-gradient(90deg, #73d5c4 0%, #b4f0e4 100%);
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.12) inset,
                0 8px 22px rgba(31, 111, 95, 0.28);
        }

        .processing-actions {
            margin-top: 28px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .processing-success {
            display: grid;
            gap: 18px;
        }

        .processing-error {
            display: grid;
            gap: 28px;
        }

        html[data-theme='dark'] .processing-track {
            border-color: rgba(145, 221, 209, 0.22);
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.02)),
                rgba(145, 221, 209, 0.08);
        }

        html[data-theme='dark'] .processing-shell.is-complete .processing-track {
            border-color: rgba(145, 221, 209, 0.32);
            background:
                linear-gradient(180deg, rgba(145, 221, 209, 0.14), rgba(145, 221, 209, 0.08)),
                rgba(145, 221, 209, 0.1);
        }
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
