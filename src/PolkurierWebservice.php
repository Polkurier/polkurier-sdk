<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice;

use Polkurier\Webservice\Api\Service\AccountServiceInterface;
use Polkurier\Webservice\Api\Service\AccountVerificationServiceInterface;
use Polkurier\Webservice\Api\Service\AddressesServiceInterface;
use Polkurier\Webservice\Api\Service\BankAccountsServiceInterface;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Api\Service\CourierServiceInterface;
use Polkurier\Webservice\Api\ConfigInterface;
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
use Polkurier\Webservice\ConfigProvider;
use Polkurier\Webservice\Http\ClientFactory;
use Polkurier\Webservice\Http\ClientFactoryInterface;
use Polkurier\Webservice\Http\Request\RequestFactory;
use Polkurier\Webservice\Http\Request\RequestFactoryInterface;
use Polkurier\Webservice\Http\Response\LastResponseHolder;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Http\Response\ResponseFactoryInterface;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Polkurier\Webservice\Serializer\UnknownEnumValue;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;
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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
/**
 * Entry point to the Polkurier web API.
 *
 * The SDK is made of small parts: a serializer, request and response factories, an HTTP client
 * factory and one service per API area. Any of them can be replaced, and this class wires up the
 * rest:
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
 * Services are created on first use and then reused. To swap a part, pass it to the constructor.
 * Anything you leave out is built from the configuration.
 *
 * No I/O happens until you call a service method.
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
     * @param SerializerProviderInterface|null $serializerProvider
     * @param ConfigProviderInterface|null $configProvider overrides $config entirely when given
     * @param ClientFactoryInterface|null $clientFactory
     * @param RequestFactoryInterface|null $requestFactory
     * @param ResponseFactoryInterface|null $responseFactory
     * @param ValidatorInterface|null $validator checks payloads before they are sent. Defaults to
     *        the SDK's own rules. Pass your own to add or replace rules. Validation can also be
     *        turned off in the configuration
     * @param array<class-string, object> $services ready-made services, keyed by the class they
     *        replace. Services not given here are built on first use
     */
    public function __construct(
        ConfigInterface $config,
        ?LoggerInterface $logger = null,
        ?SerializerProviderInterface $serializerProvider = null,
        ?ConfigProviderInterface $configProvider = null,
        ?ClientFactoryInterface $clientFactory = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?ValidatorInterface $validator = null,
        array $services = [],
    ) {
        $serializerProvider ??= new SerializerProvider();

        $this->configProvider = $configProvider ?? new ConfigProvider($config);
        $this->logger = $logger ?? new NullLogger();
        $this->clientFactory = $clientFactory ?? new ClientFactory($this->configProvider);
        $this->requestFactory = $requestFactory ?? new RequestFactory($serializerProvider, $this->configProvider);
        $this->responseFactory = $responseFactory ?? new ResponseFactory($serializerProvider);
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
     * Bank accounts used for cash-on-delivery settlement.
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
     * The enum values in the last response that the SDK doesn't know.
     *
     * Such a value reads as null, or is left out of a list, so the call succeeds. Each entry says
     * which enum, the value as the API sent it, and where in the response it was. With logging on,
     * each one is also logged as a warning.
     *
     * Tracked for the same calls as {@see self::getLastResponse()}.
     *
     * @return list<UnknownEnumValue> empty when every value was known
     */
    public function getLastUnknownEnumValues(): array
    {
        return $this->lastResponse->getUnknownEnumValues();
    }

    /**
     * Returns the service of the given class. That is the one passed in through $services, or
     * one built on first use and reused after that.
     *
     * @template T of object
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
