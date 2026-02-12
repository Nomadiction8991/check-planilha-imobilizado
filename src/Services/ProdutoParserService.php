<?php

/**
 * Produto Parser Service - Parsing e normalização de descrições de produtos
 * 
 * Serviço responsável por parsing inteligente de descrições de produtos
 * importados de planilhas, incluindo:
 * - Normalização de texto (remoção de acentos, uppercase)
 * - Geração de variações (plural/singular)
 * - Detecção de tipos de bens por código ou alias
 * - Extração de BEN (Bem) e Complemento
 * - Aplicação de sinônimos e regras de negócio
 * 
 * @package App\Services
 * @version 1.0.0
 */
class ProdutoParserService
{
    /**
     * @var array Config para sinônimos e regras customizadas
     */
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Normaliza string: trim, espaços duplicados, remove acentos, uppercase
     * 
     * @param string $str String a normalizar
     * @return string String normalizada
     */
    public function normalizar(string $str): string
    {
        $str = trim($str);
        $str = preg_replace('/\s+/', ' ', $str);

        // Remover acentos com iconv
        $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        if ($s === false) {
            $s = $str;
        }

        return strtoupper($s);
    }

    /**
     * Normaliza um único caractere SEM trim (preserva espaços)
     * 
     * @param string $char Caractere a normalizar
     * @return string Caractere normalizado
     */
    public function normalizarChar(string $char): string
    {
        $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $char);
        if ($s === false) {
            $s = $char;
        }

