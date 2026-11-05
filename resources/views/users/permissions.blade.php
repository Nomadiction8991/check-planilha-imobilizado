@extends('layouts.migration')

@section('title', 'Permissões do Usuário | ' . config('app.name'))

@section('content')
    <style>
        .users-permissions { color: var(--ui-text); font-size: 16px; line-height: 1.5; }
        .users-permissions h1 { font-size: 24px; line-height: 1.2; }
        .users-permissions .btn.primary { background: var(--product-primary); border-color: var(--product-primary); color: white; }
        .users-permissions .btn.primary:hover { background: var(--product-primary-hover); }
        .users-permissions :is(input, button, a):focus-visible { outline: 3px solid var(--ui-focus); outline-offset: 2px; }
        .users-permissions .form-actions { margin-top: 20px; display: flex; flex-wrap: wrap; gap: 12px; }
        @media (min-width: 640px) { .users-permissions h1 { font-size: 30px; } .users-permissions .form-actions { justify-content: flex-end; } }
    </style>
    <div class="users-permissions">
    <section class="hero">
        <span class="eyebrow">Segurança e Acesso</span>
        <h1>Controlar permissões de {{ $user->nome }}.</h1>
        <p class="hero-copy">
            Defina quais módulos e ações este usuário pode acessar. As permissões são aplicadas imediatamente após salvar.
            Usuários com acesso restrito verão apenas as opções permitidas no menu.
        </p>
    </section>

    <div class="section">
        <form action="{{ route('migration.users.permissions.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="permissions-panel">
                @foreach($groups as $group)
                    <div class="permissions-group">
                        <div class="permissions-group-head">
                            <strong>{{ $group['title'] }}</strong>
                            <p>{{ $group['description'] }}</p>
                        </div>

                        <div class="permissions-grid">
                            @foreach($group['abilities'] as $ability)
                                <label class="permission-item" for="ability_{{ str_replace('.', '_', $ability['key']) }}">
                                    <input type="checkbox" 
                                           name="abilities[{{ $ability['key'] }}]" 
                                           id="ability_{{ str_replace('.', '_', $ability['key']) }}"
                                           value="1"
                                           @if($currentPermissions[$ability['key']] ?? false) checked @endif>
                                    <div class="permission-info">
                                        <strong>{{ $ability['label'] }}</strong>
                                        <small>{{ $ability['description'] }}</small>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="form-actions">
                    <a href="{{ route('migration.users.index') }}" class="btn">Cancelar</a>
                    <button type="submit" class="btn primary">Salvar Permissões</button>
                </div>
            </div>
        </form>
    </div>
    </div>
@endsection
