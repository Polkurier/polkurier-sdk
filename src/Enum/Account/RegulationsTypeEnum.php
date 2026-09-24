<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Account;

/**
 * Which legal document `get_regulations` should return.
 */
enum RegulationsTypeEnum: string
{
    /**
     * The terms of service customers must accept to keep using the account.
     */
    case Regulations = 'regulations';

    /**
     * The privacy policy. Informational — there is no acceptance flow for it.
     */
    case PrivacyPolicy = 'privacy_policy';
}
