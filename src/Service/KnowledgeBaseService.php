<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\KnowledgeBaseServiceInterface;
use Polkurier\Webservice\Dto\Request\KnowledgeBase\GetKnowledgeBaseArticleMessage;
use Polkurier\Webservice\Dto\Request\KnowledgeBase\SearchKnowledgeBaseMessage;
use Polkurier\Webservice\Dto\Response\KnowledgeBase\KnowledgeBaseArticleMessage;
use Polkurier\Webservice\Dto\Response\KnowledgeBase\KnowledgeBaseEntryMessage;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * @see KnowledgeBaseServiceInterface
 */
class KnowledgeBaseService extends AbstractService implements KnowledgeBaseServiceInterface
{
    /**
     * @return KnowledgeBaseEntryMessage[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getIndex(): array
    {
        return $this->requestList(
            ApiMethodEnum::GetKnowledgeBaseIndex,
            KnowledgeBaseEntryMessage::class,
        );
    }

    /**
     * @param SearchKnowledgeBaseMessage $searchKnowledgeBaseMessage
     *
     * @return KnowledgeBaseEntryMessage[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function search(SearchKnowledgeBaseMessage $searchKnowledgeBaseMessage): array
    {
        return $this->requestList(
            ApiMethodEnum::SearchKnowledgeBase,
            KnowledgeBaseEntryMessage::class,
            $searchKnowledgeBaseMessage,
        );
    }

    /**
     * @param GetKnowledgeBaseArticleMessage $getKnowledgeBaseArticleMessage
     *
     * @return KnowledgeBaseArticleMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getArticle(
        GetKnowledgeBaseArticleMessage $getKnowledgeBaseArticleMessage
    ): KnowledgeBaseArticleMessage {
        return $this->requestObject(
            ApiMethodEnum::GetKnowledgeBaseArticle,
            KnowledgeBaseArticleMessage::class,
            $getKnowledgeBaseArticleMessage,
        );
    }
}
