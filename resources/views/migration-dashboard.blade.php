@extends('layouts.migration')

@section('title', 'Painel | ' . config('app.name'))
@section('bodyClass', 'page-dashboard')

@section('content')
    @php
        $modules = collect($snapshot->modules);
        $totalRecords = $modules->sum(fn ($module) => (int) ($module->records ?? 0));
        $modulesWithRecords = $modules->filter(fn ($module) => $module->records !== null)->count();
        $architectureCounts = collect($snapshot->architectureCounts);
        $architectureTotal = $architectureCounts->sum();
    @endphp

    <section class="hero">
        <span class="eyebrow">Painel operacional</span>
        <h1>Resumo do sistema.</h1>
        <p class="hero-copy">
            As cores abaixo indicam o papel de cada módulo: estrutura, catálogo, inventário, acesso e fluxo.
        </p>

        <div class="palette-legend" aria-label="Legenda das cores">
            <div class="palette-legend-head">
                <strong>Leitura das cores</strong>
                <span>O chip do módulo mostra a categoria principal de cada área.</span>
            </div>
            <div class="palette-legend-grid">
                <div class="palette-legend-item">
                    <span class="module-tone module-tone--structure">Estrutura</span>
                    <p>Cadastros base e vínculos da operação.</p>
                </div>
                <div class="palette-legend-item">
                    <span class="module-tone module-tone--catalog">Catálogo</span>
                    <p>Tabelas mestres e classificação dos bens.</p>
                </div>
                <div class="palette-legend-item">
                    <span class="module-tone module-tone--inventory">Inventário</span>
                    <p>Itens, revisão e manutenção do acervo.</p>
                </div>
                <div class="palette-legend-item">
                    <span class="module-tone module-tone--access">Acesso</span>
                    <p>Usuários, perfis e permissões.</p>
                </div>
                <div class="palette-legend-item">
                    <span class="module-tone module-tone--audit">Governança</span>
                    <p>Auditoria, trilha de eventos e ações sensíveis.</p>
                </div>
                <div class="palette-legend-item">
                    <span class="module-tone module-tone--flow">Fluxo</span>
                    <p>Relatórios e importações em andamento.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>Indicadores rápidos</h2>
                <p>Os números mais usados agora ficam concentrados no painel.</p>
            </div>
        </div>

        <div class="stats-grid">
            <article class="stat-card">
                <span class="stat-label">Banco de dados</span>
                <strong class="stat-value">{{ $snapshot->databaseReachable ? 'Conectado' : 'Indisponível' }}</strong>
                <p class="stat-copy">
                    {{ $snapshot->databaseReachable
                        ? trim(($snapshot->databaseDriver ?? 'n/a') . ' · ' . ($snapshot->databaseName ?? 'n/a'))
                        : ($snapshot->databaseError ?? 'Não foi possível validar a conexão.') }}
                </p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Módulos monitorados</span>
                <strong class="stat-value">{{ number_format($modules->count(), 0, ',', '.') }}</strong>
                <p class="stat-copy">Áreas principais acompanhadas pelo inventário.</p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Módulos com contagem</span>
                <strong class="stat-value">{{ number_format($modulesWithRecords, 0, ',', '.') }}</strong>
                <p class="stat-copy">Itens que já retornam totalização da base.</p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Registros contabilizados</span>
                <strong class="stat-value">{{ number_format($totalRecords, 0, ',', '.') }}</strong>
                <p class="stat-copy">Soma dos registros conhecidos pelos módulos.</p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Arquivos da arquitetura</span>
                <strong class="stat-value">{{ number_format($architectureTotal, 0, ',', '.') }}</strong>
                <p class="stat-copy">Contagem total das pastas legadas mapeadas.</p>
            </article>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>Módulos principais</h2>
                <p>Resumo das áreas acompanhadas pelo sistema.</p>
            </div>
        </div>

        <div class="module-grid">
            @foreach ($snapshot->modules as $module)
                <article class="module-card module-tone--{{ $module->tone }}">
                    <span class="module-badge">{{ $module->title }}</span>
                    <span class="module-tone module-tone--{{ $module->tone }}">{{ $module->category }}</span>
                    <p class="module-copy">{{ $module->description }}</p>
                    <p>
                        <strong>Registros:</strong>
                        {{ $module->records !== null ? $module->records : 'n/a' }}
                    </p>
                </article>
            @endforeach
        </div>
    </section>

@endsection