        return strtoupper($s);
    }

    /**
     * Gera variações de uma string (plural/singular)
     * 
     * Para palavras únicas: adiciona/remove 'S' final
     * Para frases compostas: varia apenas primeira palavra principal
     * 
     * @param string $str String original
     * @return array Array de variações (inclui original)
     */
    public function gerarVariacoes(string $str): array
    {
        $str = trim($str);
        if ($str === '') {
            return [];
        }

        $strNorm = $this->normalizar($str);
        $variacoes = [$strNorm];

        // Separar em palavras
        $palavras = preg_split('/\s+/', $strNorm);

        if (count($palavras) === 1) {
            // Palavra única: aplicar singular <-> plural
            if (substr($strNorm, -1) === 'S' && strlen($strNorm) > 2) {
                // Remove S final (plural → singular)
                $variacoes[] = substr($strNorm, 0, -1);
            } else {
                // Adiciona S (singular → plural)
                $variacoes[] = $strNorm . 'S';
            }
        } else {
            // Frase composta: variar apenas PRIMEIRA palavra
            $primeiraPalavra = $palavras[0];
            $resto = implode(' ', array_slice($palavras, 1));

            $primeiraVariada = null;
            if (substr($primeiraPalavra, -1) === 'S' && strlen($primeiraPalavra) > 2) {
                $primeiraVariada = substr($primeiraPalavra, 0, -1);
            } else {
                $primeiraVariada = $primeiraPalavra . 'S';
            }

            if ($primeiraVariada) {
                $variacoes[] = $primeiraVariada . ' ' . $resto;
            }
        }

        return array_unique($variacoes);
    }

    /**
     * Compara duas strings considerando variações (fuzzy match)
     * 
     * @param string $str1 Primeira string
     * @param string $str2 Segunda string
     * @return bool True se alguma variação das strings coincidir
     */
    public function matchFuzzy(string $str1, string $str2): bool
    {
        $vars1 = $this->gerarVariacoes($str1);
        $vars2 = $this->gerarVariacoes($str2);

        foreach ($vars1 as $v1) {
            foreach ($vars2 as $v2) {
                if ($v1 === $v2) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Converte letra de coluna Excel para índice numérico (zero-based)
     * 
     * Exemplos: A → 0, B → 1, Z → 25, AA → 26
     * 
     * @param string $coluna Letra(s) da coluna (ex: 'A', 'AA', 'AB')
     * @return int Índice zero-based
     */
    public function colunaParaIndice(string $coluna): int
    {
        $coluna = strtoupper($coluna);
        $indice = 0;
        $tamanho = strlen($coluna);

        for ($i = 0; $i < $tamanho; $i++) {
            $indice = $indice * 26 + (ord($coluna[$i]) - ord('A') + 1);
        }

        return $indice - 1;
    }

    /**
     * Extrai código prefixado de uma descrição
     * 
     * Detecta padrões como:
     * - "123 - DESCRICAO" → código 123
     * - "123.1 - DESCRICAO" → código 123
     * - "OT-123 - DESCRICAO" → sem código detectado
     * 
     * @param string $texto Texto com possível código prefixado
     * @return array [codigo_detectado|null, texto_sem_prefixo]
     */
    public function extrairCodigoPrefixo(string $texto): array
    {
        $codigoDetectado = null;

        // Padrão: dígitos opcionalmente seguidos de ponto e mais dígitos, depois traço
        if (preg_match('/^\s*(\d{1,3})(?:[\.,]\d+)?\s*\-\s*/u', $texto, $m)) {
            $codigoDetectado = (int) $m[1];
            $texto = preg_replace('/^\s*' . preg_quote($m[0], '/') . '/u', '', $texto);
        } elseif (preg_match('/^\s*OT-?\d+\s*\-\s*/iu', $texto)) {
            // Padrão OT não gera código, apenas remove prefixo
            $texto = preg_replace('/^\s*OT-?\d+\s*\-\s*/iu', '', $texto);
        }

        return [$codigoDetectado, trim($texto)];
    }

    /**
     * Constrói mapa de aliases para tipos de bens
     * 
     * Expande cada tipo em aliases individuais (separados por '/'),
     * gerando variações plural/singular para cada um.
     * 
     * @param array $tiposBens Array de tipos de bens do banco
     * @return array Array de tipos com aliases expandidos
     */
    public function construirAliasesTipos(array $tiposBens): array
    {
        $tiposAliases = [];

        foreach ($tiposBens as $tb) {
            $desc = (string) $tb['descricao'];

            // Separar aliases por "/"
            $aliases = array_filter(
                array_map('trim', preg_split('/\s*\/\s*/', $desc))
            );

            // Gerar variações (plural/singular) para cada alias
            $aliasesExpandidos = [];
            foreach ($aliases as $alias) {
                $variacoes = $this->gerarVariacoes($alias);
                $aliasesExpandidos = array_merge($aliasesExpandidos, $variacoes);
            }

            $aliasesNorm = array_unique($aliasesExpandidos);

            $tiposAliases[] = [
                'id' => (int) $tb['id'],
                'codigo' => (int) $tb['codigo'],
                'descricao' => $desc,
                'aliases' => $aliasesNorm,
                'aliases_originais' => array_map([$this, 'normalizar'], $aliases),
            ];
        }

        return $tiposAliases;
    }

    /**
     * Detecta tipo de bem em um texto
     * 
     * Estratégias:
     * 1. Por código detectado (se fornecido)
     * 2. Por alias mais longo que aparece no início do texto
     * 
     * @param string $texto Texto a analisar
     * @param int|null $codigoDetectado Código prefixado (se extraído)
     * @param array $tiposAliases Array de tipos com aliases (de construirAliasesTipos)
     * @return array [tipo_detectado, texto_original]
     */
    public function detectarTipo(string $texto, ?int $codigoDetectado, array $tiposAliases): array
    {
        $tipo = [
            'id' => 0,
            'codigo' => null,
            'descricao' => null,
            'alias_usado' => null
        ];

        $textoNorm = $this->normalizar($texto);

        // Estratégia 1: Por código
        if ($codigoDetectado !== null) {
            foreach ($tiposAliases as $tb) {
                if ((int) $tb['codigo'] === $codigoDetectado) {
                    $tipo = [
                        'id' => $tb['id'],
                        'codigo' => $tb['codigo'],
                        'descricao' => $tb['descricao'],
                        'alias_usado' => null
                    ];
                    return [$tipo, $texto];
                }
            }
        }

        // Estratégia 2: Por alias (busca o mais longo que aparece no início)
        $melhor = null;

        foreach ($tiposAliases as $tb) {
            foreach ($tb['aliases'] as $aliasNorm) {
                if ($aliasNorm !== '' && strpos($textoNorm, $aliasNorm) === 0) {
                    $len = strlen($aliasNorm);
                    if (!$melhor || $len > $melhor['len']) {
                        $melhor = [
                            'len' => $len,
                            'tb' => $tb,
                            'alias' => $aliasNorm
                        ];
                    }
                }
            }
        }

        if ($melhor) {
            $tipo = [
                'id' => $melhor['tb']['id'],
                'codigo' => $melhor['tb']['codigo'],
                'descricao' => $melhor['tb']['descricao'],
                'alias_usado' => $melhor['alias']
            ];
        }

        return [$tipo, trim($texto)];
    }

    /**
     * Extrai BEM e COMPLEMENTO de uma descrição
     * 
     * Implementa regras de parsing inteligente:
     * - Remove descrição completa do tipo se repetida
     * - Detecta separador " - "
     * - Identifica aliases repetidos
     * - Remove aliases sequenciais no início do complemento
     * 
     * @param string $texto Texto da descrição
     * @param array|null $tipoAliases Aliases do tipo (de construirAliasesTipos)
     * @param array|null $aliasesOriginais Aliases originais do tipo
     * @param string|null $tipoDescricao Descrição completa do tipo
     * @return array [ben, complemento]
     */
    public function extrairBenComplemento(
        string $texto,
        ?array $tipoAliases = null,
        ?array $aliasesOriginais = null,
        ?string $tipoDescricao = null
    ): array {
        $texto = trim($texto);

        // Regra 0: Remover descrição completa do tipo se repetida no início
        if ($tipoDescricao && !empty($tipoDescricao) && $aliasesOriginais) {
            $tipoDescNorm = $this->normalizar($tipoDescricao);
            $textoNorm = $this->normalizar($texto);

            // Match exato
            if (strpos($textoNorm, $tipoDescNorm) === 0) {
                $textoAposTipo = trim(substr($texto, strlen($tipoDescricao)));
                $textoAposTipo = preg_replace('/^[\s\-–—\/]+/u', '', $textoAposTipo);

                // Verifica se há alias no início do texto após tipo
                $aliasNoInicio = $this->temAliasNoInicio($textoAposTipo, $aliasesOriginais);
                if ($aliasNoInicio) {
                    $texto = $textoAposTipo;
                }
            }
        }

        // Regra 1: Separador explícito " - "
        if (preg_match('/^(.+?)\s+\-\s+(.+)$/u', $texto, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        // Regra 2: Detectar BEN pelos aliases
        if ($tipoAliases && !empty($tipoAliases)) {
            $resultado = $this->extrairBenPorAlias($texto, $tipoAliases, $aliasesOriginais);
            if ($resultado) {
                return $resultado;
            }
        }

        // Regra 3: Fallback -> tudo é BEN
        return [$texto, ''];
    }

    /**
     * Verifica se algum alias aparece no início do texto
     * 
     * @param string $texto Texto a verificar
     * @param array $aliasesOriginais Array de aliases normalizados
     * @return bool True se há alias no início
     */
    private function temAliasNoInicio(string $texto, array $aliasesOriginais): bool
    {
        $textoNorm = $this->normalizar($texto);

        foreach ($aliasesOriginais as $aliasOrig) {
            $aliasNorm = $this->normalizar($aliasOrig);
            $pattern = '/^' . preg_quote($aliasNorm, '/') . '\b/iu';

            if (preg_match($pattern, $textoNorm)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extrai BEN usando aliases do tipo
     * 
     * @param string $texto Texto original
     * @param array $tipoAliases Aliases do tipo
     * @param array|null $aliasesOriginais Aliases originais
     * @return array|null [ben, complemento] ou null se não detectado
     */
    private function extrairBenPorAlias(string $texto, array $tipoAliases, ?array $aliasesOriginais): ?array
    {
        $textoNorm = $this->normalizar($texto);

        // Detectar alias repetido (prioridade)
        $aliasRepetido = $this->detectarAliasRepetido($textoNorm, $aliasesOriginais);

        // Ordenar aliases: repetido primeiro, depois por tamanho
        $aliasesOrdenados = $this->ordenarAliases($tipoAliases, $aliasRepetido);

        // Tentar extrair BEN usando cada alias
        foreach ($aliasesOrdenados as $aliasNorm) {
            if ($aliasNorm === '') {
                continue;
            }

            $variacoesAlias = $this->gerarVariacoes($aliasNorm);

            foreach ($variacoesAlias as $variacao) {
                $resultado = $this->extrairBenPorVariacao($texto, $textoNorm, $variacao, $aliasesOrdenados);

                if ($resultado) {
                    return $resultado;
                }
            }
        }

        return null;
    }

    /**
     * Detecta alias que aparece repetido no texto
     * 
     * @param string $textoNorm Texto normalizado
     * @param array|null $aliasesOriginais Aliases originais
     * @return array|null [alias, num_repeticoes] ou null
     */
    private function detectarAliasRepetido(string $textoNorm, ?array $aliasesOriginais): ?array
    {
        if (!$aliasesOriginais) {
            return null;
        }

        $maxRepeticoes = 0;
        $aliasRepetido = null;

        foreach ($aliasesOriginais as $aliasOrig) {
            $pattern = '/\b' . preg_quote($aliasOrig, '/') . '\b/iu';
            $count = preg_match_all($pattern, $textoNorm);

            if ($count > $maxRepeticoes) {
                $maxRepeticoes = $count;
                $aliasRepetido = $aliasOrig;
            }
        }

        return $maxRepeticoes > 1 ? [$aliasRepetido, $maxRepeticoes] : null;
    }

    /**
     * Ordena aliases: repetido primeiro, depois por tamanho
     * 
     * @param array $tipoAliases Aliases do tipo
     * @param array|null $aliasRepetido [alias, repeticoes] ou null
     * @return array Aliases ordenados
     */
    private function ordenarAliases(array $tipoAliases, ?array $aliasRepetido): array
    {
        $aliasesOrdenados = $tipoAliases;

        usort($aliasesOrdenados, function ($a, $b) use ($aliasRepetido) {
            if ($aliasRepetido && $aliasRepetido[1] > 1) {
                if ($a === $aliasRepetido[0]) {
                    return -1;
                }
                if ($b === $aliasRepetido[0]) {
                    return 1;
                }
            }
            return strlen($b) - strlen($a);
        });

        return $aliasesOrdenados;
    }

    /**
     * Extrai BEN usando uma variação específica de alias
     * 
     * @param string $texto Texto original
     * @param string $textoNorm Texto normalizado
     * @param string $variacao Variação do alias a testar
     * @param array $aliasesOrdenados Todos os aliases ordenados
     * @return array|null [ben, complemento] ou null
     */
    private function extrairBenPorVariacao(
        string $texto,
        string $textoNorm,
        string $variacao,
        array $aliasesOrdenados
    ): ?array {
        $pattern = '/^(' . preg_quote($variacao, '/') . ')(\s|$)/iu';

        if (!preg_match($pattern, $textoNorm, $m)) {
            return null;
        }

        // Encontrar posição no texto original
        $matchLen = mb_strlen($m[1]);
        $posOrig = $this->encontrarPosicaoOriginal($texto, $matchLen);

        $ben = trim(mb_substr($texto, 0, $posOrig));
        $resto = trim(mb_substr($texto, $posOrig));

        // Remover separadores iniciais
        $resto = preg_replace('/^[\s\-–—\/]+/u', '', $resto);

        // Remover aliases sequenciais no resto
        $resto = $this->removerAliasesSequenciais($resto, $aliasesOrdenados);

        return [$ben, $resto];
    }

    /**
     * Encontra posição no texto original baseado no comprimento normalizado
     * 
     * @param string $texto Texto original
     * @param int $matchLen Comprimento do match normalizado
     * @return int Posição no texto original
     */
    private function encontrarPosicaoOriginal(string $texto, int $matchLen): int
    {
        $acumuladoNorm = '';
        $posOrig = 0;
        $textoLen = mb_strlen($texto);

        while (mb_strlen($acumuladoNorm) < $matchLen && $posOrig < $textoLen) {
            $char = mb_substr($texto, $posOrig, 1);
            $charNorm = $this->normalizarChar($char);
            $acumuladoNorm .= $charNorm;
            $posOrig++;
        }

        return $posOrig;
    }

    /**
     * Remove aliases que aparecem sequencialmente no início do texto
     * 
     * @param string $resto Texto restante
     * @param array $aliasesOrdenados Aliases ordenados
     * @return string Texto com aliases removidos
     */
    private function removerAliasesSequenciais(string $resto, array $aliasesOrdenados): string
    {
        $removeuAlgo = true;

        while ($removeuAlgo && $resto !== '') {
            $removeuAlgo = false;
            $restoNorm = $this->normalizar($resto);

            foreach ($aliasesOrdenados as $outroAlias) {
                if ($outroAlias === '') {
                    continue;
                }

                $variacoesOutro = $this->gerarVariacoes($outroAlias);

                foreach ($variacoesOutro as $varOutro) {
                    $patternOutro = '/^(' . preg_quote($varOutro, '/') . ')(\s|$)/iu';

                    if (preg_match($patternOutro, $restoNorm, $m2)) {
                        $matchLen2 = mb_strlen($m2[1]);
                        $posOrig2 = $this->encontrarPosicaoOriginal($resto, $matchLen2);

                        $resto = trim(mb_substr($resto, $posOrig2));
                        $resto = preg_replace('/^[\s\-–—\/]+/u', '', $resto);
                        $removeuAlgo = true;
                        break 2;
                    }
                }
            }
        }

        return $resto;
    }

    /**
     * Remove BEN do complemento (se aparecer duplicado)
     * 
     * @param string $ben BEN detectado
     * @param string $complemento Complemento
     * @return string Complemento sem BEN duplicado
     */
    public function removerBenDoComplemento(string $ben, string $complemento): string
    {
        if ($ben === '' || $complemento === '') {
            return $complemento;
        }

        $benQuoted = preg_quote($ben, '/');
        $complemento = preg_replace('/^' . $benQuoted . '(\s+|\/|\-|:)+/u', '', $complemento);

        return trim(preg_replace('/\s+/', ' ', $complemento));
    }

    /**
     * Aplica sinônimos configurados
     * 
     * @param string $ben BEN original
     * @param string $complemento Complemento
     * @param string|null $tipoDesc Descrição do tipo
     * @return array [ben_corrigido, complemento_corrigido]
     */
    public function aplicarSinonimos(string $ben, string $complemento, ?string $tipoDesc): array
    {
        $benNorm = $this->normalizar($ben);
        $compNorm = $this->normalizar($complemento);
        $tipoNorm = $this->normalizar((string) $tipoDesc);

        // Regras por tipo
        if (!empty($this->config['synonyms'])) {
            foreach ($this->config['synonyms'] as $tipoKey => $map) {
                if ($this->normalizar($tipoKey) === $tipoNorm) {
                    foreach ($map as $from => $to) {
                        if (strpos($compNorm, $this->normalizar($from)) !== false) {
                            $ben = $to;
                            break 2;
                        }
                    }
                }
            }
        }

        // Regras globais
        if (!empty($this->config['global_synonyms'])) {
            foreach ($this->config['global_synonyms'] as $from => $to) {
                if (strpos($compNorm, $this->normalizar($from)) !== false) {
                    $ben = $to;
                    break;
                }
            }
        }

        // Limpar BEN do complemento se foi alterado
        $complemento = $this->removerBenDoComplemento(strtoupper($ben), strtoupper($complemento));

        return [$ben, $complemento];
    }

    /**
     * Força BEN a ser um dos aliases válidos do tipo
     * 
     * @param string $ben BEN detectado
     * @param string|null $tipoDesc Descrição do tipo (aliases separados por '/')
     * @param string|null $aliasUsado Alias usado na detecção
     * @return string BEN corrigido (uppercase)
     */
    public function forcarBenEmAliases(string $ben, ?string $tipoDesc, ?string $aliasUsado = null): string
    {
        $tokens = array_map('trim', preg_split('/\s*\/\s*/', (string) $tipoDesc));
        $tokensUpper = array_map('strtoupper', $tokens);
        $tokensNorm = array_map([$this, 'normalizar'], $tokens);
        $benNorm = $this->normalizar($ben);

        // 1) BEN já pertence aos aliases
        foreach ($tokensNorm as $i => $tNorm) {
            if ($tNorm !== '' && $tNorm === $benNorm) {
                return $tokensUpper[$i];
            }
        }

        // 2) Se alias_usado existir, mapear para um token
        if (!empty($aliasUsado)) {
            $aliasNorm = $this->normalizar($aliasUsado);
            foreach ($tokensNorm as $i => $tNorm) {
                if ($tNorm !== '' && $tNorm === $aliasNorm) {
                    return $tokensUpper[$i];
                }
            }
        }

        // 3) Fallback: primeiro token válido
        foreach ($tokensUpper as $tok) {
            if (trim($tok) !== '') {
                return strtoupper($tok);
            }
        }

        return strtoupper($ben);
    }

    /**
     * Monta descrição completa formatada
     * 
     * Formato:
     * - Com BEN: "1x [CODIGO - TIPO] BEN - COMPLEMENTO (DEPENDENCIA)"
     * - Sem BEN: "1x [CODIGO - TIPO] COMPLEMENTO (DEPENDENCIA)"
     * 
     * @param int $qtd Quantidade
     * @param int|null $tipoCodigo Código do tipo
     * @param string|null $tipoDesc Descrição do tipo
     * @param string $ben BEN
     * @param string $comp Complemento
     * @param string $dep Dependência
     * @return string Descrição formatada
     */
    public function montarDescricao(
        int $qtd,
        ?int $tipoCodigo,
        ?string $tipoDesc,
        string $ben,
        string $comp,
        string $dep
    ): string {
        $brackets = '?';
        if (!empty($tipoCodigo) && !empty($tipoDesc)) {
            $brackets = sprintf('%d - %s', $tipoCodigo, strtoupper($tipoDesc));
        }

        $ben = strtoupper(trim($ben));
        $comp = strtoupper(trim($comp));
        $dep = strtoupper(trim($dep));

        $desc = sprintf('%dx [%s]', $qtd, $brackets);

        if ($ben !== '') {
            $desc .= ' ' . $ben;
            if ($comp !== '') {
                $desc .= ' - ' . $comp;
            }
        } else {
            if ($comp !== '') {
                $desc .= ' ' . $comp;
            } else {
                $desc .= ' SEM DESCRICAO';
            }
        }

        if ($dep !== '') {
            $desc .= ' (' . $dep . ')';
        }

        return $desc;
    }
}
