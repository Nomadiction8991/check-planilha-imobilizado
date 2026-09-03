@php
    $query = request()->query();
    $chips = [];
    $brazilStates = config('brazil.states', []);
    $statusLabels = $statusOptions ?? [];

    $findLabel = static function ($collection, $id): ?string {
        if ($collection === null || (int) $id <= 0) return null;
        foreach ($collection as $item) {
            if ((int) ($item->id ?? 0) === (int) $id) {
                return trim((string) ($item->descricao ?? $item->label ?? ''));
            }
        }
        return null;
    };

    $removeUrl = static function (array $currentQuery, string $key): string {
        $next = $currentQuery;
        unset($next['pagina']);
        if ($key === 'only_new') {
            unset($next['somente_novos']);
            if (($next['status'] ?? null) === 'novos') {
                unset($next['status']);
            }
        } else {
            unset($next[$key]);
            // aliases: busca pode vir de nome/codigo mas normaliza para 'busca'
            if ($key === 'busca') { unset($next['nome'], $next['codigo']); }
        }
        $qs = http_build_query(array_filter($next, static fn ($v) => $v !== '' && $v !== null));
        $base = request()->url();
        return $qs !== '' ? $base . '?' . $qs : $base;
    };

    if (($filters->administrationId ?? null) !== null) {
        $id = $filters->administrationId;
        $label = $findLabel($administrations ?? null, $id) ?: ('#' . $id);
        $chips[] = ['key' => 'administracao_id', 'label' => 'Administração: ' . $label, 'aria' => 'Remover filtro de administração'];
    }
    if (($filters->comumId ?? null) !== null) {
        $id = $filters->comumId;
        $churchLabel = null;
        if (isset($churches)) {
            foreach ($churches as $c) {
                if ((int) ($c->id ?? 0) === (int) $id) {
                    $churchLabel = trim(($c->codigo ?? '') . ' - ' . ($c->descricao ?? ''));
                    $churchLabel = trim($churchLabel, ' -');
                    break;
                }
            }
        }
        $chips[] = ['key' => 'comum_id', 'label' => 'Igreja: ' . ($churchLabel ?: ('#' . $id)), 'aria' => 'Remover filtro de igreja'];
    }
    if (($filters->state ?? null) !== null && $filters->state !== '') {
        $uf = $filters->state;
        $stateName = $brazilStates[$uf] ?? '';
        $chips[] = ['key' => 'estado', 'label' => 'Estado: ' . $uf . ($stateName !== '' ? ' — ' . $stateName : ''), 'aria' => 'Remover filtro de estado'];
    }
    if (($filters->dependencyId ?? null) !== null) {
        $id = $filters->dependencyId;
        $label = $findLabel($dependencies ?? null, $id) ?: ('#' . $id);
        $chips[] = ['key' => 'dependencia_id', 'label' => 'Dependência: ' . $label, 'aria' => 'Remover filtro de dependência'];
    }
    if (($filters->assetTypeId ?? null) !== null) {
        $id = $filters->assetTypeId;
        $label = null;
        if (isset($assetTypes)) {
            foreach ($assetTypes as $t) {
                if ((int) ($t->id ?? 0) === (int) $id) {
                    $code = trim((string) ($t->codigo ?? ''));
                    $desc = trim((string) ($t->descricao ?? ''));
                    $label = $code !== '' && $desc !== '' ? $code . ' - ' . $desc : ($desc !== '' ? $desc : $code);
                    break;
                }
            }
        }
        $chips[] = ['key' => 'tipo_bem_id', 'label' => 'Tipo: ' . ($label ?: ('#' . $id)), 'aria' => 'Remover filtro de tipo de bem'];
    }
    if (($filters->status ?? '') !== '' && $filters->status !== 'novos') {
        $label = $statusLabels[$filters->status] ?? $filters->status;
        $chips[] = ['key' => 'status', 'label' => 'Status: ' . $label, 'aria' => 'Remover filtro de status'];
    }
    if (($filters->search ?? '') !== '') {
        $chips[] = ['key' => 'busca', 'label' => 'Busca: "' . $filters->search . '"', 'aria' => 'Remover filtro de busca'];
    }
    if (($filters->onlyNew ?? false) === true) {
        $chips[] = ['key' => 'only_new', 'label' => 'Somente novos', 'aria' => 'Remover filtro de somente novos'];
    }
@endphp

@if (count($chips) > 0)
    <div class="active-filter-chips" role="status" aria-live="polite" aria-label="Filtros ativos" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:10px 0 14px;">
        @foreach ($chips as $chip)
            <a href="{{ $removeUrl($query, $chip['key']) }}"
               class="chip chip--filter"
               aria-label="{{ $chip['aria'] }}: {{ $chip['label'] }}"
               title="Remover {{ $chip['label'] }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;border:1px solid var(--line, #e5e7eb);background:var(--surface, #fff);color:var(--ink, #111827);font-size:13px;line-height:1;text-decoration:none;box-shadow:var(--shadow-soft, 0 1px 2px rgba(0,0,0,.06));">
                <span>{{ $chip['label'] }}</span>
                <span aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:999px;background:color-mix(in srgb, var(--accent, #0f766e) 12%, transparent);font-weight:700;">×</span>
            </a>
        @endforeach
        <a href="{{ request()->url() }}" class="chip chip--clear" style="font-size:12px;color:var(--muted, #6b7280);text-decoration:underline;">Limpar todos</a>
    </div>
@endif
