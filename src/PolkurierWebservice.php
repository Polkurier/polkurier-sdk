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
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Http\Response\ResponseFactoryInterface;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
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
 * The SDK is built from small collaborators — a serializer, request and response factories, an
 * HTTP client factory and one service per functional area — so that a host application can replace
 * any of them. This class exists so that an application which does not want to is not obliged to
 * wire them by hand:
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
 * Services are created on first use and reused afterwards, so asking for one repeatedly is cheap.
 * To substitute a collaborator, pass it to the constructor; anything omitted is built from the
 * configuration.
 *
 * This class holds no connection state and performs no I/O until a service method is called.
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
     * Services, keyed by class name: the ones handed in, and the ones built on first use.
     *
     * @var array<class-string, object>
     */
    private array $services;

    /**
     * Every collaborator is optional. Pass one only to override how the SDK builds it.
     *
     * @param ConfigInterface $config
     * @param LoggerInterface|null $logger defaults to a logger that discards everything, so the
     *                                     SDK never writes anywhere the host did not ask for
     * @param SerializerProviderInterface|null $serializerProvider
     * @param ConfigProviderInterface|null $configProvider overrides $config entirely when given
     * @param ClientFactoryInterface|null $clientFactory
     * @param RequestFactoryInterface|null $requestFactory
     * @param ResponseFactoryInterface|null $responseFactory
     * @param ValidatorInterface|null $validator checks payloads before they are sent; defaults to
     *        the SDK's own rules. Pass your own to add or replace them, or switch the whole thing
     *        off through the configuration
     * @param array<class-string, object> $services ready-made services, keyed by the class they
     *        stand in for. Anything not given here is built on first use
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
     * The service of the given class: the one handed in through $services, or one built on first
     * use and reused afterwards.
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
        );
    }
}
