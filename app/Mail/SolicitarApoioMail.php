<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SolicitarApoioMail extends Mailable
{
    use Queueable, SerializesModels;

    public $dados;

    public function __construct($dados)
    {
        $this->dados = $dados;
    }

    public function build()
    {
        $this->dados['frontend_url'] = env('FRONTEND_URL');

        return $this->subject('Nova solicitação de apoio')
            ->view('emails.solicitar_apoio')
            ->with('dados', $this->dados);
    }
}
