<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice;

use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Api\Http\ClientFactoryInterface;
use Polkurier\Webservice\Api\Service\AccountServiceInterface;
use Polkurier\Webservice\Api\Service\AccountVerificationServiceInterface;
use Polkurier\Webservice\Api\Service\AddressesServiceInterface;
use Polkurier\Webservice\Api\Service\BankAccountsServiceInterface;
use Polkurier\Webservice\Api\Service\CourierServiceInterface;
use Polkurier\Webservice\Api\Service\GeoServiceInterface;
use Polkurier\Webservice\Api\Service\InvoicesServiceInterface;
use Polkurier\Webservice\Api\Service\KnowledgeBaseServiceInterface;
use Polkurier\Webservice\Api\Service\MoneyBoxServiceInterface;
use Polkurier\Webservice\Api\Service\OrdersServiceInterface;
use Polkurier\Webservice\Api\Service\PackTemplatesServiceInterface;
use Polkurier\Webservice\Api\Service\PaymentsServiceInterface;
use Polkurier\Webservice\Api\Service\SystemServiceInterface;
use Polkurier\Webservice\Api\Service\TicketsServiceInterface;
use Polkurier\Webservice\Api\Service\TwoFactorTokenServiceInterface;
use Polkurier\Webservice\Api\Service\UtilsServiceInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;
use Polkurier\Webservice\Api\Webhook\OrderReaderInterface;
use Polkurier\Webservice\Http\ClientFactory;
use Polkurier\Webservice\Http\Request\RequestFactory;
use Polkurier\Webservice\Http\Request\RequestFactoryInterface;
use Polkurier\Webservice\Http\Response\LastResponseHolder;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Http\Response\ResponseFactoryInterface;
use Polkurier\Webservice\Serializer\ResponseDrift;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Polkurier\Webservice\Service\AbstractService;
use Polkurier\Webservice\Service\AccountService;
use Polkurier\Webservice\Service\AccountVerificationService;
use Polkurier\Webservice\Service\AddressesService;
use Polkurier\Webservice\Service\BankAccountsService;
use Polkurier\Webservice\Service\CourierService;
use Polkurier\Webservice\Service\GeoService;
use Polkurier\Webservice\Service\InvoicesService;
use Polkurier\Webservice\Service\KnowledgeBaseService;
use Polkurier\Webservice\Service\MoneyBoxService;
use Polkurier\Webservice\Service\OrdersService;
use Polkurier\Webservice\Service\PackTemplatesService;
use Polkurier\Webservice\Service\PaymentsService;
use Polkurier\Webservice\Service\SystemService;
use Polkurier\Webservice\Service\TicketsService;
use Polkurier\Webservice\Service\TwoFactorTokenService;
use Polkurier\Webservice\Service\UtilsService;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;
use Polkurier\Webservice\Webhook\OrderReader;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Entry point to the Polkurier web API.
 *
 * ```php
 * $client = new PolkurierWebservice(new Config(
 *     authorization: new AuthorizationMessage(token: 'abc…', login: '12345'),
 *     platform: 'my-shop',
 *     platformVersion: '1.0.0',
 * ));
 *
 * $carriers = $client->courier()->availableCarriers();   // Carrier[]
 * ```
 *
 * It wires up a serializer, request and response factories, an HTTP client factory, a validator
 * and one service per API area. Pass any of them to the constructor to replace it. Services are
 * created on first use and then reused.
 *
 * No I/O happens until you call a service method.
 *
 * @api
 */
final class PolkurierWebservice
{
    /**
     * @var ConfigProviderInterface
     */
    private readonly ConfigProviderInterface $configProvider;

    /**
     * @var RequestFactoryInterface
     */
    private readonly RequestFactoryInterface $requestFactory;

    /**
     * @var ResponseFactoryInterface
     */
    private readonly ResponseFactoryInterface $responseFactory;

    /**
     * @var ClientFactoryInterface
     */
    private readonly ClientFactoryInterface $clientFactory;

    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $validator;

    /**
     * Shared by every service the facade builds.
     *
     * @var LastResponseHolder
     */
    private readonly LastResponseHolder $lastResponse;

    /**
     * @var SerializerProviderInterface
     */
    private readonly SerializerProviderInterface $serializerProvider;

    /**
     * Kept so the webhook reader is built with the same settings as the services.
     *
     * @var SerializerConfig
     */
    private readonly SerializerConfig $serializerConfig;

