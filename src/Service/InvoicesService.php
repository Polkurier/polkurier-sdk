<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\InvoicesServiceInterface;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Request\Invoice\GetInvoiceDocumentMessage;
use Polkurier\Webservice\Request\Invoice\GetInvoicesMessage;
use Polkurier\Webservice\Response\Invoice\InvoiceDocumentMessage;
use Polkurier\Webservice\Response\Invoice\InvoiceListMessage;

/**
 * @see InvoicesServiceInterface
 */
class InvoicesService extends AbstractService implements InvoicesServiceInterface
{
    /**
     * @param GetInvoicesMessage $getInvoicesMessage
     *
     * @return InvoiceListMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getInvoices(GetInvoicesMessage $getInvoicesMessage): InvoiceListMessage
    {
        return $this->requestObject(
            ApiMethodEnum::GetInvoices,
            InvoiceListMessage::class,
            $getInvoicesMessage,
        );
    }

    /**
     * @param GetInvoiceDocumentMessage $getInvoiceDocumentMessage
     *
     * @return InvoiceDocumentMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getInvoicePdf(GetInvoiceDocumentMessage $getInvoiceDocumentMessage): InvoiceDocumentMessage
    {
        return $this->requestObject(
            ApiMethodEnum::GetInvoicePdf,
            InvoiceDocumentMessage::class,
            $getInvoiceDocumentMessage,
        );
    }

    /**
     * @param GetInvoiceDocumentMessage $getInvoiceDocumentMessage
     *
     * @return InvoiceDocumentMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getInvoiceSummaryList(
        GetInvoiceDocumentMessage $getInvoiceDocumentMessage
    ): InvoiceDocumentMessage {
        return $this->requestObject(
            ApiMethodEnum::GetInvoiceSummaryList,
            InvoiceDocumentMessage::class,
            $getInvoiceDocumentMessage,
        );
    }
}
