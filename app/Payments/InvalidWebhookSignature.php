<?php

namespace App\Payments;

use RuntimeException;

/**
 * A requisição no webhook não foi assinada com o nosso segredo: pode ser
 * qualquer pessoa tentando marcar uma compra como paga. Respondemos 401.
 */
class InvalidWebhookSignature extends RuntimeException {}