    /**
     * Services, keyed by class name: the ones handed in, and the ones built on first use.
     *
     * @var array<class-string, object>
     */
    private array $services;

    /**
     * Everything except $config is optional. Pass a part only to replace the default one.
     *
     * @param ConfigInterface $config
     * @param LoggerInterface|null $logger defaults to a logger that discards everything
     * @param SerializerConfig|null $serializerConfig how responses are read: strictly or
     *        leniently, and how payloads are written. Defaults to the SDK's own settings
     * @param ConfigProviderInterface|null $configProvider overrides $config entirely when given
     * @param ClientFactoryInterface|null $clientFactory
     * @param ValidatorInterface|null $validator checks payloads before they are sent. Defaults to
     *        the SDK's own rules. Pass your own to add or replace rules. Validation can also be
     *        turned off in the configuration
     * @param array<class-string, object> $services ready-made services, keyed by the class they
     *        replace. Services not given here are built on first use
     */
    public function __construct(
        ConfigInterface $config,
        ?LoggerInterface $logger = null,
        ?SerializerConfig $serializerConfig = null,
        ?ConfigProviderInterface $configProvider = null,
        ?ClientFactoryInterface $clientFactory = null,
        ?ValidatorInterface $validator = null,
        array $services = [],
    ) {
        $this->serializerConfig = $serializerConfig ?? new SerializerConfig();
        $this->serializerProvider = new SerializerProvider($this->serializerConfig);

        $this->configProvider = $configProvider ?? new ConfigProvider($config);
        $this->logger = $logger ?? new NullLogger();
        $this->clientFactory = $clientFactory ?? new ClientFactory($this->configProvider);
        $this->requestFactory = new RequestFactory($this->serializerProvider, $this->configProvider);
        $this->responseFactory = new ResponseFactory($this->serializerProvider);
        $this->validator = $validator ?? DefaultValidatorRegistryFactory::create();
        $this->services = $services;
        $this->lastResponse = new LastResponseHolder();
    }

    /**
     * Carriers, pickups, countries and pickup points.
     *
     * @return CourierServiceInterface
     */
    public function courier(): CourierServiceInterface
    {
        /** @var CourierServiceInterface */
        return $this->service(CourierService::class);
    }

    /**
     * Placing, pricing, tracking and cancelling orders.
     *
     * @return OrdersServiceInterface
     */
    public function orders(): OrdersServiceInterface
    {
        /** @var OrdersServiceInterface */
        return $this->service(OrdersService::class);
    }

    /**
     * The account itself: profile, balances, preferences, credentials and telephone numbers.
     *
     * @return AccountServiceInterface
     */
    public function account(): AccountServiceInterface
    {
        /** @var AccountServiceInterface */
        return $this->service(AccountService::class);
    }

    /**
     * The account's address book.
     *
     * @return AddressesServiceInterface
     */
    public function addresses(): AddressesServiceInterface
    {
        /** @var AddressesServiceInterface */
        return $this->service(AddressesService::class);
    }

    /**
     * Bank accounts money box payouts can be sent to.
     *
     * @return BankAccountsServiceInterface
     */
    public function bankAccounts(): BankAccountsServiceInterface
    {
        /** @var BankAccountsServiceInterface */
        return $this->service(BankAccountsService::class);
    }

    /**
     * Saved parcel templates.
     *
     * @return PackTemplatesServiceInterface
     */
    public function packTemplates(): PackTemplatesServiceInterface
    {
        /** @var PackTemplatesServiceInterface */
        return $this->service(PackTemplatesService::class);
    }

    /**
     * City lookup and map tokens.
     *
     * @return GeoServiceInterface
     */
    public function geo(): GeoServiceInterface
    {
        /** @var GeoServiceInterface */
        return $this->service(GeoService::class);
    }

    /**
     * Two-factor tokens for operations that require confirmation.
     *
     * @return TwoFactorTokenServiceInterface
     */
    public function twoFactorToken(): TwoFactorTokenServiceInterface
    {
        /** @var TwoFactorTokenServiceInterface */
        return $this->service(TwoFactorTokenService::class);
    }

    /**
     * Service-wide configuration, carrier notices and coupon checks.
     *
     * @return SystemServiceInterface
     */
    public function system(): SystemServiceInterface
    {
        /** @var SystemServiceInterface */
        return $this->service(SystemService::class);
    }

