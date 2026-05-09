@extends('layouts.migration')

@section('title', 'Permissões do Usuário | ' . config('app.name'))

@section('content')
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

                <div class="form-actions" style="margin-top: 20px; display: flex; gap: 12px; justify-content: flex-end;">
                    <a href="{{ route('migration.users.index') }}" class="btn">Cancelar</a>
                    <button type="submit" class="btn primary">Salvar Permissões</button>
                </div>
            </div>
        </form>
    </div>
@endsection
