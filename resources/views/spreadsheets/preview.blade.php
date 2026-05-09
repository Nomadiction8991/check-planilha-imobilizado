@extends('layouts.migration')

@section('title', 'Prévia da Importação | ' . config('app.name'))

@section('content')
    @php
        $resumo = $analise['resumo'] ?? [];
        $igrejasDetectadas = collect($igrejasDetectadas ?? []);
        $savedChurches = $igrejasSalvas ?? [];
    @endphp

    <style>
        .spreadsheet-warning-banner {
            position: relative;
            overflow: hidden;
            display: grid;
            gap: 10px;
            padding: 20px 22px 20px 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 206, 79, 0.38);
            border-left: 8px solid #ffb703;
            border-radius: 24px;
            background:
                linear-gradient(135deg, rgba(146, 32, 32, 0.98), rgba(77, 17, 17, 0.98)),
                radial-gradient(circle at top right, rgba(255, 183, 3, 0.28), transparent 34%);
            color: #fff9f0;
            box-shadow: 0 20px 48px rgba(62, 15, 15, 0.38);
        }

        .spreadsheet-warning-banner::after {
            content: '';
            position: absolute;
            inset: auto -18px -18px auto;
            width: 128px;
            height: 128px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(255, 183, 3, 0.18), transparent 70%);
            pointer-events: none;
        }

        .spreadsheet-warning-banner__eyebrow {
            display: inline-flex;
            align-self: start;
            width: fit-content;
            padding: 5px 12px;
            border-radius: 999px;
            background: rgba(255, 244, 214, 0.16);
            color: #ffe28a;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .spreadsheet-warning-banner strong {
            display: block;
            font-size: 18px;
            line-height: 1.35;
            letter-spacing: -0.01em;
        }

        .spreadsheet-warning-banner p {
            position: relative;
            z-index: 1;
            margin: 0;
            max-width: 90ch;
            font-size: 15px;
            line-height: 1.65;
            color: rgba(255, 249, 240, 0.95);
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

    <div class="spreadsheet-warning-banner" role="alert" aria-live="polite">
        <span class="spreadsheet-warning-banner__eyebrow">Atenção máxima</span>
        <strong>Importação por dependência não é suportada.</strong>
        <p>
            Confirme sempre a igreja inteira ou todas as igrejas do arquivo. Separar por dependência pode omitir
            itens do relatório e causar divergências futuras.
        </p>
    </div>

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
                            <tr>
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
                                    <select class="js-preview-church-action" data-codigo="{{ $churchKey }}">
                                        <option value="pular" @selected($churchAction !== 'importar')>Não importar</option>
                                        <option value="importar" @selected($churchAction === 'importar')>Importar</option>
                                    </select>
                                </td>
                            </tr>
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
    </script>
    <script src="{{ asset('assets/reports/spreadsheet-preview.js') }}"></script>
@endsection
