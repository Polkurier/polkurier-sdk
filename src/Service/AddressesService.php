<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\AddressesServiceInterface;
use Polkurier\Webservice\Dto\Request\Address\DeleteMessage;
use Polkurier\Webservice\Dto\Request\Address\GetMessage;
use Polkurier\Webservice\Entity\Address\Address;
use Polkurier\Webservice\Entity\Address\SaveAddressMessage;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * @see \Polkurier\Webservice\Api\Service\AddressesServiceInterface
 */
class AddressesService extends AbstractService implements AddressesServiceInterface
{
    /**
     * @param GetMessage|null $getMessage
     *
     * @return Address[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getAddresses(?GetMessage $getMessage = null): array
    {
        return $this->requestList(ApiMethodEnum::GetAddresses, Address::class, $getMessage);
    }

    /**
     * @param SaveAddressMessage $saveMessage
     *
     * @return Address
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function saveAddress(SaveAddressMessage $saveMessage): Address
    {
        return $this->requestObject(ApiMethodEnum::SaveAddress, Address::class, $saveMessage);
    }

    /**
     * @param DeleteMessage $deleteMessage
     *
     * @return bool
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function deleteAddress(DeleteMessage $deleteMessage): bool
    {
        return $this->requestAcknowledgement(ApiMethodEnum::DeleteAddress, $deleteMessage);
    }
}
