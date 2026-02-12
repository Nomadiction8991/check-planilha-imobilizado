<?php

/**
 * Exemplo de Controlador para Preenchimento de Formulários
 * Use este código em seus controllers para gerar PDFs preenchidos
 */

require_once __DIR__ . '/../../scripts/preenche_formulario.php';

class FormularioController
{

    /**
     * Exemplo: Gera Formulário 14.1 - Declaração de Doação
     */
    public function gerarFormulario141($dadosDoacao)
    {

        $pdfOriginal = __DIR__ . '/../../Manual Imobilizado.pdf';
        $preenchedor = new PreencheFormularioPDF($pdfOriginal);

        // Prepara os dados do formulário
        $dados = [
            'data_emissao' => date('d/m/Y'),
            'administracao' => $dadosDoacao['administracao'] ?? '',
            'cidade' => $dadosDoacao['cidade'] ?? '',
            'setor' => $dadosDoacao['setor'] ?? '',
            'cnpj_administracao' => $dadosDoacao['cnpj'] ?? '',
            'num_relatorio' => $dadosDoacao['numero_relatorio'] ?? '',
            'casa_oracao' => $dadosDoacao['casa_oracao'] ?? '',
            'descricao_bem' => $dadosDoacao['descricao_bem'] ?? '',
            'num_nota_fiscal' => $dadosDoacao['nota_fiscal'] ?? '',
            'data_emissao_nf' => $dadosDoacao['data_nota_fiscal'] ?? '',
            'valor_nf' => $dadosDoacao['valor'] ?? '',
            'fornecedor' => $dadosDoacao['fornecedor'] ?? '',
            'local_data_assinatura' => $dadosDoacao['local_data'] ?? '',
            'nome_doador' => $dadosDoacao['nome_doador'] ?? '',
            'nome_conjuge' => $dadosDoacao['nome_conjuge'] ?? '',
            'endereco_doador' => $dadosDoacao['endereco_doador'] ?? '',
            'endereco_conjuge' => $dadosDoacao['endereco_conjuge'] ?? '',
            'cpf_doador' => $dadosDoacao['cpf_doador'] ?? '',
            'cpf_conjuge' => $dadosDoacao['cpf_conjuge'] ?? '',
            'rg_doador' => $dadosDoacao['rg_doador'] ?? '',
            'rg_conjuge' => $dadosDoacao['rg_conjuge'] ?? '',
            'nome_administrador' => $dadosDoacao['administrador'] ?? '',
            'nome_doador_aceite' => $dadosDoacao['nome_doador'] ?? '',
        ];

        // Preenche o formulário
        $preenchedor->preencherFormulario(1, $dados);

        // Opções de saída:
        // 'I' = Exibir no navegador
        // 'D' = Forçar download
        // 'F' = Salvar em arquivo

        return $preenchedor->salvar('I', 'Formulario_14.1_Doacao.pdf');
    }

    /**
     * Exemplo: Gera Formulário 14.2 - Ocorrência de Entrada
     */
    public function gerarFormulario142($dadosEntrada)
    {

        $pdfOriginal = __DIR__ . '/../../Manual Imobilizado.pdf';
        $preenchedor = new PreencheFormularioPDF($pdfOriginal);

        $dados = [
            'data_emissao' => date('d/m/Y'),
            'administracao' => $dadosEntrada['administracao'] ?? '',
            'num_relatorio' => $dadosEntrada['numero_relatorio'] ?? '',
            'casa_oracao' => $dadosEntrada['casa_oracao'] ?? '',
            // Adicione mais campos conforme necessário
        ];

        $preenchedor->preencherFormulario(2, $dados);

        return $preenchedor->salvar('I', 'Formulario_14.2_Entrada.pdf');
    }

    /**
     * Gera múltiplos formulários em um único PDF
     */
    public function gerarMultiplosFormularios($listaFormularios)
    {

        $pdfOriginal = __DIR__ . '/../../Manual Imobilizado.pdf';
        $preenchedor = new PreencheFormularioPDF($pdfOriginal);

        foreach ($listaFormularios as $formulario) {
            $numeroFormulario = $formulario['numero'];
            $dados = $formulario['dados'];

            $preenchedor->preencherFormulario($numeroFormulario, $dados);
        }

        return $preenchedor->salvar('D', 'Formularios_Multiplos.pdf');
    }

