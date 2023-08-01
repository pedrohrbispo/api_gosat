<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CreditOfferController extends Controller
{
    private $cpfsDisponiveis = ['111.111.111-11', '123.123.123-12', '222.222.222-22'];

    public function consultarCpf($cpf)
    {
        // Verifica se o CPF está na lista de CPFs disponíveis
        if (!in_array($cpf, $this->cpfsDisponiveis)) {
            return ['message' => 'CPF não encontrado'];
        }
    
        $url = 'https://dev.gosat.org/api/v1/simulacao/credito';
        $data = [
            'cpf' => $cpf,
        ];
    
        $response = Http::withOptions(['verify' => false])->post($url, $data);
    
        if ($response->successful()) {
            // Requisição bem-sucedida, obter a resposta
            $responseData = $response->json();
    
            // Processar as informações da instituição e do ID
            $instituicoes = $responseData['instituicoes'];
            
            // Inicializar a variável $melhoresOfertas como um array vazio
            $melhoresOfertas = [];
    
            foreach ($instituicoes as $instituicao) {
                $id = $instituicao['id'];
                $nome = $instituicao['nome'];
    
                // Acessar as modalidades da instituição
                $modalidades = $instituicao['modalidades'];
    
                foreach ($modalidades as $modalidade) {
                    $cod = $modalidade['cod'];
    
                    // Consultar a API externa específica do banco/modalidade para obter os detalhes da oferta
                    $oferta = $this->consultarOfertaExterna($cpf, $cod, $id);
    
                    // Criar um array associativo com as informações da modalidade e adicionar à matriz de melhores ofertas
                    $melhoresOfertas[] = [
                        'instituicao' => $nome,
                        'modalidade' => $modalidade['nome'],
                        'oferta' => $oferta
                    ];
                }
            }
    
            return $melhoresOfertas;
        } else {
            // Requisição falhou, obter a mensagem de erro
            $errorMessage = $response->body();
            return $errorMessage;
        }
    }    
private function consultarOfertaExterna($cpf, $cod, $id)
{
    $url = 'https://dev.gosat.org/api/v1/simulacao/oferta';
    $data = [
        'cpf' => $cpf,
        'instituicao_id' => $id,
        'codModalidade' => $cod
    ];

    $response = Http::withOptions(['verify' => false])->post($url, $data);

    if ($response->successful()) {
        // Decodificar a resposta da API
        $responseData = $response->json();

        // Verificar se a resposta contém os campos esperados
        if (
            isset($responseData['QntParcelaMin']) &&
            isset($responseData['QntParcelaMax']) &&
            isset($responseData['valorMin']) &&
            isset($responseData['valorMax']) &&
            isset($responseData['jurosMes'])
        ) {
            return [
                'QntParcelaMin' => $responseData['QntParcelaMin'],
                'QntParcelaMax' => $responseData['QntParcelaMax'],
                'valorMin' => $responseData['valorMin'],
                'valorMax' => $responseData['valorMax'],
                'jurosMes' => $responseData['jurosMes'],
            ];
        }
    }

    // Caso ocorra algum erro na consulta ou os campos não estejam disponíveis, retorne null ou uma estrutura vazia.
    return [];
}
}