<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\TwoFactorToken;

/**
 * The operation a two-factor token authorises.
 *
 * A token is bound to one namespace when it is registered and only works for that operation. The
 * API rejects unknown namespaces.
 */
enum TwoFactorNamespaceEnum: string
{
    /**
     * Signing in.
     */
    case Login = 'LOGIN';

    /**
     * Turning two-factor authentication on for sign-in.
     */
    case EnableTwoFactorLogin = 'ENABLE_TWO_FACTOR_LOGIN';

    /**
     * Turning two-factor authentication off for sign-in.
     */
    case DisableTwoFactorLogin = 'DISABLE_TWO_FACTOR_LOGIN';

    /**
     * Changing the account's default telephone number.
     */
    case ChangeDefaultPhoneNumber = 'CHANGE_DEFAULT_PHONE_NUMBER';

    /**
     * Registering a bank account, or changing its number or default flag.
     *
     * The bank accounts service requires it unless bank account editing is PIN-locked.
     */
    case ChangeBankAccountNumber = 'CHANGE_BANK_ACCOUNT_NUMBER';

    /**
     * Changing how the money box pays out.
     */
    case ChangeMoneyBoxSettings = 'CHANGE_MONEY_BOX_SETTINGS';

    /**
     * Withdrawing from the money box balance.
     */
    case MoneyBoxPayout = 'MONEY_BOX_PAYOUT';

    /**
     * Setting the account's security PIN.
     */
    case SecuritySetPin = 'SECURITY_SET_PIN';

    /**
     * Registering a new channel for delivering two-factor codes.
     */
    case CreateTwoFactorTransportMethod = 'CREATE_TWO_FACTOR_TRANSPORT_METHOD';

    /**
     * Removing a channel for delivering two-factor codes.
     */
    case DeleteTwoFactorTransportMethod = 'DELETE_TWO_FACTOR_TRANSPORT_METHOD';
}