    /**
     * Availability and credential checks.
     *
     * @return UtilsServiceInterface
     */
    public function utils(): UtilsServiceInterface
    {
        /** @var UtilsServiceInterface */
        return $this->service(UtilsService::class);
    }


    /**
     * Identity verification, where the account requires it before shipping.
     *
     * @return AccountVerificationServiceInterface
     */
    public function accountVerification(): AccountVerificationServiceInterface
    {
        /** @var AccountVerificationServiceInterface */
        return $this->service(AccountVerificationService::class);
    }

    /**
     * Invoices issued to the account, and the documents behind them.
     *
     * @return InvoicesServiceInterface
     */
    public function invoices(): InvoicesServiceInterface
    {
        /** @var InvoicesServiceInterface */
        return $this->service(InvoicesService::class);
    }

    /**
     * Online payments and the prepaid balance.
     *
     * @return PaymentsServiceInterface
     */
    public function payments(): PaymentsServiceInterface
    {
        /** @var PaymentsServiceInterface */
        return $this->service(PaymentsService::class);
    }

    /**
     * Support tickets and the contact forms that feed them.
     *
     * @return TicketsServiceInterface
     */
    public function tickets(): TicketsServiceInterface
    {
        /** @var TicketsServiceInterface */
        return $this->service(TicketsService::class);
    }

    /**
     * The customer-facing help content.
     *
     * @return KnowledgeBaseServiceInterface
     */
    public function knowledgeBase(): KnowledgeBaseServiceInterface
    {
        /** @var KnowledgeBaseServiceInterface */
        return $this->service(KnowledgeBaseService::class);
    }

    /**
     * Cash-on-delivery funds held on the account, and withdrawing them.
     *
     * @return MoneyBoxServiceInterface
     */
    public function moneyBox(): MoneyBoxServiceInterface
    {
        /** @var MoneyBoxServiceInterface */
        return $this->service(MoneyBoxService::class);
    }

    /**
     * Reads the order events Polkurier posts to the webhook address set in the panel.
     *
     * Like the services, it is the instance passed in through `$services` under
     * {@see OrderReader}, or one built on first use, because most clients never read a webhook.
     *
     * @return OrderReaderInterface
     */
    public function orderReader(): OrderReaderInterface
    {
        /** @var OrderReaderInterface */
        return $this->services[OrderReader::class] ??= new OrderReader($this->serializerConfig);
    }

    /**
     * The HTTP response to the last call made through this client, exactly as the API sent it.
     *
     * Use it when a typed result isn't enough, e.g. to read a value the SDK doesn't know yet, which
     * the result shows as null. The body has already been read once, so cast it to a string, which
     * rewinds it: `(string) $client->getLastResponse()?->getBody()`.
     *
     * Only calls through services the client builds itself are tracked, not ones handed in through
     * the constructor's `$services`.
     *
     * @return ResponseInterface|null null before the first call, or if the last call got no
     *         response, e.g. the connection failed or the payload didn't pass validation
     */
    public function getLastResponse(): ?ResponseInterface
    {
        return $this->lastResponse->getResponse();
    }

    /**
     * How the last response differed from what this version of the SDK expects.
     *
     * The SDK reads such a response anyway, as
     * {@see \Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum} describes, so the call
     * succeeds. Each entry says what differed, where in the response, the value the API sent and the
     * type the SDK expected. With logging on, each one is also logged as a warning. Strict reading
     * ({@see \Polkurier\Webservice\Serializer\SerializerConfig}) fails instead, except for unknown
     * enum values.
     *
     * Tracked for the same calls as {@see self::getLastResponse()}.
     *
     * @return list<ResponseDrift> empty when the response was as expected
     */
    public function getLastResponseDrift(): array
    {
        return $this->lastResponse->getDrift();
    }

    /**
     * Returns the service of the given class. That is the one passed in through $services, or
     * one built on first use and reused after that.
     *
     * A class built here is constructed with {@see AbstractService}'s arguments, so that is what
     * it must extend. A service written from scratch against the service interface is handed in
     * ready-made through `$services` instead.
     *
     * @template T of AbstractService
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function service(string $class): object
    {
        /** @var T */
        return $this->services[$class] ??= new $class(
            $this->requestFactory,
            $this->responseFactory,
            $this->logger,
            $this->clientFactory,
            $this->configProvider,
            $this->validator,
            $this->lastResponse,
        );
    }
}
