<?php

namespace Modules\PseService\Http\Gior;

/**
 * Class Endpoints.
 */
final class Endpoints
{
    const BETA_TOKEN = 'https://beta-pse.giortechnology.com/service/api/auth/cpe/token';
    const BETA_GENERATE = 'https://beta-pse.giortechnology.com/service/api/cpe/generar';
    const BETA_SEND = 'https://beta-pse.giortechnology.com/service/api/cpe/enviar';
    const BETA_QUERY = 'https://beta-pse.giortechnology.com/service/api/cpe/consultar';

    const TOKEN = 'https://pse.giortechnology.com/service/api/auth/cpe/token';
    const GENERATE = 'https://pse.giortechnology.com/service/api/cpe/generar';
    const SEND = 'https://pse.giortechnology.com/service/api/cpe/enviar';
    const QUERY = 'https://pse.giortechnology.com/service/api/cpe/consultar';
}
