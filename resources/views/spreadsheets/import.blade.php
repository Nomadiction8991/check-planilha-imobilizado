@extends('layouts.migration')

@section('title', 'Importar Planilha de Bens | ' . config('app.name'))

@section('content')
    <div class="container py-6 max-w-4xl mx-auto px-4">
        <header class="mb-6">
            <span class="text-sm font-semibold tracking-wide uppercase text-indigo-600 dark:text-indigo-400">Importação de Dados</span>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">Importar Planilha de Bens Imobilizados</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Envie o relatório emitido em formato CSV, XLS ou XLSX para conferência e conciliação.</p>
        </header>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-900 text-red-700 dark:text-red-300 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('migration.spreadsheets.store') }}" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm space-y-6">
            @csrf

            <div>
                <label for="administracao_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Administração *</label>
                <select name="administracao_id" id="administracao_id" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Selecione uma administração...</option>
                    @foreach ($administrations as $adm)
                        <option value="{{ $adm['id'] }}" {{ old('administracao_id') == $adm['id'] ? 'selected' : '' }}>
                            {{ $adm['descricao'] ?? $adm['nome'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="church_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Comum / Igreja Fallback (Opcional)</label>
                <select name="church_id" id="church_id" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Auto-detectar pelo código da planilha...</option>
                    @foreach ($churches as $church)
                        <option value="{{ $church['id'] }}" {{ old('church_id') == $church['id'] ? 'selected' : '' }}>
                            {{ $church['codigo'] }} - {{ $church['descricao'] }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Se não selecionada, as comuns serão detectadas automaticamente pelos códigos presentes nas linhas.</p>
            </div>

            <div>
                <label for="arquivo_csv" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Arquivo da Planilha (CSV, XLS ou XLSX) *</label>
                <input type="file" name="arquivo_csv" id="arquivo_csv" accept=".csv,.txt,.xls,.xlsx" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-300">
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Formatos aceitos: Relatório de Bens Imobilizado em <strong>CSV</strong>, <strong>XLS</strong> (Excel 97-2004) ou <strong>XLSX</strong> até 50MB.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('migration.spreadsheets.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg">Cancelar</a>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-sm">Processar e Analisar</button>
            </div>
        </form>
    </div>
@endsection
