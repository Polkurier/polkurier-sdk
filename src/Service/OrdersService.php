<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\OrdersServiceInterface;
use Polkurier\Webservice\Dto\Request\Order\CancelOrderMessage;
use Polkurier\Webservice\Dto\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Dto\Request\Order\GetDocumentMessage;
use Polkurier\Webservice\Dto\Request\Order\GetOrderFileMessage;
use Polkurier\Webservice\Dto\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Dto\Request\Order\GetStatusMessage;
use Polkurier\Webservice\Dto\Request\Order\MultiPickupAvailabilityRequestMessage;
use Polkurier\Webservice\Dto\Request\Order\MultiPickupRequestMessage;
use Polkurier\Webservice\Dto\Request\Order\OrderValuationMessage;
use Polkurier\Webservice\Dto\Response\Order\CancellationMessage;
use Polkurier\Webservice\Dto\Response\Order\CreatedOrderMessage;
use Polkurier\Webservice\Dto\Response\Order\DocumentMessage;
use Polkurier\Webservice\Dto\Response\Order\MultiPickupAvailabilityMessage;
use Polkurier\Webservice\Dto\Response\Order\MultiPickupResultMessage;
use Polkurier\Webservice\Dto\Response\Order\OrderFileMessage;
use Polkurier\Webservice\Dto\Response\Order\OrderListMessage;
use Polkurier\Webservice\Dto\Response\Order\OrderStatusMessage;
use Polkurier\Webservice\Dto\Response\Order\ValuationMessage;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * @see OrdersServiceInterface
 */
class OrdersService extends AbstractService implements OrdersServiceInterface
{
    /**
     * @param CreateOrderMessage $createOrderMessage
     * @param string|null $courier
     *
     * @return ValuationMessage[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function orderValuation(CreateOrderMessage $createOrderMessage, ?string $courier = null): array
    {
        if ($courier === null) {
            return $this->requestList(ApiMethodEnum::OrderValuation, ValuationMessage::class, $createOrderMessage);
        }

        // Priced for one carrier, the API answers with that quote alone, not a list
        $quote = $this->requestObject(
            ApiMethodEnum::OrderValuation,
            ValuationMessage::class,
            new OrderValuationMessage($createOrderMessage, $courier),
        );

        return [$quote];
    }

    /**
     * @param CreateOrderMessage $createOrderMessage
     *
     * @return CreatedOrderMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function createOrder(CreateOrderMessage $createOrderMessage): CreatedOrderMessage
    {
        return $this->requestObject(
            ApiMethodEnum::CreateOrder,
            CreatedOrderMessage::class,
            $createOrderMessage,
        );
    }

    /**
     * @param GetOrdersMessage|null $getOrdersMessage
     *
     * @return OrderListMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getOrders(?GetOrdersMessage $getOrdersMessage = null): OrderListMessage
    {
        return $this->requestObject(
            ApiMethodEnum::GetOrders,
            OrderListMessage::class,
            $getOrdersMessage,
        );
    }

    /**
     * @param CancelOrderMessage $cancelOrderMessage
     *
     * @return CancellationMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function cancelOrder(CancelOrderMessage $cancelOrderMessage): CancellationMessage
    {
        return $this->requestObject(
            ApiMethodEnum::CancelOrder,
            CancellationMessage::class,
            $cancelOrderMessage,
        );
    }

    /**
     * @param GetDocumentMessage $getDocumentMessage
     *
     * @return DocumentMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getLabel(GetDocumentMessage $getDocumentMessage): DocumentMessage
    {
        return $this->requestObject(
            ApiMethodEnum::GetLabel,
            DocumentMessage::class,
            $getDocumentMessage,
        );
    }

    /**
     * @param GetDocumentMessage $getDocumentMessage
     *
     * @return DocumentMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getProtocol(GetDocumentMessage $getDocumentMessage): DocumentMessage
    {
        return $this->requestObject(
            ApiMethodEnum::GetProtocol,
            DocumentMessage::class,
            $getDocumentMessage,
        );
    }

    /**
     * @param GetStatusMessage $getStatusMessage
     *
     * @return OrderStatusMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getStatus(GetStatusMessage $getStatusMessage): OrderStatusMessage
    {
        return $this->requestObject(
            ApiMethodEnum::GetStatus,
            OrderStatusMessage::class,
            $getStatusMessage,
        );
    }

    /**
     * @param MultiPickupAvailabilityRequestMessage $multiPickupAvailabilityRequestMessage
     *
     * @return MultiPickupAvailabilityMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function isMultiPickupAvailable(
        MultiPickupAvailabilityRequestMessage $multiPickupAvailabilityRequestMessage
    ): MultiPickupAvailabilityMessage {
        return $this->requestObject(
            ApiMethodEnum::IsMultiPickupAvailable,
            MultiPickupAvailabilityMessage::class,
            $multiPickupAvailabilityRequestMessage,
        );
    }

    /**
     * @param MultiPickupRequestMessage $multiPickupRequestMessage
     *
     * @return MultiPickupResultMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function createMultiOrderPickup(
        MultiPickupRequestMessage $multiPickupRequestMessage
    ): MultiPickupResultMessage {
        return $this->requestObject(
            ApiMethodEnum::CreateMultiOrderPickup,
            MultiPickupResultMessage::class,
            $multiPickupRequestMessage,
        );
    }

    /**
     * @param GetOrderFileMessage $getOrderFileMessage
     *
     * @return OrderFileMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getOrderFile(GetOrderFileMessage $getOrderFileMessage): OrderFileMessage
    {
        return $this->requestObject(
            ApiMethodEnum::GetOrderFile,
            OrderFileMessage::class,
            $getOrderFileMessage,
        );
    }

}