    /**
     * Salva o PDF em uma pasta específica e retorna o caminho
     */
    public function salvarFormulario($numeroFormulario, $dados, $nomeSaida)
    {

        $pdfOriginal = __DIR__ . '/../../Manual Imobilizado.pdf';
        $preenchedor = new PreencheFormularioPDF($pdfOriginal);

        $preenchedor->preencherFormulario($numeroFormulario, $dados);

        $caminhoSaida = __DIR__ . "/../../relatorios/{$nomeSaida}";
        $preenchedor->salvar('F', $caminhoSaida);

        return $caminhoSaida;
    }
}

// ============================================================================
// EXEMPLO DE USO EM ROTA/ENDPOINT
// ============================================================================

// Exemplo 1: Usar em uma rota POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_formulario'])) {

    $controller = new FormularioController();

    // Recebe dados do formulário web
    $dadosDoacao = [
        'administracao' => $_POST['administracao'] ?? '',
        'cidade' => $_POST['cidade'] ?? '',
        'setor' => $_POST['setor'] ?? '',
        'cnpj' => $_POST['cnpj'] ?? '',
        'numero_relatorio' => $_POST['numero_relatorio'] ?? '',
        'casa_oracao' => $_POST['casa_oracao'] ?? '',
        'descricao_bem' => $_POST['descricao_bem'] ?? '',
        'nota_fiscal' => $_POST['nota_fiscal'] ?? '',
        'data_nota_fiscal' => $_POST['data_nota_fiscal'] ?? '',
        'valor' => $_POST['valor'] ?? '',
        'fornecedor' => $_POST['fornecedor'] ?? '',
        'local_data' => $_POST['local_data'] ?? '',
        'nome_doador' => $_POST['nome_doador'] ?? '',
        'nome_conjuge' => $_POST['nome_conjuge'] ?? '',
        'endereco_doador' => $_POST['endereco_doador'] ?? '',
        'endereco_conjuge' => $_POST['endereco_conjuge'] ?? '',
        'cpf_doador' => $_POST['cpf_doador'] ?? '',
        'cpf_conjuge' => $_POST['cpf_conjuge'] ?? '',
        'rg_doador' => $_POST['rg_doador'] ?? '',
        'rg_conjuge' => $_POST['rg_conjuge'] ?? '',
        'administrador' => $_POST['administrador'] ?? '',
    ];

    // Gera o PDF
    $controller->gerarFormulario141($dadosDoacao);
    exit;
}

// Exemplo 2: Integração com sistema existente
if (php_sapi_name() === 'cli') {

    echo "=== Exemplo de Uso do Controller ===\n\n";

    $controller = new FormularioController();

    // Dados de exemplo
    $dadosExemplo = [
        'administracao' => 'Administração Central SP',
        'cidade' => 'São Paulo',
        'setor' => 'Zona Sul',
        'cnpj' => '12.345.678/0001-90',
        'numero_relatorio' => '001/2026',
        'casa_oracao' => 'Casa de Oração Central',
        'descricao_bem' => 'Notebook Dell Inspiron 15, 8GB RAM, 256GB SSD, Processador i5',
        'nota_fiscal' => 'NF-123456',
        'data_nota_fiscal' => '05/02/2026',
        'valor' => 'R$ 3.500,00',
        'fornecedor' => 'Dell Computadores Ltda',
        'local_data' => 'São Paulo, 10 de fevereiro de 2026',
        'nome_doador' => 'José Silva Santos',
        'nome_conjuge' => 'Ana Paula Santos',
        'endereco_doador' => 'Av. Paulista, 1000 - São Paulo/SP - CEP 01310-100',
        'endereco_conjuge' => 'Av. Paulista, 1000 - São Paulo/SP - CEP 01310-100',
        'cpf_doador' => '111.222.333-44',
        'cpf_conjuge' => '555.666.777-88',
        'rg_doador' => '11.222.333-4',
        'rg_conjuge' => '55.666.777-8',
        'administrador' => 'Carlos Eduardo Oliveira',
    ];

    try {
        // Salva o arquivo ao invés de exibir
        $caminhoArquivo = $controller->salvarFormulario(
            1,
            $dadosExemplo,
            'exemplo_formulario_141_' . date('YmdHis') . '.pdf'
        );

        echo "✓ Formulário gerado com sucesso!\n";
        echo "  Arquivo salvo em: {$caminhoArquivo}\n\n";
    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage() . "\n";
    }
}
