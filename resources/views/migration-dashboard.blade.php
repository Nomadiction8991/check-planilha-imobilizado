@extends('layouts.migration')

@section('title', 'Dashboard do sistema.')

@section('content')
    <div class="shell">
        <div class="hero">
            <div class="eyebrow">Dashboard do sistema.</div>
            <h1>Indicadores rápidos</h1>
            <p class="hero-copy">Total de registros: {{ array_sum($snapshot->architectureCounts) }}</p>
        </div>

        <div style="margin-top: 24px; display: grid; gap: 12px;">
            <div class="panel" style="padding: 20px;">
                <h2>Módulos principais</h2>
                <div style="display: grid; gap: 8px; margin-top: 12px;">
                    @foreach ($snapshot->modules as $module)
                        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--line);">
                            <strong>{{ $module->title }}</strong>
                            <span class="metric-label">{{ $module->category }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin-top: 12px;">
                @foreach ($snapshot->architectureCounts as $key => $count)
                    <div class="stat-card" style="padding: 16px; text-align: center;">
                        <div class="metric-label">{{ ucfirst($key) }}</div>
                        <div style="font-size: 28px; font-weight: 700; margin-top: 4px;">{{ $count }}</div>
                    </div>
                @endforeach
            </div>

            <div class="panel" style="padding: 20px; margin-top: 12px;">
                <h2>Indicadores por igreja</h2>
                <div style="display: grid; gap: 8px; margin-top: 12px;">
                    @foreach ($churches as $church)
                        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--line);">
                            <strong>{{ $church->descricao }}</strong>
                            <span class="metric-label">{{ $church->codigo }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 12px;">
                <div class="stat-card" style="padding: 16px; text-align: center;">
                    <div class="metric-label">Igrejas</div>
                    <div style="font-size: 28px; font-weight: 700; margin-top: 4px;">0</div>
                </div>
                <div class="stat-card" style="padding: 16px; text-align: center;">
                    <div class="metric-label">Produtos</div>
                    <div style="font-size: 28px; font-weight: 700; margin-top: 4px;">0</div>
                </div>
                <div class="stat-card" style="padding: 16px; text-align: center;">
                    <div class="metric-label">Dependências</div>
                    <div style="font-size: 28px; font-weight: 700; margin-top: 4px;">0</div>
                </div>
                <div class="stat-card" style="padding: 16px; text-align: center;">
                    <div class="metric-label">Tipos de bem</div>
                    <div style="font-size: 28px; font-weight: 700; margin-top: 4px;">0</div>
                </div>
                <div class="stat-card" style="padding: 16px; text-align: center;">
                    <div class="metric-label">Usuários</div>
                    <div style="font-size: 28px; font-weight: 700; margin-top: 4px;">0</div>
                </div>
                <div class="stat-card" style="padding: 16px; text-align: center;">
                    <div class="metric-label">Auditoria</div>
                    <div style="font-size: 28px; font-weight: 700; margin-top: 4px;">0</div>
                </div>
            </div>
        </div>
    </div>
@endsection
