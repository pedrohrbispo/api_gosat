<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CreditOfferController;
use Illuminate\Http\Request;

class CreditSimulationController extends Controller
{
    public function simularOferta(Request $request, $cpf)
    {
        // Dados fornecidos pelo usuário
        $valor = $request->input('valor');
        $parcelas = $request->input('parcelas');

        // Resultado da simulação
        $resultado = $this->realizarSimulacao($cpf, $valor, $parcelas);

        // Retornar a resposta da simulação em formato JSON
        return response()->json($resultado);
    }

    private function realizarSimulacao($cpf, $valor, $parcelas)
    {
        // Dados da resposta da API
        $creditOfferController = new CreditOfferController();
    
        // Resultado da simulação
        $respostaApi = $creditOfferController->consultarCpf($cpf);
    
        // Variável para armazenar todas as ofertas que atendem aos requisitos
        $melhoresOfertas = [];
    
        // Realizar a simulação com base nos valores fornecidos pelo usuário
        foreach ($respostaApi as $oferta) {
            $modalidade = $oferta['modalidade'];
            $ofertaDados = $oferta['oferta'];
    
            // Verificar se a oferta atende aos requisitos do valor e parcelas
            $valorValido = $valor >= $ofertaDados['valorMin'] && $valor <= $ofertaDados['valorMax'];
            $parcelasValidas = $parcelas >= $ofertaDados['QntParcelaMin'] && $parcelas <= $ofertaDados['QntParcelaMax'];
    
            // Mensagens de erro para valores e parcelas inválidos
            $mensagemErro = "";
            if (!$valorValido) {
                if ($valor < $ofertaDados['valorMin']) {
                    $mensagemErro .= "Valor mínimo é de R$" . number_format($ofertaDados['valorMin'], 2, ',', '.');
                } else {
                    $mensagemErro .= "Valor máximo é de R$" . number_format($ofertaDados['valorMax'], 2, ',', '.');
                }
            }
            if (!$parcelasValidas) {
                if ($parcelas < $ofertaDados['QntParcelaMin']) {
                    $mensagemErro .= "Quantidade de parcelas mínima de ({$ofertaDados['QntParcelaMin']})";
                } else {
                    $mensagemErro .= "Quantidade de parcelas máxima de ({$ofertaDados['QntParcelaMax']})";
                }
            }
    
            // Verificar se alguma mensagem de erro foi definida
            if (empty($mensagemErro)) {
                // Se não houver mensagem de erro, calcular o valor a pagar com base nos juros e quantidade de parcelas
                $valorAPagar = $this->calcularValorAPagar($valor, $parcelas, $ofertaDados['jurosMes']);
                $valorSolicitadoFormatado = 'R$ ' . number_format($valor, 2, ',', '.');
                $taxaJurosFormatada = number_format($ofertaDados['jurosMes'] * 100, 2, ',', '') . '%';
                // Armazenar a oferta que atende aos requisitos
                $melhoresOfertas[] = [
                    'banco' => $oferta['instituicao'],
                    'modalidadeCredito' => $modalidade,
                    'valorAPagar' => $valorAPagar,
                    'valorSolicitado' => $valorSolicitadoFormatado,
                    'taxaJuros' =>  $taxaJurosFormatada,
                    'qntParcelas' => $parcelas
                ];
            } else {
                // Se houver mensagem de erro, armazenar as mensagens de erro no resultado
                $melhoresOfertas[] = [
                    'banco' => $oferta['instituicao'],
                    'modalidadeCredito' => $modalidade,
                    'mensagemErro' => $mensagemErro,
                ];
            }
        }

        // Ordenar as ofertas da mais vantajosa para a menos vantajosa
        usort($melhoresOfertas, function ($a, $b) {
        // Primeiro, verificamos se há mensagens de erro em ambas as ofertas
        if (isset($a['mensagemErro']) && isset($b['mensagemErro'])) {
            // Se ambas têm mensagens de erro, não altera a ordem original
            return 0;
        } elseif (isset($a['mensagemErro'])) {
            // Se apenas a oferta A tem mensagem de erro, coloca a oferta B na frente
            return 1;
        } elseif (isset($b['mensagemErro'])) {
            // Se apenas a oferta B tem mensagem de erro, coloca a oferta A na frente
            return -1;
        }

        // Se não há mensagens de erro, comparamos os valores a pagar
        return $a['valorAPagar'] <=> $b['valorAPagar'];
        });

        return $melhoresOfertas;
    }

    private function calcularValorAPagar($valor, $parcelas, $jurosMes)
    {
        $valorAPagar = $valor * pow(1 + $jurosMes, $parcelas);
        return 'R$ ' . number_format($valorAPagar, 2, ',', '.');
    }
}
