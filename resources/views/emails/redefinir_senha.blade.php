<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Recuperação de senha</title>
</head>

<body style="font-family: Arial, sans-serif; background-color:#f5f7fa; margin:0; padding:20px;">

    <table role="presentation"
        style="min-width:500px; margin:40px auto; background:#fff; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); padding:30px;">
        <tr>
            <td style="text-align:center; padding-bottom:10px;">
                <h1 style="color:#064B72; margin:0;">Nexus</h1>
                <h2 style="color:#064B72; margin:0;">Redefinição de Senha</h2>
            </td>
        </tr>

        <tr>
            <td style="color:#333; font-size:15px; line-height:1.6;text-align:center;">
                <h2>Olá, {{ $dados['nome'] }}!</h2>

                <p>Você solicitou a redefinição da sua senha.</p>

                <p>Seu código de verificação é:</p>
                <h1 style="font-size: 32px; color: #c62828;">{{ $dados['codigo'] }}</h1>
                <p>Este código expira em 10 minutos.</p>
            </td>
        </tr>

    </table>

</body>

</html>