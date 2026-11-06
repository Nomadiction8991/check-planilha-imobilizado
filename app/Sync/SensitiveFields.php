<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Campos que o sistema guarda cifrados com a chave de cada instalação (ex.: senha do e-mail).
 *
 * Como cada lado tem a própria chave, o valor não pode viajar cifrado: quem envia
 * decifra, e quem recebe cifra de novo com a chave dele. Assim o valor chega legível
 * ao outro lado, e nenhuma chave sai da máquina onde ela está.
 */
final class SensitiveFields
{
    /**
     * Devolve a linha com os campos cifrados em texto puro, para enviar ao outro lado.
     *
     * @param  array<string, mixed>  $linha
     * @return array<string, mixed>
     *
     * @throws SensitiveFieldException  quando um campo não abre com a chave desta instalação
     */
    public function decifrar(string $tabela, array $linha): array
    {
        foreach ($this->campos($tabela) as $campo) {
            if (! isset($linha[$campo])) {
                continue;
            }

            try {
                $linha[$campo] = Crypt::decryptString((string) $linha[$campo]);
            } catch (DecryptException) {
                throw new SensitiveFieldException(
                    "O campo {$campo} da tabela {$tabela} não abre com a chave deste computador.",
                );
            }
        }

        return $linha;
    }

    /**
     * Cifra com a chave desta instalação os campos que chegaram em texto puro.
     *
     * @param  array<string, mixed>  $linha
     * @return array<string, mixed>
     */
    public function cifrar(string $tabela, array $linha): array
    {
        foreach ($this->campos($tabela) as $campo) {
            if (isset($linha[$campo])) {
                $linha[$campo] = Crypt::encryptString((string) $linha[$campo]);
            }
        }

        return $linha;
    }

    /**
     * @return list<string>
     */
    public function campos(string $tabela): array
    {
        return array_values((array) config("sincronizacao.campos_cifrados.{$tabela}", []));
    }
}
