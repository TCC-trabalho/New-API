<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Solicitação de Apoio</title>
</head>

<body style="font-family: Arial, sans-serif; background-color:#f5f7fa; margin:0; padding:20px;">

    <table role="presentation"
        style="max-width:600px; margin:40px auto; background:#fff; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); padding:30px;">
        <tr>
            <td style="text-align:center; padding-bottom:20px;">
                <h1 style="color:#064B72; margin:0;">Nexus</h1>
                <h2 style="color:#064B72; margin:0;">Nova solicitação de apoio recebida!</h2>
            </td>
        </tr>

        <tr>
            <td style="color:#333; font-size:15px; line-height:1.6;">
                <p>
                    Olá, o <strong>{{ $dados['tipo_usuario'] }}</strong> <strong>{{ $dados['nome_usuario'] }}</strong>
                    está pedindo apoio para seu projeto <strong>{{ $dados['projeto'] }}</strong>.
                </p>

                <p>
                    <strong>Tipo de Apoio:</strong> {{ ucfirst($dados['tipo_apoio']) }}
                </p>

                <p>
                    <strong>Mensagem:</strong><br>
                    {{ $dados['mensagem'] }}
                </p>

                <p style="margin-top:20px; font-style:italic; color:#666;">
                    Qualquer ajuda é bem-vinda 💙
                </p>
            </td>
        </tr>

        <tr>
            <td style="text-align:center; padding-top:30px;">
                <a href="https://nexus.caetanodev.com/plataforma-nexus/detalhes-projeto/{{ $dados['id_projeto'] }}"
                    style="display:inline-block; background:#064B72; color:#fff; padding:12px 28px; 
                          text-decoration:none; border-radius:6px; font-weight:bold; font-size:15px;">
                    Ver Projeto na Plataforma
                </a>
            </td>
        </tr>
    </table>

</body>

</html>