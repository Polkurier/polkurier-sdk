<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
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
     * The privacy policy. For information only, there is nothing to accept.
     */
    case PrivacyPolicy = 'privacy_policy';
}
