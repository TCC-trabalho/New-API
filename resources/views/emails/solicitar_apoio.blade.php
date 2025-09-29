<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Solicitação de Apoio</title>
</head>

<body style="font-family: Arial, sans-serif; margin:0; padding:0;">

    <!-- Header -->
    <div style="background-color:#064B72; padding:15px; text-align:center;">
        <img src="{{ $message->embed(public_path('logo.png')) }}" alt="Logo" style="width:150px;">
    </div>

    <div style="padding:20px;">
        <h2 style="color:#064B72;">Nova solicitação de apoio recebida!</h2>

        <p><strong>Nome do Usuário:</strong> {{ $dados['nome_usuario'] }}</p>
        <p><strong>Tipo do Usuário:</strong> {{ $dados['tipo_usuario'] }}</p>
        <p><strong>Projeto:</strong> {{ $dados['projeto'] }}</p>
        <p><strong>Tipo de Apoio:</strong> {{ ucfirst($dados['tipo_apoio']) }}</p>
        <p><strong>Mensagem:</strong> {{ $dados['mensagem'] }}</p>

        <br>

        <!-- Botão -->
        <div style="text-align:center; margin-top:20px;">
            <a href="{{ $dados['frontend_url'] }}/plataforma-nexus/detalhes-projeto/{{ $dados['id_projeto'] }}" style="display:inline-block; background:#064B72; color:#fff; padding:12px 24px;
          text-decoration:none; border-radius:5px; font-weight:bold;">
                Ver Projeto na Plataforma
            </a>
        </div>
    </div>

</body>

</html>