<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\PackTemplatesServiceInterface;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Request\Common\DeleteMessage;
use Polkurier\Webservice\Request\PackTemplate\SaveMessage;
use Polkurier\Webservice\Response\PackTemplate;

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
     * @throws WebserviceException
     */
    public function getPackTemplates(): array
    {
        return $this->requestList(ApiMethodEnum::GetPackTemplates, PackTemplate::class);
    }

    /**
     * @param SaveMessage $saveMessage
     *
     * @return PackTemplate
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function savePackTemplates(SaveMessage $saveMessage): PackTemplate
    {
        return $this->requestObject(
            ApiMethodEnum::SavePackTemplate,
            PackTemplate::class,
            $saveMessage,
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
