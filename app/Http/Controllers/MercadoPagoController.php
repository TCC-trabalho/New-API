<?php

namespace App\Http\Controllers;

use App\Events\PaymentStatusUpdated;
use App\Models\Project;
use App\Models\UsuarioContaMP;
use App\Providers\MercadoPagoProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MercadoPagoController extends Controller
{

    public function status(Request $request)
    {
        $idUsuario = $request->query('id_usuario');
        $tipoUsuario = $request->query('tipo_usuario');

        if (!$idUsuario || !$tipoUsuario) {
            return response()->json([
                'error' => 'Parâmetros id_usuario e tipo_usuario são obrigatórios.'
            ], 400);
        }

        $conta = UsuarioContaMP::where('id_usuario', $idUsuario)
            ->where('tipo_usuario', $tipoUsuario)
            ->first();

        if (!$conta) {
            return response()->json(['vinculado' => false]);
        }

        return response()->json([
            'vinculado' => true,
            'data_vinculo' => $conta->data_vinculo,
            'expira_em' => $conta->mp_token_expires_at,
        ]);
    }


    /**
     * Gera o link para o usuário conectar a conta Mercado Pago
     */
    public function connect(Request $request)
    {
        $clientId = env('MP_CLIENT_ID');
        $redirectUri = urlencode(env('MP_REDIRECT_URI'));

        $idUsuario = $request->query('id_usuario');
        $tipoUsuario = $request->query('tipo_usuario');

        if (!$idUsuario || !$tipoUsuario) {
            return response()->json(['erro' => 'ID do usuário ou tipo não informado.'], 400);
        }

        $state = base64_encode($idUsuario . '|' . $tipoUsuario);

        $url = "https://auth.mercadopago.com/authorization?response_type=code&client_id={$clientId}&redirect_uri={$redirectUri}&state={$state}";

        return response()->json(['url' => $url]);
    }

    /**
     * Recebe o retorno do Mercado Pago e salva os tokens
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $state = base64_decode($request->get('state'));
        [$idUsuario, $tipoUsuario] = explode('|', $state);

        if (!$code) {
            return redirect()->away('https://nexus.caetanodev.com/pagamentos/erro?motivo=missing_code');
        }

        $response = Http::asForm()->post('https://api.mercadopago.com/oauth/token', [
            'client_secret' => env('MP_CLIENT_SECRET'),
            'client_id' => env('MP_CLIENT_ID'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => env('MP_REDIRECT_URI'),
        ]);

        if ($response->failed()) {
            return redirect()->away('https://nexus.caetanodev.com/pagamentos/erro?motivo=token_error');
        }

        $data = $response->json();

        UsuarioContaMP::updateOrCreate(
            ['id_usuario' => $idUsuario, 'tipo_usuario' => $tipoUsuario],
            [
                'mp_user_id' => $data['user_id'] ?? null,
                'mp_access_token' => Crypt::encryptString($data['access_token']),
                'mp_refresh_token' => Crypt::encryptString($data['refresh_token']),
                'mp_token_expires_at' => now()->addSeconds($data['expires_in']),
                'mp_scope' => $data['scope'] ?? null,
            ]
        );

        return redirect()->away('https://nexus.caetanodev.com/plataforma-nexus/configuracoes?status=success');
    }

    /**
     * Cria um pagamento via PIX
     */
    public function criarPix(Request $request, $idProjeto)
    {
        $valor = (float) $request->input('valor');

        // busca o projeto
        $projeto = Project::findOrFail($idProjeto);

        // busca o gestor financeiro (relacionamento com usuario_conta_mp)
        $gestor = UsuarioContaMP::where('id_usuario', $projeto->id_gestor_financeiro)
            ->where('tipo_usuario', $projeto->tipo_gestor)
            ->first();

        if (!$gestor) {
            return response()->json([
                'erro' => 'O projeto não possui gestor financeiro vinculado ao Mercado Pago.'
            ], 400);
        }

        $mp = new MercadoPagoProvider();

        if ($mp->tokenExpirado($gestor)) {
            $gestor = $mp->renovarToken($gestor);

            if (!$gestor) {
                return response()->json(['erro' => 'Falha ao renovar token do Mercado Pago.'], 400);
            }
        }

        // descriptografa o token atualizado
        $gestorAccessToken = Crypt::decryptString($gestor->mp_access_token);

        if ($erro = $mp->validarContaMercadoPago($gestorAccessToken)) {
            return $erro;
        }

        // define comissão (exemplo: 5%)
        $applicationFee = round($valor * 0.05, 2);

        // monta o payload
        $payload = [
            'transaction_amount' => $valor,
            'description' => "Patrocínio Projeto {$projeto->nome}",
            'payment_method_id' => 'pix',
            'application_fee' => $applicationFee,
            'external_reference' => "proj-{$projeto->id}-" . Str::uuid(),
            'payer' => [
                'email' => $request->input('email', 'teste@empresa.com'),
                'first_name' => $request->input('nome', 'Empresa Teste'),
            ],
        ];

        // envia a requisição
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $gestorAccessToken,
            'X-Idempotency-Key' => (string) Str::uuid(),
        ])->post('https://api.mercadopago.com/v1/payments', $payload)->json();

        if (isset($response['status']) && $response['status'] == 400) {
            return response()->json(['erro' => $response]);
        }

        // retorna os dados do QR Code para o front
        return response()->json([
            'status' => $response['status'] ?? 'erro',
            'id' => $response['id'] ?? null,
            'qr_base64' => $response['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
            'qr_text' => $response['point_of_interaction']['transaction_data']['qr_code'] ?? null,
            'valor' => $valor,
            'comissao' => $applicationFee,
        ]);
    }

    /**
     * Recebe notificações do Mercado Pago (Webhook)
     */
    public function webhook(Request $request)
    {
        // Loga tudo pra debug
        \Log::info('Webhook Mercado Pago recebido:', $request->all());

        // Verifica se veio um ID de pagamento
        if (!isset($request->data['id'])) {
            return response()->json(['error' => 'ID de pagamento ausente.'], 400);
        }

        $paymentId = $request->data['id'];

        // Busca o status atualizado do pagamento
        $response = Http::withToken(env('MP_ACCESS_TOKEN'))
            ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

        if ($response->failed()) {
            return response()->json(['error' => 'Falha ao consultar pagamento'], 500);
        }

        $payment = $response->json();
        $status = $payment['status'] ?? null;

        // Log para depuração
        \Log::info("Status atual do pagamento: {$status}");

        if ($status === 'approved') {
            event(new PaymentStatusUpdated([
                'status' => 'approved',
                'id' => $paymentId,
                'amount' => $payment['transaction_amount'] ?? null,
            ]));
        }

        return response()->json(['success' => true], 200);
    }

}
