<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Uma regra de negócio impediu a operação ("estoque insuficiente",
 * "não é possível enviar um pedido de retirada", etc.).
 *
 * A mensagem é escrita para o usuário final. Em bootstrap/app.php esta
 * exceção é convertida automaticamente em:
 *   - página web: volta para o formulário com a mensagem em $errors;
 *   - JSON (API/agentes): resposta 422 com {"message": "..."}.
 */
class BusinessRuleException extends RuntimeException {}
