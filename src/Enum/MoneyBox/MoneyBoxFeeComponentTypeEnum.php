<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\MoneyBox;

/**
 * What one component of a payout's fee is charged for.
 *
 * `calculate_money_box_payout_fee` lists the components that apply. One that does not apply is
 * left out rather than sent as zero, so the list can be empty.
 *
 * @api
 */
enum MoneyBoxFeeComponentTypeEnum: string
{
    /**
     * Handling the payout. Charged only once the account's free payouts are used up.
     */
    case PayoutFee = 'PAYOUT_FEE';

    /**
     * Sending the money as a postal order, charged on top of the payout fee.
     */
    case PostalTransferFee = 'POSTAL_TRANSFER_FEE';
}
