@extends('layouts.migration')

@section('title', 'Importação | ' . config('app.name'))

@section('content')
    @php
        $recentImports = collect($recentImports ?? []);
        $administrations = collect($administrations ?? []);
        $selectedAdministrationId = $selectedAdministrationId ?? null;
    @endphp

    <style>
        .spreadsheet-warning-banner {
            position: relative;
            overflow: hidden;
            display: grid;
            gap: 10px;
            padding: 20px 22px 20px 24px;
            margin-top: 40px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 206, 79, 0.38);
            border-left: 8px solid #ffb703;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(146, 32, 32, 0.98), rgba(77, 17, 17, 0.98));
            color: #fff9f0;
            box-shadow: 0 20px 48px rgba(62, 15, 15, 0.38);
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
        <span class="eyebrow">Importação de planilhas</span>
        <h1>Importe uma planilha para análise.</h1>
        <p class="hero-copy">
            O arquivo é analisado antes da importação definitiva, permitindo revisão dos dados, das igrejas detectadas e da administração vinculada.
        </p>
    </section>

    <div class="spreadsheet-warning-banner" role="alert" aria-live="polite">
        <span class="spreadsheet-warning-banner__eyebrow">Importação por igreja</span>
        <strong>Prefira a planilha filtrada por igreja.</strong>
        <p>
            Se não houver muitos dados, envie já filtrado por igreja para deixar a análise mais leve.
        </p>
    </div>

    @if (session('status') || $errors->any())
        <div class="flash-stack">
            @if (session('status'))
                <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                    <strong>{{ session('status') }}</strong>
                </div>
            @endif

            @if ($errors->any())
                <div class="flash error">
                    <strong>Revise os dados informados.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <section class="section">
        <div class="table-shell">
            <form method="POST" action="{{ route('migration.spreadsheets.store') }}" class="form-shell" enctype="multipart/form-data">
                @csrf

                <div class="field-grid">
                    <input type="hidden" name="usuario_id" value="{{ (int) session('usuario_id', 0) }}">

                    <label>
                        Administração
                        <select name="administracao_id" required @disabled($administrations->isEmpty())>
                            <option value="">Selecione</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected((int) old('administracao_id') === (int) $administration->id)>
                                    {{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Arquivo CSV
                        <input type="file" name="arquivo_csv" accept=".csv,.txt" required>
                    </label>
                </div>

                @if ($administrations->isEmpty())
                    <p class="field-note">Cadastre uma administração antes de enviar a planilha.</p>
                @endif

                <p class="field-note">
                    A análise detecta as igrejas do CSV e prepara a prévia antes da confirmação. O responsável será sempre o usuário logado. Tamanho máximo: 50MB.
                </p>

                <div class="inline-actions">
                    <button class="btn primary" type="submit" @disabled($administrations->isEmpty())>Enviar e analisar</button>
                    <a class="btn" href="{{ route('migration.products.index') }}">Cancelar</a>
                    @if (!empty($legacyPermissions['spreadsheets.errors.view']))
                        <a class="btn" href="{{ route('migration.spreadsheets.errors') }}">Ver erros pendentes</a>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>
                    {{ $selectedAdministrationId
                        ? 'Importações recentes desta administração'
                        : 'Importações recentes' }}
                </h2>
                <p>
                    {{ $selectedAdministrationId
                        ? 'Mostrando até 5 envios feitos com a administração selecionada.'
                        : 'Mostrando até 5 envios registrados no sistema.' }}
                </p>
            </div>
            <div class="metric-label">
                {{ $recentImports->count() }} registro(s)
            </div>
        </div>

        @if ($recentImports->isEmpty())
            <div class="empty-state">
                Nenhuma importação registrada ainda.
            </div>
        @else
            <div class="table-shell">
                <table>
                    <thead>
                                <tr>
                                    <th>Igreja</th>
                                    <th>Administração</th>
                                    <th>Arquivo</th>
                                    <th>Status</th>
                                    <th>Progresso</th>
                            <th>Linhas</th>
                            <th>Responsável</th>
                            <th>Atualização</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentImports as $import)
                            @php
                                $status = (string) ($import['status'] ?? 'aguardando');
                                $dateRef = $import['data_referencia'] ?? null;
                                $dateLabel = $dateRef !== null && $dateRef !== ''
                                    ? \Illuminate\Support\Carbon::parse($dateRef)->format('d/m/Y H:i')
                                    : 'Nenhuma';
                                $progress = number_format((float) ($import['porcentagem'] ?? 0), 0, ',', '.');
                                $completed = (int) ($import['linhas_processadas'] ?? 0);
                                $total = (int) ($import['total_linhas'] ?? 0);
                                $churchLabel = trim((string) ($import['comum_label'] ?? '')) ?: 'Sem igreja informada';
                                $administrationLabel = trim((string) ($import['administracao_label'] ?? '')) ?: 'Sem administração';
                                $responsible = trim((string) ($import['usuario_responsavel_nome'] ?? ''));
                                $responsibleEmail = trim((string) ($import['usuario_responsavel_email'] ?? ''));
                            @endphp
                            <tr>
                                <td data-label="Igreja">{{ $churchLabel }}</td>
                                <td data-label="Administração">{{ $administrationLabel }}</td>
                                <td data-label="Arquivo">
                                    <strong>{{ $import['arquivo_nome'] ?? 'Nenhum' }}</strong>
                                </td>
                                <td data-label="Status">
                                    @if ($status === 'concluida')
                                        <span class="capsule accent">Concluída</span>
                                    @elseif ($status === 'processando')
                                        <span class="capsule dark">Processando</span>
                                    @elseif ($status === 'erro')
                                        <span class="capsule warn">Erro</span>
                                    @else
                                        <span class="capsule">Aguardando</span>
                                    @endif
                                </td>
                                <td data-label="Progresso">
                                    <strong>{{ $progress }}%</strong>
                                </td>
                                <td data-label="Linhas">
                                    {{ $completed }}/{{ $total }}
                                </td>
                                <td data-label="Responsável">
                                    <strong>{{ $responsible !== '' ? $responsible : 'Nenhum' }}</strong>
                                    @if ($responsibleEmail !== '')
                                        <div class="table-note">{{ $responsibleEmail }}</div>
                                    @endif
                                </td>
                                <td data-label="Atualização">
                                    <span class="table-note">{{ $dateLabel }}</span>
                                </td>
                                <td data-label="Ação">
                                    <a class="btn" href="{{ route('migration.spreadsheets.processing', ['importacao' => $import['id']]) }}">Ver</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
