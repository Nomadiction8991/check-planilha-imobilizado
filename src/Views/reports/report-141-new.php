<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';


$pageTitle = 'Relatório 14.1';
$backUrl = '/products/view?comum_id=' . urlencode((string) ($comum_id ?? $id_planilha));
$customCssPath = '/assets/css/reports/report-141-new.css';
$tailwindReportsCss = '/assets/css/reports/tailwind-reports.css';


ob_start();
?>

<link rel="stylesheet" href="<?php echo $tailwindReportsCss; ?>">

<?php if (count($PRODUTOS) > 0): ?>

    <!-- Navegação do carrossel -->
    <div class="flex items-center justify-between gap-4 p-4 bg-white border-b border-neutral-200 sticky top-0 z-10">
        <button id="btnPrev" onclick="navegarCarrossel(-1)" class="px-4 py-2 bg-black text-white hover:bg-neutral-900 transition flex items-center gap-2" style="border-radius:2px">
            <i class="bi bi-chevron-left"></i> Anterior
        </button>
        <div class="text-sm font-medium text-neutral-600">
            <span id="paginaAtual" class="font-bold">1</span> / <span id="totalPaginas"><?php echo count($PRODUTOS); ?></span>
        </div>
        <button id="btnNext" onclick="navegarCarrossel(1)" class="px-4 py-2 bg-black text-white hover:bg-neutral-900 transition flex items-center gap-2" style="border-radius:2px">
            Próximo <i class="bi bi-chevron-right"></i>
        </button>
    </div>

    <!-- Formulário de valores comuns -->
    <div class="p-4 bg-neutral-50 border-b border-neutral-200">
        <h6 class="text-sm font-bold text-neutral-700 mb-4 flex items-center gap-2">
            <i class="bi bi-ui-checks"></i> Valores Comuns para Todos
        </h6>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-neutral-600 mb-2">Administração</label>
                <input type="text" id="admin_geral" onchange="atualizarTodos('admin')" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" style="border-radius:2px">
            </div>
            <div>
                <label class="block text-xs font-medium text-neutral-600 mb-2">CIDADE</label>
                <input type="text" id="cidade_geral" onchange="atualizarTodos('cidade')" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" style="border-radius:2px">
            </div>
            <div>
                <label class="block text-xs font-medium text-neutral-600 mb-2">Setor</label>
                <input type="text" id="setor_geral" onchange="atualizarTodos('setor')" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" style="border-radius:2px">
            </div>
            <div>
                <label class="block text-xs font-medium text-neutral-600 mb-2">Administrador/Acessor</label>
                <input type="text" id="admin_acessor_geral" onchange="atualizarTodos('admin_acessor')" class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black" style="border-radius:2px">
            </div>
        </div>
    </div>

    <!-- Carrossel de pginas -->
    <div class="overflow-hidden">
        <div class="flex transition-transform duration-300" id="carrosselTrack" style="transform: translateX(0)">
            <?php foreach ($PRODUTOS as $index => $row): ?>
                <div class="w-full flex-shrink-0 p-4 md:p-6">
                    <div class="bg-white overflow-hidden" style="border-radius:2px">
                        <div class="bg-neutral-50 p-4 md:p-6 overflow-auto" style="max-height: 85vh;">
                            <link rel="stylesheet" href="/public/assets/css/relatorio-14-1.css">
                            <div class="a4">
                                <section class="cabecalho">
                                    <table>
                                        <tr class="row1">
                                            <th class="col1" rowspan="3">CCB</th>
                                            <th class="col2" rowspan="3">MANUAL ADMINISTRATIVO</th>
                                            <th class="col3">SEO: </th>
                                            <th class="col4">14</th>
                                        </tr>
                                        <tr class="row2">
                                            <th class="col3">FL./FLS. </th>
                                            <th class="col4">34/36</th>
                                        </tr>
                                        <tr class="row3">
                                            <th class="col3">DATA REVISO: </th>
                                            <th class="col4">24/09/2019</th>
                                        </tr>
                                        <tr class="row4">
                                            <th class="col1" rowspan="2">ASSUNTO</th>
                                            <th class="col2" rowspan="2">PATRIMNIO - BENS MVEIS</th>
                                            <th class="col3">EDIO: </th>
                                            <th class="col4">6</th>
                                        </tr>
                                        <tr class="row5">
                                            <th class="col3">REVISO: </th>
                                            <th class="col4">1</th>
                                        </tr>
                                    </table>
                                </section>
                                <section class="conteudo">
                                    <h1>FORMULRIO 14.1: DECLARAO DE DOAO DE BEM MVEL</h1>
                                    <div class="conteudo">
                                        <table>
                                            <tr class="row1">
                                                <td class="col1" colspan="2">CONGREGAO CRIST NO BRASIL</td>
                                                <td class="col2" colspan="2">FORMULRIO 14.1</td>
                                            </tr>
                                            <tr class="row2">
                                                <td class="col1" colspan="2">DECLARAO DE DOAO DE BENS MVEIS</td>
                                                <td class="col2" colspan="2">
                                                    <label for="">Data Emisso</label><br>
                                                    <input type="text" name="data_emissao" id="data_emissao_<?php echo $row['id']; ?>" value="<?php echo date('d/m/Y'); ?>" readonly>
                                                </td>
                                            </tr>
                                        </table>
                                        <table>
                                            <tr class="row3">
                                                <td class="col1">A</td>
                                                <td class="col2" colspan="2">LOCALIDADE RECEBIDA</td>
                                            </tr>
                                        </table>
                                        <table>
                                            <tr class="row4">
                                                <td class="col1">Administração</td>
                                                <td class="col2">CIDADE</td>
                                                <td class="col3">Setor</td>
                                            </tr>
                                            <tr class="row5">
                                                <td class="col1">
                                                    <input type="text" name="administracao" id="administracao_<?php echo $row['id']; ?>">
                                                </td>
                                                <td class="col2">
                                                    <input type="text" name="cidade" id="cidade_<?php echo $row['id']; ?>">
                                                </td>
                                                <td class="col3">
                                                    <input type="text" name="setor" id="setor_<?php echo $row['id']; ?>">
                                                </td>
                                            </tr>
                                            <tr class="row6">
                                                <td class="col1">CNPJ da Administração</td>
                                                <td class="col2">N° Relatório</td>
                                                <td class="col3">Casa de Orao</td>
                                            </tr>
                                            <tr class="row7">
                                                <td class="col1">
                                                    <input type="text" name="cnpj" id="cnpj_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($cnpj_planilha ?? ''); ?>">
                                                </td>
                                                <td class="col2">
                                                    <input type="text" name="numero_relatorio" id="numero_relatorio_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($numero_relatorio_auto ?? ''); ?>">
                                                </td>
                                                <td class="col3">
                                                    <input type="text" name="casa_oracao" id="casa_oracao_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($casa_oracao_auto ?? ''); ?>">
                                                </td>
                                            </tr>
                                        </table>
                                        <table>
                                            <tr class="row8">
                                                <td class="col1">B</td>
                                                <td class="col2" colspan="3">DESCRIO DO BEM</td>
                                            </tr>
                                        </table>
                                        <table>
                                            <tr class="row9">
                                                <td class="col1" colspan="4">
                                                    <textarea name="descricao_bem" id="descricao_bem_<?php echo $row['id']; ?>" readonly><?php echo htmlspecialchars($row['descricao_completa']); ?></textarea>
                                                </td>
                                            </tr>
                                        </table>
                                        <table>
                                            <tr class="row10">
                                                <td class="col1">N° Nota fiscal</td>
                                                <td class="col2">Data de emisso</td>
                                                <td class="col3">Valor</td>
                                                <td class="col4">Fornecedor</td>
                                            </tr>
                                            <tr class="row11">
                                                <td class="col1">
                                                    <input type="text" name="numero_nota" id="numero_nota_<?php echo $row['id']; ?>">
                                                </td>
                                                <td class="col2">
                                                    <input type="text" name="data_emissao_nota" id="data_emissao_nota_<?php echo $row['id']; ?>">
                                                </td>
                                                <td class="col3">
                                                    <input type="text" name="valor" id="valor_<?php echo $row['id']; ?>">
                                                </td>
                                                <td class="col4">
                                                    <input type="text" name="fornecedor" id="fornecedor_<?php echo $row['id']; ?>">
                                                </td>
                                            </tr>
                                            <tr class="row12">
                                                <td class="col1" colspan="4">
                                                    <p>Declaramos que estamos doando CONGREGAO CRIST NO BRASIL o bem acima descrito, de nossa propriedade, livre e sesembaraado de dvidas e nus, para uso na Casa de Orao acima identificada.</p><br>
                                                    <label>
                                                        <input type="checkbox" class="opcao-checkbox" name="opcao_1_<?php echo $row['id']; ?>" id="opcao_1_<?php echo $row['id']; ?>" data-page="<?php echo $index; ?>">
                                                        O bem tem mais de cinco anos de uso e o documento fiscal de aquisio est anexo.
                                                    </label><br>
                                                    <label>
                                                        <input type="checkbox" class="opcao-checkbox" name="opcao_2_<?php echo $row['id']; ?>" id="opcao_2_<?php echo $row['id']; ?>" data-page="<?php echo $index; ?>">
                                                        O bem tem mais de cinco anos de uso, porm o documento fiscal de aquisio foi extraviado.
                                                    </label><br>
                                                    <label>
                                                        <input type="checkbox" class="opcao-checkbox" name="opcao_3_<?php echo $row['id']; ?>" id="opcao_3_<?php echo $row['id']; ?>" data-page="<?php echo $index; ?>">
                                                        O bem tem at cinco anos de uso e o documento fiscal de aquisio est anexo.
                                                    </label><br><br>
                                                    <p>Por ser verdade firmamos esta declarao.</p><br>
                                                    <label>Local e data: <input type="text" name="local_data" id="local_data_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($comum_planilha); ?> ____/____/_______"></label>
                                                </td>
                                            </tr>
                                        </table>
                                        <table>
                                            <tr class="row13">
                                                <td class="col1">C</td>
                                                <td class="col2" colspan="2">DOADOR</td>
                                            </tr>
                                        </table>
                                        <table>
                                            <tr class="row14">
                                                <td class="col1"></td>
                                                <td class="col2">Dados do doador</td>
                                                <td class="col3">Dados do cnjuge</td>
                                            </tr>
                                            <tr class="row15">
                                                <td class="col1">Nome</td>
                                                <td class="col2"><input type="text" name="nome_doador" id="nome_doador_<?php echo $row['id']; ?>"></td>
                                                <td class="col3"><input type="text" name="nome_conjuge" id="nome_conjuge_<?php echo $row['id']; ?>"></td>
                                            </tr>
                                            <tr class="row16">
                                                <td class="col1">Endereo</td>
                                                <td class="col2"><input type="text" name="endereco_doador" id="endereco_doador_<?php echo $row['id']; ?>"></td>
                                                <td class="col3"><input type="text" name="endereco_conjuge" id="endereco_conjuge_<?php echo $row['id']; ?>"></td>
                                            </tr>
                                            <tr class="row17">
                                                <td class="col1">CPF</td>
                                                <td class="col2"><input type="text" name="cpf_doador" id="cpf_doador_<?php echo $row['id']; ?>"></td>
                                                <td class="col3"><input type="text" name="cpf_conjuge" id="cpf_conjuge_<?php echo $row['id']; ?>"></td>
                                            </tr>
                                            <tr class="row18">
                                                <td class="col1">RG</td>
                                                <td class="col2"><input type="text" name="rg_doador" id="rg_doador_<?php echo $row['id']; ?>"></td>
                                                <td class="col3"><input type="text" name="rg_conjuge" id="rg_conjuge_<?php echo $row['id']; ?>"></td>
                                            </tr>
                                            <tr class="row19">
                                                <td class="col1">Assinatura</td>
                                                <td class="col2"><input type="text" name="assinatura_doador" id="assinatura_doador_<?php echo $row['id']; ?>"></td>
                                                <td class="col3"><input type="text" name="assinatura_conjuge" id="assinatura_conjuge_<?php echo $row['id']; ?>"></td>
                                            </tr>
                                        </table>
                                        <table>
                                            <tr class="row20">
                                                <td class="col1">D</td>
                                                <td class="col2" colspan="2">TERMO DE ACEITE DA DOAO</td>
                                            </tr>
                                        </table>
                                        <table>
                                            <tr class="row21">
                                                <td class="col1" colspan="3">
                                                    <p>A Congregao Crist No Brasil aceita a presente doao por atender necessidade do momento.</p>
                                                </td>
                                            </tr>
                                            <tr class="row22">
                                                <td class="col1"></td>
                                                <td class="col2">Nome</td>
                                                <td class="col3">Assinatura</td>
                                            </tr>
                                            <tr class="row23">
                                                <td class="col1">Administrador/Acessor</td>
                                                <td class="col2"><input type="text" name="admin_acessor" id="admin_acessor_<?php echo $row['id']; ?>"></td>
                                                <td class="col3"><input type="text" name="assinatura_admin" id="assinatura_admin_<?php echo $row['id']; ?>"></td>
                                            </tr>
                                            <tr class="row24">
                                                <td class="col1">Doador</td>
                                                <td class="col2"></td>
                                                <td class="col3"></td>
                                            </tr>
                                        </table>
                                    </div>
                                </section>
                                <section class="rodape">
                                    <table>
                                        <tr class="row1">
                                            <td class="col1"></td>
                                            <td class="col2">sp.saopaulo.manualadm@congregacao.org.br</td>
                                            <td class="col3"></td>
                                        </tr>
                                    </table>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php else: ?>
    <div class="m-4 md:m-6 p-4" style="border-radius:2px;color:#b45309;background:#fef9c3;border:1px solid #fde047">
        <div class="flex items-start gap-3">
            <i class="bi bi-exclamation-triangle text-lg mt-0.5"></i>
            <span>Nenhum PRODUTO encontrado para impressão do relatório 14.1.</span>
        </div>
    </div>
<?php endif; ?>

<script>
    window.__totalPaginas = <?php echo count($PRODUTOS); ?>;
</script>
<script src="/assets/js/reports/report-141-new.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
