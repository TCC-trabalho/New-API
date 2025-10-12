<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class PaymentStatusUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $payment;

    public function __construct($payment)
    {
        $this->payment = $payment;
    }

    // Canal de transmissão (canal público "pagamentos")
    public function broadcastOn()
    {
        return new Channel('pagamentos');
    }

    // Nome do evento que o front vai escutar
    public function broadcastAs()
    {
        return 'status-atualizado';
    }
}
