<?php

namespace App\Services;

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
            'from' => env('MAIL_FROM_NAME').' <'.env('MAIL_FROM_ADDRESS').'>',
            'to' => [$dados['email_empresa']],
            'subject' => 'Nova solicitação de apoio',
            'html' => view('emails.solicitar_apoio', compact('dados'))->render(),
        ]);
    }
}
