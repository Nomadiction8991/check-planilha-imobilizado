<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * ImportException — Exceção para erros durante importação de dados.
 *
 * Usada em CsvParserService, ImportacaoService para erros
 * específicos de processamento de CSV/importação.
 *
 * @since 15.0
 */
class ImportException extends Exception
{
    /** @var array Contexto adicional da exceção */
    private array $contexto = [];

    /** @var int Linha do CSV onde ocorreu o erro (se aplicável) */
    private int $linhaCSV = 0;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        array $contexto = [],
        int $linhaCSV = 0
    ) {
        parent::__construct($message, $code, $previous);
        $this->contexto = $contexto;
        $this->linhaCSV = $linhaCSV;
    }

    /**
     * Obtém contexto adicional da exceção.
     *
     * @return array
     */
    public function getContexto(): array
    {
        return $this->contexto;
    }

    /**
     * Obtém número da linha CSV onde ocorreu erro.
     *
     * @return int
     */
    public function getLinhaCSV(): int
    {
        return $this->linhaCSV;
    }
}
