@php
    $editing = isset($user);
    $selectedAdministrationId = old('administracao_id', $user->administracao_id ?? '');
    $selectedAdministrationIds = old(
        'administracoes_permitidas',
        $editing ? ($user->administracoes_permitidas ?? [$user->administracao_id ?? null]) : []
    );
    $selectedAdministrationIds = array_values(array_filter(array_map('intval', (array) $selectedAdministrationIds)));
    $selectedState = old('endereco_estado', $user->endereco_estado ?? '');
    $selectedCity = old('endereco_cidade', $user->endereco_cidade ?? '');
    $isActive = (int) old('ativo', $editing ? $user->ativo : 1) === 1;
    $isMarried = (int) old('casado', $user->casado ?? 0) === 1;
    $rgEqualsCpf = (int) old('rg_igual_cpf', $user->rg_igual_cpf ?? 0) === 1;
    $spouseRgEqualsCpf = (int) old('rg_conjuge_igual_cpf', $user->rg_conjuge_igual_cpf ?? 0) === 1;
    $hasAdministrations = isset($administrations) && $administrations->isNotEmpty();
    $permissionConfig = (array) config('legacy.permissions', []);
    $permissionGroups = (array) ($permissionConfig['groups'] ?? []);
    $defaultPermissions = (array) ($permissionConfig['defaults'] ?? []);
    $selectedPermissions = old('permissions');
    if (is_array($selectedPermissions)) {
        $selectedPermissionKeys = $selectedPermissions;
    } else {
        $currentPermissionMap = $editing ? (array) ($user->permissions ?? $defaultPermissions) : $defaultPermissions;
        $selectedPermissionKeys = array_keys(array_filter($currentPermissionMap));
    }
    $canManagePermissions = !empty($legacyPermissions['users.permissions.manage'] ?? false);
    if ($selectedAdministrationIds === [] && $hasAdministrations && $administrations->count() === 1) {
        $selectedAdministrationIds = [(int) $administrations->first()->id];
    }
