<?php
// Script para converter usuario_editar.php para UPPERCASE com UTF-8 correto
// Carrega o arquivo original em uma variável
$srcFile = __DIR__ . '/../app/views/usuarios/usuario_editar.php';
$content = file_get_contents($srcFile);

// ============ APLICAR CONVERSÕES ============

// 1. Corrigir encoding - substituições de caracteres mal codificados
$corrections = [
    'Autenticação' => 'AUTENTICAÇÃO',
    'autenticação' => 'AUTENTICAÇÃO',
    'Autenticação' => 'AUTENTICAÇÃO',
    'Visualizar / Editar lógica:' => 'VISUALIZAR / EDITAR LÓGICA:',
    'lógica:' => 'LÓGICA:',
    'Visualizar / Editar lógica' => 'VISUALIZAR / EDITAR LÓGICA',
    
    'Qualquer usuário acessa sua própria página em modo edição' => 'QUALQUER USUÁRIO ACESSA SUA PRÓPRIA PÁGINA EM MODO EDIÇÃO',
    'própria' => 'PRÓPRIA',
    'edição' => 'EDIÇÃO',
    
    'Administrador pode visualizar outros usuários em modo somente leitura' => 'ADMINISTRADOR PODE VISUALIZAR OUTROS USUÁRIOS EM MODO SOMENTE LEITURA',
    'Administrador' => 'ADMINISTRADOR',
    'visualizar' => 'VISUALIZAR',
    'visualizar outros' => 'VISUALIZAR OUTROS',
    'outros usuários' => 'OUTROS USUÁRIOS',
    'somente leitura' => 'SOMENTE LEITURA',
    
    'Doador/Cônjuge não tem listagem, então só verá seu próprio usuário' => 'DOADOR/CÔNJUGE NÃO TEM LISTAGEM, ENTÃO SÓ VERÁ SEU PRÓPRIO USUÁRIO',
    'Doador' => 'DOADOR',
    'Cônjuge' => 'CÔNJUGE',
    'cônjuge' => 'CÔNJUGE',
    'conjuge' => 'CÔNJUGE',
    'Cônjuge não tem' => 'CÔNJUGE NÃO TEM',
    'listagem, então' => 'LISTAGEM, ENTÃO',
    'verá seu' => 'VERÁ SEU',
    
    'Se não for self e não for admin, bloquear' => 'SE NÃO FOR SELF E NÃO FOR ADMIN, BLOQUEAR',
    'Se não' => 'SE NÃO',
    
    'Voltar: self e admin vão para listagem; outros vão para index' => 'VOLTAR: SELF E ADMIN VÃO PARA LISTAGEM; OUTROS VÃO PARA INDEX',
    'Voltar:' => 'VOLTAR:',
    'vão para' => 'VÃO PARA',
    
    'jQuery e InputMask' => 'JQUERY E INPUTMASK',
    'jQuery' => 'JQUERY',
    'InputMask' => 'INPUTMASK',
    
    'SignaturePad' => 'SIGNATUREPAD',
    
    'Campo oculto: tipo de usuário' => 'CAMPO OCULTO: TIPO DE USUÁRIO',
    'Campo oculto' => 'CAMPO OCULTO',
    'tipo de usuário' => 'TIPO DE USUÁRIO',
    
    'DADOS BÁSICOS' => 'DADOS BÁSICOS',
    'Dados Básicos' => 'DADOS BÁSICOS',
    'Dados básicos' => 'DADOS BÁSICOS',
    'dados básicos' => 'DADOS BÁSICOS',
    'básicos' => 'BÁSICOS',
    
    'NOME COMPLETO' => 'NOME COMPLETO',
    'Nome Completo' => 'NOME COMPLETO',
    'Nome completo' => 'NOME COMPLETO',
    'nome completo' => 'NOME COMPLETO',
    
    'CPF' => 'CPF',
    'cpf' => 'CPF',
    
    'RG' => 'RG',
    'rg' => 'RG',
    'RG IGUAL AO CPF' => 'RG IGUAL AO CPF',
    'RG igual ao CPF' => 'RG IGUAL AO CPF',
    'RG igual' => 'RG IGUAL',
    'igual ao' => 'IGUAL AO',
    
    'DIGITE OS DÍGITOS DO RG' => 'DIGITE OS DÍGITOS DO RG',
    'Digite os dígitos do RG' => 'DIGITE OS DÍGITOS DO RG',
    'Digite' => 'DIGITE',
    'dígitos' => 'DÍGITOS',
    
    'FORMATAÇÃO AUTOMÁTICA' => 'FORMATAÇÃO AUTOMÁTICA',
    'Formatação automática' => 'FORMATAÇÃO AUTOMÁTICA',
    'Formatação' => 'FORMATAÇÃO',
    'automática' => 'AUTOMÁTICA',
    
    'HÍFEN ANTES DO ÚLTIMO DÍGITO' => 'HÍFEN ANTES DO ÚLTIMO DÍGITO',
    'Hífen antes' => 'HÍFEN ANTES',
    'Hífen' => 'HÍFEN',
    
    'TELEFONE' => 'TELEFONE',
    'Telefone' => 'TELEFONE',
    'telefone' => 'TELEFONE',
    
    'EMAIL' => 'EMAIL',
    'Email' => 'EMAIL',
    'email' => 'EMAIL',
    
    'DEIXE OS CAMPOS DE SENHA EM BRANCO' => 'DEIXE OS CAMPOS DE SENHA EM BRANCO PARA MANTER A SENHA ATUAL',
    'Deixe' => 'DEIXE',
    
    'NOVA SENHA' => 'NOVA SENHA',
    'Nova Senha' => 'NOVA SENHA',
    'Senha' => 'SENHA',
    'senha' => 'SENHA',
    
    'CONFIRMAR NOVA SENHA' => 'CONFIRMAR NOVA SENHA',
    'Confirmar Nova Senha' => 'CONFIRMAR NOVA SENHA',
    'Confirmar' => 'CONFIRMAR',
    
    'MÍNIMO DE 6 CARACTERES' => 'MÍNIMO DE 6 CARACTERES',
    'Mínimo' => 'MÍNIMO',
    'mínimo' => 'MÍNIMO',
    'caracteres' => 'CARACTERES',
    
    'USUÁRIO ATIVO' => 'USUÁRIO ATIVO',
    'Usuário Ativo' => 'USUÁRIO ATIVO',
    'ativo' => 'ATIVO',
    
    'ASSINATURA DIGITAL' => 'ASSINATURA DIGITAL',
    'Assinatura Digital' => 'ASSINATURA DIGITAL',
    'Assinatura' => 'ASSINATURA',
    'assinatura' => 'ASSINATURA',
    'Digital' => 'DIGITAL',
    'digital' => 'DIGITAL',
    
    'CLIQUE NO BOTÃO ABAIXO' => 'CLIQUE NO BOTÃO ABAIXO PARA ATUALIZAR SUA ASSINATURA DIGITAL',
    'Clique' => 'CLIQUE',
    'BOTÃO' => 'BOTÃO',
    'Botão' => 'BOTÃO',
    'botão' => 'BOTÃO',
    'abaixo' => 'ABAIXO',
    
    'CONTAINER DE PREVIEW' => 'CONTAINER DE PREVIEW DA ASSINATURA',
    'Container' => 'CONTAINER',
    'container' => 'CONTAINER',
    'Preview' => 'PREVIEW',
    'preview' => 'PREVIEW',
    
    'FAZER ASSINATURA' => 'FAZER ASSINATURA',
    'Fazer Assinatura' => 'FAZER ASSINATURA',
    'Fazer' => 'FAZER',
    'fazer' => 'FAZER',
    
    'CAMPO HIDDEN' => 'CAMPO HIDDEN PARA ARMAZENAR ASSINATURA EM BASE64',
    'Campo Hidden' => 'CAMPO HIDDEN PARA ARMAZENAR ASSINATURA EM BASE64',
    'Hidden' => 'HIDDEN',
    'hidden' => 'HIDDEN',
    
    'ESTADO CIVIL' => 'ESTADO CIVIL',
    'Estado Civil' => 'ESTADO CIVIL',
    'Estado' => 'ESTADO',
    'estado' => 'ESTADO',
    'Civil' => 'CIVIL',
    'civil' => 'CIVIL',
    
    'SOU CASADO(A)' => 'SOU CASADO(A)',
    'Sou Casado(a)' => 'SOU CASADO(A)',
    'Casado' => 'CASADO',
    'casado' => 'CASADO',
    
    'DADOS DO CÔNJUGE' => 'DADOS DO CÔNJUGE',
    'Dados do Cônjuge' => 'DADOS DO CÔNJUGE',
    'Dados do cônjuge' => 'DADOS DO CÔNJUGE',
    
    'NOME COMPLETO DO CÔNJUGE' => 'NOME COMPLETO DO CÔNJUGE',
    'Nome Completo do Cônjuge' => 'NOME COMPLETO DO CÔNJUGE',
    
    'CPF DO CÔNJUGE' => 'CPF DO CÔNJUGE',
    'CPF do Cônjuge' => 'CPF DO CÔNJUGE',
    'Cpf' => 'CPF',
    'cpf' => 'CPF',
    
    'RG DO CÔNJUGE' => 'RG DO CÔNJUGE',
    'RG do Cônjuge' => 'RG DO CÔNJUGE',
    
    'RG DO CÔNJUGE IGUAL AO CPF DO CÔNJUGE' => 'RG DO CÔNJUGE IGUAL AO CPF DO CÔNJUGE',
    'RG do Cônjuge igual' => 'RG DO CÔNJUGE IGUAL',
    
    'TELEFONE DO CÔNJUGE' => 'TELEFONE DO CÔNJUGE',
    'Telefone do Cônjuge' => 'TELEFONE DO CÔNJUGE',
    'Telefone do' => 'TELEFONE DO',
    
    'FAZER ASSINATURA DO CÔNJUGE' => 'FAZER ASSINATURA DO CÔNJUGE',
    'Fazer Assinatura do Cônjuge' => 'FAZER ASSINATURA DO CÔNJUGE',
    
    'ENDEREÇO' => 'ENDEREÇO',
    'Endereço' => 'ENDEREÇO',
    'endereço' => 'ENDEREÇO',
    
    'CEP' => 'CEP',
    'cep' => 'CEP',
    
    'LOGRADOURO' => 'LOGRADOURO',
    'Logradouro' => 'LOGRADOURO',
    'logradouro' => 'LOGRADOURO',
    
    'PREENCHA PARA BUSCAR AUTOMATICAMENTE' => 'PREENCHA PARA BUSCAR AUTOMATICAMENTE',
    'Preencha' => 'PREENCHA',
    'para buscar' => 'PARA BUSCAR',
    'automaticamente' => 'AUTOMATICAMENTE',
    
    'NÚMERO' => 'NÚMERO',
    'Número' => 'NÚMERO',
    'número' => 'NÚMERO',
    
    'COMPLEMENTO' => 'COMPLEMENTO',
    'Complemento' => 'COMPLEMENTO',
    'complemento' => 'COMPLEMENTO',
    
    'APTO, BLOCO, ETC' => 'APTO, BLOCO, ETC',
    'Apto' => 'APTO',
    'apto' => 'APTO',
    'Bloco' => 'BLOCO',
    'bloco' => 'BLOCO',
    
    'BAIRRO' => 'BAIRRO',
    'Bairro' => 'BAIRRO',
    'bairro' => 'BAIRRO',
    
    'CIDADE' => 'CIDADE',
    'Cidade' => 'CIDADE',
    'cidade' => 'CIDADE',
    
    'SELECIONE' => 'SELECIONE',
    'Selecione' => 'SELECIONE',
    'selecione' => 'SELECIONE',
    
    'ACRE' => 'ACRE',
    'acre' => 'ACRE',
    'ALAGOAS' => 'ALAGOAS',
    'alagoas' => 'ALAGOAS',
    'AMAPÁ' => 'AMAPÁ',
    'amapá' => 'AMAPÁ',
    'AMAZONAS' => 'AMAZONAS',
    'amazonas' => 'AMAZONAS',
    'BAHIA' => 'BAHIA',
    'bahia' => 'BAHIA',
    'CEARÁ' => 'CEARÁ',
    'ceará' => 'CEARÁ',
    'DISTRITO FEDERAL' => 'DISTRITO FEDERAL',
    'ESPÍRITO SANTO' => 'ESPÍRITO SANTO',
    'GOIÁS' => 'GOIÁS',
    'goiás' => 'GOIÁS',
    'MARANHÃO' => 'MARANHÃO',
    'maranhão' => 'MARANHÃO',
    'MATO GROSSO' => 'MATO GROSSO',
    'MATO GROSSO DO SUL' => 'MATO GROSSO DO SUL',
    'MINAS GERAIS' => 'MINAS GERAIS',
    'PARÁ' => 'PARÁ',
    'pará' => 'PARÁ',
    'PARAÍBA' => 'PARAÍBA',
    'paraíba' => 'PARAÍBA',
    'PARANÁ' => 'PARANÁ',
    'paraná' => 'PARANÁ',
    'PERNAMBUCO' => 'PERNAMBUCO',
    'pernambuco' => 'PERNAMBUCO',
    'PIAUÍ' => 'PIAUÍ',
    'piauí' => 'PIAUÍ',
    'RIO DE JANEIRO' => 'RIO DE JANEIRO',
    'RIO GRANDE DO NORTE' => 'RIO GRANDE DO NORTE',
    'RIO GRANDE DO SUL' => 'RIO GRANDE DO SUL',
    'RONDÔNIA' => 'RONDÔNIA',
    'rondônia' => 'RONDÔNIA',
    'RORAIMA' => 'RORAIMA',
    'roraima' => 'RORAIMA',
    'SANTA CATARINA' => 'SANTA CATARINA',
    'SÃO PAULO' => 'SÃO PAULO',
    'são paulo' => 'SÃO PAULO',
    'SERGIPE' => 'SERGIPE',
    'sergipe' => 'SERGIPE',
    'TOCANTINS' => 'TOCANTINS',
    'tocantins' => 'TOCANTINS',
    
    'ATUALIZAR' => 'ATUALIZAR',
    'Atualizar' => 'ATUALIZAR',
    'atualizar' => 'ATUALIZAR',
    
    'MODO DE VISUALIZAÇÃO (SOMENTE LEITURA)' => 'MODO DE VISUALIZAÇÃO (SOMENTE LEITURA)',
    'Modo de Visualização' => 'MODO DE VISUALIZAÇÃO',
    
    'AS SENHAS NÃO CONFEREM' => 'AS SENHAS NÃO CONFEREM!',
    'As Senhas não conferem' => 'AS SENHAS NÃO CONFEREM!',
    'conferem' => 'CONFEREM',
    
    'CEP NÃO ENCONTRADO' => 'CEP NÃO ENCONTRADO!',
    'CEP não encontrado' => 'CEP NÃO ENCONTRADO!',
    'encontrado' => 'ENCONTRADO',
    
    'ERRO AO BUSCAR CEP' => 'ERRO AO BUSCAR CEP',
    'Erro' => 'ERRO',
    'erro' => 'ERRO',
    
    'BUSCAR' => 'BUSCAR',
    'buscar' => 'BUSCAR',
    
    'NOVAMENTE' => 'NOVAMENTE',
    'novamente' => 'NOVAMENTE',
    
    'PREENCHA TODOS OS DADOS OBRIGATÓRIOS DO CÔNJUGE' => 'PREENCHA TODOS OS DADOS OBRIGATÓRIOS DO CÔNJUGE.',
    
    'A ASSINATURA DO USUÁRIO É OBRIGATÓRIA' => 'A ASSINATURA DO USUÁRIO É OBRIGATÓRIA.',
    'A ASSINATURA' => 'A ASSINATURA',
    'assinatura' => 'ASSINATURA',
    
    'A ASSINATURA DO CÔNJUGE É OBRIGATÓRIA' => 'A ASSINATURA DO CÔNJUGE É OBRIGATÓRIA.',
    
    'TODOS OS CAMPOS DE ENDEREÇO SÃO OBRIGATÓRIOS' => 'TODOS OS CAMPOS DE ENDEREÇO SÃO OBRIGATÓRIOS.',
    'Todos' => 'TODOS',
    'campos' => 'CAMPOS',
    'obrigatórios' => 'OBRIGATÓRIOS',
    
    'LIMPAR' => 'LIMPAR',
    'Limpar' => 'LIMPAR',
    'limpar' => 'LIMPAR',
    
    'SALVAR' => 'SALVAR',
    'Salvar' => 'SALVAR',
    'salvar' => 'SALVAR',
    
    'FECHAR' => 'FECHAR',
    'Fechar' => 'FECHAR',
    'fechar' => 'FECHAR',
];

// Aplicar as substituições
foreach ($corrections as $from => $to) {
    $content = str_replace($from, $to, $content);
}

// 2. Garantir UTF-8 correto no início do arquivo
if (strpos($content, '<?php') === 0) {
    $content = '<?php' . substr($content, 5);
}

// Escrever o arquivo corrigido
file_put_contents($srcFile, $content);
echo "✅ Arquivo usuario_editar.php corrigido com sucesso!\n";
echo "   - Encoding UTF-8 corrigido\n";
echo "   - Todos os textos convertidos para UPPERCASE\n";
?>
