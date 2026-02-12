<?php

namespace App\Repositories;

use PDO;
use Exception;

/**
 * Repositório de Comuns
 * Gerencia acesso a dados da tabela 'comums'
 */
class ComumRepository extends BaseRepository
{
    protected string $tabela = 'comums';

    /**
     * Busca comuns com paginação e filtros
     */
    public function buscarPaginado(string $busca = '', int $limite = 10, int $offset = 0): array
    {
        $params = [];
        $where = '';

        if ($busca !== '') {
            $where = "(codigo LIKE :busca OR descricao LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        $sql = "SELECT * FROM {$this->tabela}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $sql .= " ORDER BY codigo ASC LIMIT :limite OFFSET :offset";

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conta comuns com filtro de busca
     */
    public function contarComFiltro(string $busca = ''): int
    {
        $params = [];
        $where = '';

        if ($busca !== '') {
            $where = "(codigo LIKE :busca OR descricao LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        return $this->contar($where, $params);
    }

    /**
     * Busca comum por código
     */
    public function buscarPorCodigo(int $codigo): ?array
    {
        $sql = "SELECT * FROM {$this->tabela} WHERE codigo = :codigo";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':codigo', $codigo, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    /**
     * Busca comum por CNPJ
     */
    public function buscarPorCnpj(string $cnpj): ?array
    {
        $sql = "SELECT * FROM {$this->tabela} WHERE cnpj = :cnpj";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':cnpj', $cnpj);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    /**
     * Normaliza CNPJ (remove não-dígitos)
     */
    public function normalizarCnpj(string $cnpj): string
    {
        return preg_replace('/\D+/', '', trim($cnpj));
    }

    /**
     * Gera CNPJ único para o comum
     */
    public function gerarCnpjUnico(string $cnpjBase, int $codigo, ?int $ignorarId = null): string
    {
        $cnpjLimpo = $this->normalizarCnpj($cnpjBase);
        $base = $cnpjLimpo === '' ? 'SEM-CNPJ-' . $codigo : $cnpjLimpo;
        $cnpjFinal = $base;
        $tentativa = 0;

        while (true) {
            $stmt = $this->conexao->prepare("SELECT id FROM {$this->tabela} WHERE cnpj = :cnpj");
            $stmt->bindValue(':cnpj', $cnpjFinal);
            $stmt->execute();
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);

            // Não existe ou é o próprio registro sendo atualizado
            if (!$existente || ($ignorarId !== null && (int)$existente['id'] === (int)$ignorarId)) {
                return $cnpjFinal;
            }

            $tentativa++;
            $cnpjFinal = $base . '-COD-' . $codigo;
            if ($tentativa > 1) {
                $cnpjFinal .= '-' . $tentativa;
            }
        }
    }

    /**
     * Garante que existe um comum com o código informado
     * Se não existir, cria com placeholders
     */
    public function garantirPorCodigo(int $codigo, array $dados = []): int
    {
        if ($codigo <= 0) {
            throw new Exception('Código do comum inválido.');
        }

        $existente = $this->buscarPorCodigo($codigo);

        if ($existente) {
            // Atualizar dados básicos se enviados
            if (!empty($dados)) {
                $updates = [];
                $params = [':id' => (int)$existente['id']];

                if (!empty($dados['cnpj'])) {
                    $novoCnpj = $this->gerarCnpjUnico($dados['cnpj'], $codigo, (int)$existente['id']);
                    if ($novoCnpj !== $existente['cnpj']) {
                        $updates[] = "cnpj = :cnpj";
                        $params[':cnpj'] = $novoCnpj;
                    }
                }

                if (isset($dados['descricao'])) {
                    $updates[] = "descricao = :descricao";
                    $params[':descricao'] = mb_strtoupper($dados['descricao'], 'UTF-8');
                }

                if (isset($dados['administracao'])) {
                    $updates[] = "administracao = :administracao";
                    $params[':administracao'] = mb_strtoupper($dados['administracao'], 'UTF-8');
                }

                if (isset($dados['cidade'])) {
                    $updates[] = "cidade = :cidade";
                    $params[':cidade'] = mb_strtoupper($dados['cidade'], 'UTF-8');
                }

                if (isset($dados['setor'])) {
                    $updates[] = "setor = :setor";
                    $params[':setor'] = $dados['setor'];
                }

                if (!empty($updates)) {
                    $sql = "UPDATE {$this->tabela} SET " . implode(', ', $updates) . " WHERE id = :id";
                    $stmt = $this->conexao->prepare($sql);
                    foreach ($params as $k => $v) {
                        $stmt->bindValue($k, $v);
                    }
                    $stmt->execute();
                }
            }

            return (int)$existente['id'];
        }

        // Inserir novo registro
        $dadosInsert = [
            'codigo' => $codigo,
            'cnpj' => null,
            'descricao' => mb_strtoupper($dados['descricao'] ?? '', 'UTF-8'),
            'administracao' => mb_strtoupper($dados['administracao'] ?? '', 'UTF-8'),
            'cidade' => mb_strtoupper($dados['cidade'] ?? '', 'UTF-8'),
            'setor' => $dados['setor'] ?? null
        ];

        return $this->criar($dadosInsert);
    }

    /**
     * Extrai código numérico do texto do comum
     * Ex: "BR 09-0040 - SIBIPIRUNAS" retorna 90040
     */
    public function extrairCodigo(string $comumTexto): int
    {
        $comumTexto = trim($comumTexto);

        // Aceita variações como "BR 09-0040", "BR09 0040", "09-0040"
        if (preg_match('/BR\s*(\d{2})\D?(\d{4})/i', $comumTexto, $matches)) {
            return (int)($matches[1] . $matches[2]);
        }
        if (preg_match('/(\d{2})\D?(\d{4})/', $comumTexto, $matches)) {
            return (int)($matches[1] . $matches[2]);
        }

        return 0;
    }

    /**
     * Extrai descrição do texto do comum
     * Ex: "BR 09-0040 - SIBIPIRUNAS" retorna "SIBIPIRUNAS"
     */
    public function extrairDescricao(string $comumTexto): string
    {
        $comumTexto = trim($comumTexto);

        if (
            preg_match('/BR\s*\d{2}\D?\d{4}\s*[-\/]?\s*(.+)$/i', $comumTexto, $matches) ||
            preg_match('/\d{2}\D?\d{4}\s*[-\/]?\s*(.+)$/', $comumTexto, $matches)
        ) {
            $descricao = trim($matches[1]);

            if (strpos($descricao, '-') !== false) {
                $partes = array_map('trim', explode('-', $descricao));
                $descricao = end($partes);
            }

            return $descricao;
        }

        return '';
    }

    /**
     * Processa/cria comum a partir do texto completo
     */
    public function processarComum(string $comumTexto, array $dados = []): int
    {
        if (empty($comumTexto)) {
            throw new Exception('Comum vazio ou não informado.');
        }

        $codigo = $this->extrairCodigo($comumTexto);
        if ($codigo <= 0) {
            throw new Exception('Não foi possível extrair código do comum.');
        }

        $descricao = $this->extrairDescricao($comumTexto);
        if ($descricao) {
            $dados['descricao'] = $descricao;
        }

        return $this->garantirPorCodigo($codigo, $dados);
    }
}
