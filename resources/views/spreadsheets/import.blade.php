@extends('layouts.migration')

@section('title', 'Importação | ' . config('app.name'))

@section('content')
    @php
        $recentImports = collect($recentImports ?? []);
        $administrations = collect($administrations ?? []);
        $selectedAdministrationId = $selectedAdministrationId ?? null;
    @endphp

    <section class="hero">
        <span class="eyebrow">Importação de planilhas</span>
        <h1>Importe uma planilha para análise.</h1>
        <p class="hero-copy">
            O arquivo é analisado antes da importação definitiva, permitindo revisão dos dados, das igrejas detectadas e da administração vinculada.
        </p>
    </section>

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
                                    : 'n/a';
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
                                    <strong>{{ $import['arquivo_nome'] ?? 'n/a' }}</strong>
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
                                    <strong>{{ $responsible !== '' ? $responsible : 'n/a' }}</strong>
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

    <section class="section">
        <div class="table-shell">
            <form method="POST" action="{{ route('migration.spreadsheets.store') }}" class="form-shell" enctype="multipart/form-data">
                @csrf

                <div class="field-grid">
                    <label>
                        Responsável
                        <select name="usuario_id" required>
                            <option value="">Selecione</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((int) old('usuario_id') === (int) $user->id)>
                                    {{ $user->nome }}{{ $user->email ? ' - ' . $user->email : '' }}
                                </option>
                            @endforeach
                        </select>
                    </label>

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
                    A importação detecta as igrejas diretamente do CSV e cadastra os produtos a partir dessas linhas. O arquivo é analisado antes da importação definitiva. Tamanho máximo: 50MB.
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
@endsection
