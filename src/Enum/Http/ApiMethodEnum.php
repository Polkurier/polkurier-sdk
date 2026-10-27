<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Http;

/**
 * Every API method this SDK can call, as the `apimethod` field of a request.
 *
 * Grouped by service, so each block matches one interface under
 * {@see \Polkurier\Webservice\Api\Service}.
 *
 * 2 case names are shorter than their values: OrderValuation is `order_valuation_v2` and
 * GetSupportedCountries is `get_supported_countries_v2`. The value is what is sent.
 */
enum ApiMethodEnum: string implements ApiMethodInterface
{
    // CourierServiceInterface: carriers, collections, pickup points, country coverage
    case AvailableCarriers = 'available_carriers';
    case PickupCourier = 'pickup_courier';
    case GetCourierPickupTime = 'get_courier_pickup_time';
    case GetCountries = 'get_countries';
    case GetSupportedCountries = 'get_supported_countries_v2';
    case GetCourierPoint = 'get_courier_point';

    // OrdersServiceInterface: pricing, placing, tracking, documents
    case OrderValuation = 'order_valuation_v2';
    case GetOrders = 'get_orders';
    case CreateOrder = 'create_order';
    case CancelOrder = 'cancel_order';
    case GetLabel = 'get_label';
    case GetProtocol = 'get_protocol';
    case GetStatus = 'get_status';
    case IsMultiPickupAvailable = 'is_multi_pickup_available';
    case CreateMultiOrderPickup = 'create_multi_order_pickup';
    case GetOrderFile = 'get_order_file';

    // AccountServiceInterface: profile, balances, preferences, credentials, phone numbers
    case GetAccountInfo = 'get_account_info';
    case SaveAccountSettings = 'save_account_settings';
    case SavePayerAddress = 'save_payer_address';
    case ChangePassword = 'change_password';
    case ChangePhonePin = 'change_phone_pin';
    case ValidateUserPin = 'validate_user_pin';
    case ChangeDefaultCourier = 'change_default_courier';
    case ChangeDefaultSenderPoint = 'change_default_sender_point';
    case GetRegulations = 'get_regulations';
    case AcceptRegulations = 'accept_regulations';
    case GetPhoneNumbers = 'get_phone_numbers';
    case SavePhoneNumber = 'save_phone_number';
    case DeletePhoneNumber = 'delete_phone_number';

    // AddressesServiceInterface: the address book
    case GetAddresses = 'get_addresses';
    case SaveAddress = 'save_address';
    case DeleteAddress = 'delete_address';

    // BankAccountsServiceInterface: bank accounts money box payouts can be sent to
    case GetBankAccounts = 'get_bank_accounts';
    case SaveBankAccount = 'save_bank_account';
    case DeleteBankAccount = 'delete_bank_account';

    // PackTemplatesServiceInterface: saved parcel templates
    case GetPackTemplates = 'get_pack_templates';
    case SavePackTemplate = 'save_pack_template';
    case DeletePackTemplate = 'delete_pack_template';

    // GeoServiceInterface: city lookup and the map token
    case FindCity = 'find_city';
    case GetMapToken = 'get_map_token';

    // TwoFactorTokenServiceInterface: confirmation codes for guarded operations
    case TwoFactorTokenRegister = 'two_factor_token_register';
    case TwoFactorTokenResend = 'two_factor_token_resend';
    case TwoFactorTokenVerify = 'two_factor_token_verify';
    case TwoFactorTokenChangeTransportMethod = 'two_factor_token_change_transport_method';

    // SystemServiceInterface: limits, carrier notices, coupons
    case GetSystemInfo = 'get_system_info';
    case GetCouriersMessages = 'get_couriers_messages';
    case ValidateCouponCode = 'validate_coupon_code';

    // UtilsServiceInterface: reachability and credential checks
    case Heartbeat = 'heartbeat';
    case TestAuthApi = 'test_auth_api';

    // AccountVerificationServiceInterface: identity verification
    case RegisterAccountVerification = 'register_account_verification';
    case GetAccountVerificationInfo = 'get_account_verification_info';

    // InvoicesServiceInterface: invoices and their documents
    case GetInvoices = 'get_invoices';
    case GetInvoicePdf = 'get_invoice_pdf';
    case GetInvoiceSummaryList = 'get_invoice_summary_list';

    // PaymentsServiceInterface: online payments and the prepaid balance
    case RegisterPayment = 'register_payment';
    case GetPaymentInfo = 'get_payment_info';
    case GetPrepaidHistory = 'get_prepaid_history';

    // TicketsServiceInterface: support tickets and contact forms
    case CreateTicket = 'create_ticket';
    case CreateTicketMessage = 'create_ticket_message';
    case SetTicketStatus = 'set_ticket_status';
    case GetTickets = 'get_tickets';
    case GetTicketMessages = 'get_ticket_messages';
    case GetTicketMessageFile = 'get_ticket_message_file';
    case GetContactTopics = 'get_contact_topics';
    case GetContactForm = 'get_contact_form';
    case SaveContactForm = 'save_contact_form';

    // KnowledgeBaseServiceInterface: help articles
    case GetKnowledgeBaseArticle = 'get_knowledge_base_article';
    case GetKnowledgeBaseIndex = 'get_knowledge_base_index';
    case SearchKnowledgeBase = 'search_knowledge_base';

    // MoneyBoxServiceInterface: collected funds and paying them out
    case CreateMoneyBoxPayout = 'create_money_box_payout';
    case CancelMoneyBoxPayout = 'cancel_money_box_payout';
    case CalculateMoneyBoxPayoutFee = 'calculate_money_box_payout_fee';
    case GetMoneyBoxConfig = 'get_money_box_config';
    case GetMoneyBoxPayouts = 'get_money_box_payouts';

}
