<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\PackTemplatesServiceInterface;
use Polkurier\Webservice\Dto\Request\Common\DeleteMessage;
use Polkurier\Webservice\Entity\PackTemplate;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * @see \Polkurier\Webservice\Api\Service\PackTemplatesServiceInterface
 */
class PackTemplatesService extends AbstractService implements PackTemplatesServiceInterface
{
    /**
     * @return PackTemplate[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getPackTemplates(): array
    {
        return $this->requestList(ApiMethodEnum::GetPackTemplates, PackTemplate::class);
    }

    /**
     * @param PackTemplate $packTemplate
     *
     * @return PackTemplate
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function savePackTemplates(PackTemplate $packTemplate): PackTemplate
    {
        return $this->requestObject(
            ApiMethodEnum::SavePackTemplate,
            PackTemplate::class,
            $packTemplate,
        );
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
    public function deletePackTemplates(DeleteMessage $deleteMessage): bool
    {
        return $this->requestAcknowledgement(ApiMethodEnum::DeletePackTemplate, $deleteMessage);
    }
}
