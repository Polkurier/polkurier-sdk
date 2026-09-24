<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\TwoFactorToken;

/**
 * The operation a two-factor token authorises.
 *
 * A token is bound to one namespace when it is registered and is only accepted by that operation,
 * so a token minted for a bank-account change cannot be spent on a money box payout. The API
 * rejects an unrecognised namespace outright, which is why this is an enum rather than a string.
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
     * Adding or changing a bank account that collected cash-on-delivery funds are paid into.
     *
     * The one integrations meet most often: required by the bank accounts service whenever the
     * account is not protected by a PIN instead.
     */
    case ChangeIban = 'CHANGE_BANK_ACCOUNT_NUMBER';

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
