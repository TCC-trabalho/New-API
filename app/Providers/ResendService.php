<?php

namespace App\Providers;

use Resend;


class ResendService
{
    protected $client;

    public function __construct()
    {
        $this->client = Resend::client(env('RESEND_API_KEY'));
    }

    public function sendSupportRequest($dados)
    {
        return $this->client->emails->send([
            'from' => 'Nexus Apoios <apoios@nexus.caetanodev.com>',
            'to' => [$dados['email_empresa']],
            'subject' => 'Nova solicitação de apoio',
            'html' => view('emails.solicitar_apoio', compact('dados'))->render(),
        ]);
    }

    public function sendPasswordResetCode($dados)
    {
        return $this->client->emails->send([
            'from' => 'Nexus Suporte <suporte@nexus.caetanodev.com>',
            'to' => [$dados['email_destino']],
            'subject' => 'Recuperação de senha - NEXUS',
            'html' => view('emails.redefinir_senha', compact('dados'))->render(),
        ]);
    }
}
