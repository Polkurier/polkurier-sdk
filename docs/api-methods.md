API method index
================

Every endpoint the SDK supports, by its `apimethod` value, with the client method that sends it and
the example that shows it.

`Enum\Http\ApiMethodEnum` holds these values. Its cases are grouped by service, so you can find
the method that sends a case without searching.

Two case names are shorter than their values: `OrderValuation` is `order_valuation_v2` and
`GetSupportedCountries` is `get_supported_countries_v2`. The case name describes the call. The
value is what is sent.

To run an example:

```bash
POLKURIER_API_LOGIN=12345 POLKURIER_API_TOKEN=… php examples/available_carriers.php
```

Examples that change data do nothing unless you pass `--confirm`. Examples that need an identifier
take it as the first argument.

| Enum case | Wire value | Client method | Example |
|-----------|-----------|---------------|---------|
| `AvailableCarriers` | `available_carriers` | `courier()->availableCarriers()` | `available_carriers.php` |
| `PickupCourier` | `pickup_courier` | `courier()->pickupCourier()` | `pickup_courier.php` |
| `GetCourierPickupTime` | `get_courier_pickup_time` | `courier()->getCourierPickupTime()` | `get_courier_pickup_time.php` |
| `GetCountries` | `get_countries` | `courier()->getCountries()` | `get_countries.php` |
| `GetSupportedCountries` | `get_supported_countries_v2` | `courier()->getSupportedCountries()` | `get_supported_countries_v2.php` |
| `GetCourierPoint` | `get_courier_point` | `courier()->findCourierPoints()`, `courier()->getCourierPoint()` | `get_courier_point.php` |
| `OrderValuation` | `order_valuation_v2` | `orders()->orderValuation()` | `order_valuation_v2.php` |
| `GetOrders` | `get_orders` | `orders()->getOrders()` | `get_orders.php` |
| `CreateOrder` | `create_order` | `orders()->createOrder()` | `create_order.php` |
| `CancelOrder` | `cancel_order` | `orders()->cancelOrder()` | `cancel_order.php` |
| `GetLabel` | `get_label` | `orders()->getLabel()` | `get_label.php` |
| `GetProtocol` | `get_protocol` | `orders()->getProtocol()` | `get_protocol.php` |
| `GetStatus` | `get_status` | `orders()->getStatus()` | `get_status.php` |
| `IsMultiPickupAvailable` | `is_multi_pickup_available` | `orders()->isMultiPickupAvailable()` | `is_multi_pickup_available.php` |
| `CreateMultiOrderPickup` | `create_multi_order_pickup` | `orders()->createMultiOrderPickup()` | `create_multi_order_pickup.php` |
| `GetOrderFile` | `get_order_file` | `orders()->getOrderFile()` | `get_order_file.php` |
| `GetAccountInfo` | `get_account_info` | `account()->getAccountInfo()` | `get_account_info.php` |
| `SaveAccountSettings` | `save_account_settings` | `account()->saveAccountSettings()` | `save_account_settings.php` |
| `SavePayerAddress` | `save_payer_address` | `account()->savePayerAddress()` | `save_payer_address.php` |
| `ChangePassword` | `change_password` | `account()->changePassword()` | `change_password.php` |
| `ChangePhonePin` | `change_phone_pin` | `account()->changePhonePin()` | `change_phone_pin.php` |
| `ValidateUserPin` | `validate_user_pin` | `account()->validateUserPin()` | `validate_user_pin.php` |
| `ChangeDefaultCourier` | `change_default_courier` | `account()->changeDefaultCourier()` | `change_default_courier.php` |
| `ChangeDefaultSenderPoint` | `change_default_sender_point` | `account()->changeDefaultSenderPoint()` | `change_default_sender_point.php` |
| `GetRegulations` | `get_regulations` | `account()->getRegulations()` | `get_regulations.php` |
| `AcceptRegulations` | `accept_regulations` | `account()->acceptRegulations()` | `accept_regulations.php` |
| `GetPhoneNumbers` | `get_phone_numbers` | `account()->getPhoneNumbers()` | `get_phone_numbers.php` |
| `SavePhoneNumber` | `save_phone_number` | `account()->savePhoneNumber()` | `save_phone_number.php` |
| `DeletePhoneNumber` | `delete_phone_number` | `account()->deletePhoneNumber()` | `delete_phone_number.php` |
| `GetAddresses` | `get_addresses` | `addresses()->getAddresses()` | `get_addresses.php` |
| `SaveAddress` | `save_address` | `addresses()->saveAddress()` | `save_address.php` |
| `DeleteAddress` | `delete_address` | `addresses()->deleteAddress()` | `delete_address.php` |
| `GetBankAccounts` | `get_bank_accounts` | `bankAccounts()->getBankAccounts()` | `get_bank_accounts.php` |
| `SaveBankAccount` | `save_bank_account` | `bankAccounts()->saveBankAccount()` | `save_bank_account.php` |
| `DeleteBankAccount` | `delete_bank_account` | `bankAccounts()->deleteBankAccount()` | `delete_bank_account.php` |
| `GetPackTemplates` | `get_pack_templates` | `packTemplates()->getPackTemplates()` | `get_pack_templates.php` |
| `SavePackTemplate` | `save_pack_template` | `packTemplates()->savePackTemplates()` | `save_pack_template.php` |
| `DeletePackTemplate` | `delete_pack_template` | `packTemplates()->deletePackTemplates()` | `delete_pack_template.php` |
| `FindCity` | `find_city` | `geo()->findCity()` | `find_city.php` |
| `GetMapToken` | `get_map_token` | `geo()->getMapToken()` | `get_map_token.php` |
| `TwoFactorTokenRegister` | `two_factor_token_register` | `twoFactorToken()->twoFactorTokenRegister()` | `two_factor_token_register.php` |
| `TwoFactorTokenResend` | `two_factor_token_resend` | `twoFactorToken()->twoFactorTokenResend()` | `two_factor_token_resend.php` |
| `TwoFactorTokenVerify` | `two_factor_token_verify` | `twoFactorToken()->twoFactorTokenVerify()` | `two_factor_token_verify.php` |
| `TwoFactorTokenChangeTransportMethod` | `two_factor_token_change_transport_method` | `twoFactorToken()->twoFactorTokenChangeTransportMethod()` | `two_factor_token_change_transport_method.php` |
| `GetSystemInfo` | `get_system_info` | `system()->getSystemInfo()` | `get_system_info.php` |
| `GetCouriersMessages` | `get_couriers_messages` | `system()->getCouriersMessages()` | `get_couriers_messages.php` |
| `ValidateCouponCode` | `validate_coupon_code` | `system()->validateCouponCode()` | `validate_coupon_code.php` |
| `Heartbeat` | `heartbeat` | `utils()->heartbeat()` | `heartbeat.php` |
| `TestAuthApi` | `test_auth_api` | `utils()->testAuthApi()` | `test_auth_api.php` |
| `RegisterAccountVerification` | `register_account_verification` | `accountVerification()->register()` | `register_account_verification.php` |
| `GetAccountVerificationInfo` | `get_account_verification_info` | `accountVerification()->getInfo()` | `get_account_verification_info.php` |
| `GetInvoices` | `get_invoices` | `invoices()->getInvoices()` | `get_invoices.php` |
| `GetInvoicePdf` | `get_invoice_pdf` | `invoices()->getInvoicePdf()` | `get_invoice_pdf.php` |
| `GetInvoiceSummaryList` | `get_invoice_summary_list` | `invoices()->getInvoiceSummaryList()` | `get_invoice_summary_list.php` |
| `RegisterPayment` | `register_payment` | `payments()->registerPayment()` | `register_payment.php` |
| `GetPaymentInfo` | `get_payment_info` | `payments()->getPaymentInfo()` | `get_payment_info.php` |
| `GetPrepaidHistory` | `get_prepaid_history` | `payments()->getPrepaidHistory()` | `get_prepaid_history.php` |
| `CreateTicket` | `create_ticket` | `tickets()->createTicket()` | `create_ticket.php` |
| `CreateTicketMessage` | `create_ticket_message` | `tickets()->createTicketMessage()` | `create_ticket_message.php` |
| `SetTicketStatus` | `set_ticket_status` | `tickets()->setTicketStatus()` | `set_ticket_status.php` |
| `GetTickets` | `get_tickets` | `tickets()->getTickets()` | `get_tickets.php` |
| `GetTicketMessages` | `get_ticket_messages` | `tickets()->getTicketMessages()` | `get_ticket_messages.php` |
| `GetTicketMessageFile` | `get_ticket_message_file` | `tickets()->getTicketMessageFile()` | `get_ticket_message_file.php` |
| `GetContactTopics` | `get_contact_topics` | `tickets()->getContactTopics()` | `get_contact_topics.php` |
| `GetContactForm` | `get_contact_form` | `tickets()->getContactForm()` | `get_contact_form.php` |
| `SaveContactForm` | `save_contact_form` | `tickets()->saveContactForm()` | `save_contact_form.php` |
| `GetKnowledgeBaseArticle` | `get_knowledge_base_article` | `knowledgeBase()->getArticle()` | `get_knowledge_base_article.php` |
| `GetKnowledgeBaseIndex` | `get_knowledge_base_index` | `knowledgeBase()->getIndex()` | `get_knowledge_base_index.php` |
| `SearchKnowledgeBase` | `search_knowledge_base` | `knowledgeBase()->search()` | `search_knowledge_base.php` |
| `CreateMoneyBoxPayout` | `create_money_box_payout` | `moneyBox()->createPayout()` | `create_money_box_payout.php` |
| `CancelMoneyBoxPayout` | `cancel_money_box_payout` | `moneyBox()->cancelPayout()` | `cancel_money_box_payout.php` |
| `CalculateMoneyBoxPayoutFee` | `calculate_money_box_payout_fee` | `moneyBox()->calculatePayoutFee()` | `calculate_money_box_payout_fee.php` |
| `GetMoneyBoxConfig` | `get_money_box_config` | `moneyBox()->getConfig()` | `get_money_box_config.php` |
| `GetMoneyBoxPayouts` | `get_money_box_payouts` | `moneyBox()->getPayouts()` | `get_money_box_payouts.php` |

Grouped by service
------------------

There are 75 client methods and 74 endpoints. `findCourierPoints()` and `getCourierPoint()` both
send `get_courier_point`: one searches for points and the other looks one up by id. The table above
lists both against that endpoint.

| Service | Client methods |
|---------|----------------|
| `CourierServiceInterface` | 7 |
| `OrdersServiceInterface` | 10 |
| `AccountServiceInterface` | 13 |
| `TicketsServiceInterface` | 9 |
| `MoneyBoxServiceInterface` | 5 |
| `TwoFactorTokenServiceInterface` | 4 |
| `AddressesServiceInterface` | 3 |
| `BankAccountsServiceInterface` | 3 |
| `PackTemplatesServiceInterface` | 3 |
| `InvoicesServiceInterface` | 3 |
| `PaymentsServiceInterface` | 3 |
| `SystemServiceInterface` | 3 |
| `KnowledgeBaseServiceInterface` | 3 |
| `GeoServiceInterface` | 2 |
| `AccountVerificationServiceInterface` | 2 |
| `UtilsServiceInterface` | 2 |

[Services](services.md) describes what each method does and returns. The docblock on each
interface method has the details: the payload it sends, the success responses and their variants,
and the most common errors.
