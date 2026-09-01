@extends('layouts.migration')

@section('title', 'Assinar Documentos - Acesso Público')

@section('content')
    <section class="hero" style="max-width: 720px; margin-inline: auto;">
        <span class="eyebrow">Acesso público</span>
        <h1>Selecione sua igreja para continuar.</h1>
        <p class="hero-copy">
            Use esta tela para iniciar o atendimento público e acessar apenas os itens vinculados à igreja escolhida.
        </p>
    </section>

    @if (session('status'))
        <div class="flash-stack" style="max-width: 720px; margin-inline: auto;">
            <div class="flash {{ session('status_type', 'info') === 'error' ? 'error' : 'success' }}">
                <strong>{{ session('status') }}</strong>
            </div>
        </div>
    @endif

    <section class="section" style="max-width: 720px; margin-inline: auto;">
        <div class="table-shell">
            <form method="POST" action="{{ route('public.access.store') }}" class="form-shell">
                @csrf

                <div class="field-grid">
                    <label for="public-church-search">
                        Buscar igreja
                        <input
                            id="public-church-search"
                            type="search"
                            placeholder="Digite para filtrar"
                            autocomplete="off"
                            aria-controls="comum_id"
                            data-public-church-search
                        >
                    </label>
                    <label>
                        Igreja
                        <select id="comum_id" name="comum_id" required data-public-church-select>
                            <option value="">Selecione</option>
                            @foreach ($churches as $church)
                                <option value="{{ $church->id }}">{{ $church->descricao }}</option>
                            @endforeach
                        </select>
                    </label>
                    <p id="public-church-search-status" class="helper" role="status" aria-live="polite" hidden data-public-church-status></p>
                </div>

                @error('comum_id')
                    <div class="flash error">
                        <strong>{{ $message }}</strong>
                    </div>
                @enderror

                <div class="inline-actions">
                    <button type="submit" class="btn primary">Continuar</button>
                    <a href="{{ route('migration.login') }}" class="btn">Voltar ao login</a>
                </div>
            </form>
        </div>
    </section>
    <script>
        (() => {
            const search = document.querySelector('[data-public-church-search]');
            const select = document.querySelector('[data-public-church-select]');
            const status = document.querySelector('[data-public-church-status]');
            if (!search || !select || !status) return;
            const options = Array.from(select.options);
            const placeholder = options.find((o) => o.value === '');
            const churchOptions = options.filter((o) => o.value !== '');
            const applyFilter = () => {
                const term = search.value.trim().toLowerCase();
                let visible = 0;
                churchOptions.forEach((opt) => {
                    const match = term === '' || opt.textContent.toLowerCase().includes(term);
                    opt.hidden = !match;
                    opt.disabled = !match;
                    if (match) visible += 1;
                });
                if (select.value !== '' && select.options[select.selectedIndex]?.hidden) {
                    select.value = '';
                }
                if (visible === 0 && term !== '') {
                    status.textContent = 'Nenhuma igreja encontrada para "' + search.value.trim() + '".';
                    status.hidden = false;
                    select.disabled = true;
                } else {
                    status.textContent = '';
                    status.hidden = true;
                    select.disabled = false;
                    if (placeholder) {
                        placeholder.hidden = false;
                        placeholder.disabled = false;
                    }
                }
            };
            search.addEventListener('input', applyFilter);
            search.addEventListener('search', applyFilter);
        })();
    </script>
@endsection
