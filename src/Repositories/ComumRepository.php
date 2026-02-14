<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Exception;


class ComumRepository extends BaseRepository
{
    protected string $tabela = 'comums';


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
        $sql .= " ORDER BY codigo ASC LIMIT " . (int)$limite . " OFFSET " . (int)$offset;

        $stmt = $this->conexao->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


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


    public function buscarPorCodigo(int $codigo): ?array
    {
        $sql = "SELECT * FROM {$this->tabela} WHERE codigo = :codigo";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':codigo', $codigo, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }


    public function buscarPorCnpj(string $cnpj): ?array
    {
        $sql = "SELECT * FROM {$this->tabela} WHERE cnpj = :cnpj";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':cnpj', $cnpj);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }


    public function normalizarCnpj(string $cnpj): string
    {
        return preg_replace('/\D+/', '', trim($cnpj));
    }


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


    public function garantirPorCodigo(int $codigo, array $dados = []): int
    {
        if ($codigo <= 0) {
            throw new Exception('Código do comum inválido.');
        }

        $existente = $this->buscarPorCodigo($codigo);

        if ($existente) {

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


    public function extrairCodigo(string $comumTexto): int
    {
        $comumTexto = trim($comumTexto);


        if (preg_match('/BR\s*(\d{2})\D?(\d{4})/i', $comumTexto, $matches)) {
            return (int)($matches[1] . $matches[2]);
        }
        if (preg_match('/(\d{2})\D?(\d{4})/', $comumTexto, $matches)) {
            return (int)($matches[1] . $matches[2]);
        }

        return 0;
    }


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