@endphp

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
        <form method="POST" action="{{ $editing ? route('migration.users.update', ['user' => $user->id]) : route('migration.users.store') }}" class="form-shell">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="form-section">
                <div class="section-head">
                    <div>
                        <h2>Dados principais</h2>
                        <p class="form-section__copy">Defina a administração, o estado da conta e as credenciais básicas do usuário.</p>
                    </div>
                </div>

                <div class="field-grid">
                    <label>
                        Administração
                        <select name="administracao_id" required @disabled(!$hasAdministrations)>
                            <option value="">Selecione</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected((int) $selectedAdministrationId === (int) $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                        @unless ($hasAdministrations)
                            <span class="field-note">Cadastre uma administração antes de salvar usuários.</span>
                        @endunless
                    </label>

                    <label>
                        Status
                        <select name="ativo">
                            <option value="1" @selected($isActive)>Ativo</option>
                            <option value="0" @selected(!$isActive)>Inativo</option>
                        </select>
                    </label>

                    <label>
                        Nome completo
                        <input type="text" name="nome" value="{{ old('nome', $user->nome ?? '') }}" required maxlength="255">
                    </label>

                    <label>
                        E-mail
                        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required maxlength="255">
                    </label>

                    <label>
                        {{ $editing ? 'Nova senha' : 'Senha' }}
                        <input type="password" name="senha" minlength="6">
                    </label>

                    <label>
                        {{ $editing ? 'Confirmar nova senha' : 'Confirmar senha' }}
                        <input type="password" name="confirmar_senha" minlength="6">
                    </label>
                </div>

                @if ($editing)
                    <p class="field-note">Deixe a senha em branco para manter a atual.</p>
                @endif
            </div>

            <div class="form-section">
                <div class="section-head">
                    <div>
                        <h2>Identificação e contato</h2>
                        <p class="form-section__copy">Reúna os documentos e o telefone que ajudam na conferência do cadastro.</p>
                    </div>
                </div>

                <div class="field-grid">
                    <label>
                        CPF
                        <input type="text" name="cpf" id="cpf" value="{{ old('cpf', $user->cpf ?? '') }}" required maxlength="14" data-mask="cpf" inputmode="numeric">
                    </label>

                    <label>
                        RG
                        <input type="text" name="rg" id="rg" value="{{ old('rg', $user->rg ?? '') }}" required maxlength="30">
                    </label>

                    <label>
                        Telefone
                        <input type="text" name="telefone" id="telefone" value="{{ old('telefone', $user->telefone ?? '') }}" required maxlength="15" data-mask="phone" inputmode="numeric">
                    </label>

                    <label class="check-inline check-inline--block">
                        <input type="checkbox" name="rg_igual_cpf" id="rg_igual_cpf" value="1" @checked($rgEqualsCpf)>
                        <span>RG igual ao CPF</span>
                    </label>
                </div>
            </div>

            @if ($canManagePermissions)
                <div class="form-section">
                    <div class="section-head">
                        <div>
                            <h2>Permissões do sistema</h2>
                            <p class="form-section__copy">Marque apenas o que este usuário poderá acessar. As permissões podem ser ajustadas depois.</p>
                        </div>
                    </div>

                    <div class="permissions-panel">
                        <input type="hidden" name="permissions_present" value="1">

                        @foreach ($permissionGroups as $group)
                            <div class="permissions-group">
                                <div class="permissions-group-head">
                                    <strong>{{ $group['title'] }}</strong>
                                    <p>{{ $group['description'] }}</p>
                                </div>

                                <div class="permissions-grid">
                                    @foreach ($group['abilities'] as $ability)
                                        <label class="permission-item">
                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $ability['key'] }}"
                                                @checked(in_array($ability['key'], $selectedPermissionKeys, true))
                                            >
                                            <span>
                                                <strong>{{ $ability['label'] }}</strong>
                                                <small>{{ $ability['description'] }}</small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="form-section">
                <div class="section-head">
                    <div>
                        <h2>Administrações permitidas</h2>
                        <p class="form-section__copy">Selecione as administrações adicionais que este usuário poderá usar na importação. A administração principal será incluída automaticamente.</p>
                    </div>
                </div>

                <div class="permissions-panel">
                    @if ($hasAdministrations)
                        <div class="permissions-grid">
                            @foreach ($administrations as $administration)
                                <label class="permission-item">
                                    <input
                                        type="checkbox"
                                        name="administracoes_permitidas[]"
                                        value="{{ $administration->id }}"
                                        @checked(in_array((int) $administration->id, $selectedAdministrationIds, true))
                                    >
                                    <span>
                                        <strong>#{{ $administration->id }} - {{ $administration->descricao }}</strong>
                                        <small>Permite importar e consultar dados vinculados a esta administração.</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <p class="field-note">Cadastre uma administração antes de configurar o acesso do usuário.</p>
                    @endif
                </div>
            </div>

            <div class="form-section">
                <div class="section-head">
                    <div>
                        <h2>Estado civil e cônjuge</h2>
                        <p class="form-section__copy">Informe os dados complementares somente quando o usuário for casado.</p>
                    </div>

                    <label class="check-inline">
                        <input type="checkbox" name="casado" id="casado" value="1" @checked($isMarried)>
                        <span>Sou casado(a)</span>
                    </label>
                </div>

                <div id="spouse_fields" class="field-grid" style="{{ $isMarried ? '' : 'display:none;' }}">
                    <label>
                        Nome do cônjuge
                        <input type="text" name="nome_conjuge" id="nome_conjuge" value="{{ old('nome_conjuge', $user->nome_conjuge ?? '') }}" maxlength="255">
                    </label>

                    <label>
                        CPF do cônjuge
                        <input type="text" name="cpf_conjuge" id="cpf_conjuge" value="{{ old('cpf_conjuge', $user->cpf_conjuge ?? '') }}" maxlength="14" data-mask="cpf" inputmode="numeric">
                    </label>

                    <label>
                        RG do cônjuge
                        <input type="text" name="rg_conjuge" id="rg_conjuge" value="{{ old('rg_conjuge', $user->rg_conjuge ?? '') }}" maxlength="30">
                    </label>

                    <label>
                        Telefone do cônjuge
                        <input type="text" name="telefone_conjuge" id="telefone_conjuge" value="{{ old('telefone_conjuge', $user->telefone_conjuge ?? '') }}" maxlength="15" data-mask="phone" inputmode="numeric">
                    </label>

                    <label class="check-inline check-inline--block">
                        <input type="checkbox" name="rg_conjuge_igual_cpf" id="rg_conjuge_igual_cpf" value="1" @checked($spouseRgEqualsCpf)>
                        <span>RG do cônjuge igual ao CPF</span>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <div class="section-head">
                    <div>
                        <h2>Endereço</h2>
                        <p class="form-section__copy">CEP, rua e localização para contato e referência administrativa. O CEP preenche os campos de endereço e o estado/cidade seguem o fluxo de localidades.</p>
                    </div>
                </div>

                <div class="field-grid">
                    <label>
                        CEP
                        <input type="text" name="endereco_cep" id="endereco_cep" value="{{ old('endereco_cep', $user->endereco_cep ?? '') }}" maxlength="9" data-mask="cep" inputmode="numeric">
                    </label>

                    <label>
                        Logradouro
                        <input type="text" name="endereco_logradouro" id="endereco_logradouro" value="{{ old('endereco_logradouro', $user->endereco_logradouro ?? '') }}" maxlength="255">
                    </label>

                    <label>
                        Número
                        <input type="text" name="endereco_numero" value="{{ old('endereco_numero', $user->endereco_numero ?? '') }}" maxlength="50">
                    </label>

                    <label>
                        Complemento
                        <input type="text" name="endereco_complemento" value="{{ old('endereco_complemento', $user->endereco_complemento ?? '') }}" maxlength="255">
                    </label>

                    <label>
                        Bairro
                        <input type="text" name="endereco_bairro" id="endereco_bairro" value="{{ old('endereco_bairro', $user->endereco_bairro ?? '') }}" maxlength="255">
                    </label>

                    <label>
                        Cidade
                        <select name="endereco_cidade" id="endereco_cidade" disabled data-selected-city="{{ $selectedCity }}">
                            <option value="">Selecione um estado primeiro</option>
                        </select>
                    </label>

                    <label>
                        UF
                        <select name="endereco_estado" id="endereco_estado">
                            <option value="">Selecione</option>
                            @foreach ($states as $state)
                                <option value="{{ $state }}" @selected($selectedState === $state)>{{ $state }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            <div class="inline-actions">
                <button class="btn primary" type="submit" @disabled(!$hasAdministrations)>{{ $editing ? 'Salvar alterações' : 'Salvar usuário' }}</button>
                <a class="btn" href="{{ route('migration.users.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
</section>

<script>
    (() => {
        const marriedCheckbox = document.getElementById('casado');
        const spouseFields = document.getElementById('spouse_fields');
        const cpfInput = document.getElementById('cpf');
        const rgInput = document.getElementById('rg');
        const rgEqualsCpfCheckbox = document.getElementById('rg_igual_cpf');
        const spouseCpfInput = document.getElementById('cpf_conjuge');
        const spouseRgInput = document.getElementById('rg_conjuge');
        const spouseRgEqualsCpfCheckbox = document.getElementById('rg_conjuge_igual_cpf');
        const zipInput = document.getElementById('endereco_cep');

        function digitsOnly(value) {
            return value.replace(/\D/g, '');
        }

        function toggleSpouseFields() {
            const visible = marriedCheckbox.checked;
            spouseFields.style.display = visible ? '' : 'none';
        }

        function syncRgWithCpf(checkbox, cpfField, rgField) {
            if (!checkbox || !cpfField || !rgField) {
                return;
            }

            if (checkbox.checked) {
                rgField.value = digitsOnly(cpfField.value);
                rgField.readOnly = true;
            } else {
                rgField.readOnly = false;
            }
        }

        async function lookupZip() {
            const zip = digitsOnly(zipInput.value);

            if (zip.length !== 8) {
                return;
            }

            try {
                const response = await fetch(`https://viacep.com.br/ws/${zip}/json/`);
                const data = await response.json();

                if (data.erro) {
                    return;
                }

                document.getElementById('endereco_logradouro').value = data.logradouro || '';
                document.getElementById('endereco_bairro').value = data.bairro || '';
            } catch (error) {
                console.error('Erro ao buscar CEP:', error);
            }
        }

        marriedCheckbox?.addEventListener('change', toggleSpouseFields);
        rgEqualsCpfCheckbox?.addEventListener('change', () => syncRgWithCpf(rgEqualsCpfCheckbox, cpfInput, rgInput));
        cpfInput?.addEventListener('input', () => syncRgWithCpf(rgEqualsCpfCheckbox, cpfInput, rgInput));
        spouseRgEqualsCpfCheckbox?.addEventListener('change', () => syncRgWithCpf(spouseRgEqualsCpfCheckbox, spouseCpfInput, spouseRgInput));
        spouseCpfInput?.addEventListener('input', () => syncRgWithCpf(spouseRgEqualsCpfCheckbox, spouseCpfInput, spouseRgInput));
        zipInput?.addEventListener('blur', lookupZip);

        toggleSpouseFields();
        syncRgWithCpf(rgEqualsCpfCheckbox, cpfInput, rgInput);
        syncRgWithCpf(spouseRgEqualsCpfCheckbox, spouseCpfInput, spouseRgInput);
    })();
</script>
