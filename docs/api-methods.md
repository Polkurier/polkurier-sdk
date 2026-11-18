API method index
================

Every endpoint the SDK supports, by its `apimethod` value, with the client method that sends it and
the example that shows it.

[`Enum\Http\ApiMethodEnum`](../src/Enum/Http/ApiMethodEnum.php) holds these values. Its cases are grouped by service, so you can find
the method that sends a case without searching.

Two case names are shorter than their values: [`ApiMethodEnum::OrderValuation`](../src/Enum/Http/ApiMethodEnum.php) is `order_valuation_v2` and
[`ApiMethodEnum::GetSupportedCountries`](../src/Enum/Http/ApiMethodEnum.php) is `get_supported_countries_v2`. The case name describes the call. The
value is what is sent.

To run an example:

```bash
POLKURIER_API_LOGIN=12345 POLKURIER_API_TOKEN=… php examples/available_carriers.php
```

Examples that change data do nothing unless you pass `--confirm`. Examples that need an identifier
take it as the first argument.

| Enum case | Wire value | Client method | Example |
|-----------|-----------|---------------|---------|
| [`ApiMethodEnum::AvailableCarriers`](../src/Enum/Http/ApiMethodEnum.php) | `available_carriers` | [`$client->courier()->getAvailableCarriers()`](../src/Api/Service/CourierServiceInterface.php) | `available_carriers.php` |
| [`ApiMethodEnum::PickupCourier`](../src/Enum/Http/ApiMethodEnum.php) | `pickup_courier` | [`$client->courier()->pickupCourier()`](../src/Api/Service/CourierServiceInterface.php) | `pickup_courier.php` |
| [`ApiMethodEnum::GetCourierPickupTime`](../src/Enum/Http/ApiMethodEnum.php) | `get_courier_pickup_time` | [`$client->courier()->getCourierPickupTime()`](../src/Api/Service/CourierServiceInterface.php) | `get_courier_pickup_time.php` |
| [`ApiMethodEnum::GetCountries`](../src/Enum/Http/ApiMethodEnum.php) | `get_countries` | [`$client->courier()->getCountries()`](../src/Api/Service/CourierServiceInterface.php) | `get_countries.php` |
| [`ApiMethodEnum::GetSupportedCountries`](../src/Enum/Http/ApiMethodEnum.php) | `get_supported_countries_v2` | [`$client->courier()->getSupportedCountries()`](../src/Api/Service/CourierServiceInterface.php) | `get_supported_countries_v2.php` |
| [`ApiMethodEnum::GetCourierPoint`](../src/Enum/Http/ApiMethodEnum.php) | `get_courier_point` | [`$client->courier()->findCourierPoints()`](../src/Api/Service/CourierServiceInterface.php), [`$client->courier()->getCourierPoint()`](../src/Api/Service/CourierServiceInterface.php) | `get_courier_point.php` |
| [`ApiMethodEnum::OrderValuation`](../src/Enum/Http/ApiMethodEnum.php) | `order_valuation_v2` | [`$client->orders()->orderValuation()`](../src/Api/Service/OrdersServiceInterface.php) | `order_valuation_v2.php` |
| [`ApiMethodEnum::GetOrders`](../src/Enum/Http/ApiMethodEnum.php) | `get_orders` | [`$client->orders()->getOrders()`](../src/Api/Service/OrdersServiceInterface.php) | `get_orders.php` |
| [`ApiMethodEnum::CreateOrder`](../src/Enum/Http/ApiMethodEnum.php) | `create_order` | [`$client->orders()->createOrder()`](../src/Api/Service/OrdersServiceInterface.php) | `create_order.php` |
| [`ApiMethodEnum::CancelOrder`](../src/Enum/Http/ApiMethodEnum.php) | `cancel_order` | [`$client->orders()->cancelOrder()`](../src/Api/Service/OrdersServiceInterface.php) | `cancel_order.php` |
| [`ApiMethodEnum::GetLabel`](../src/Enum/Http/ApiMethodEnum.php) | `get_label` | [`$client->orders()->getLabel()`](../src/Api/Service/OrdersServiceInterface.php) | `get_label.php` |
| [`ApiMethodEnum::GetProtocol`](../src/Enum/Http/ApiMethodEnum.php) | `get_protocol` | [`$client->orders()->getProtocol()`](../src/Api/Service/OrdersServiceInterface.php) | `get_protocol.php` |
| [`ApiMethodEnum::GetStatus`](../src/Enum/Http/ApiMethodEnum.php) | `get_status` | [`$client->orders()->getStatus()`](../src/Api/Service/OrdersServiceInterface.php) | `get_status.php` |
| [`ApiMethodEnum::IsMultiPickupAvailable`](../src/Enum/Http/ApiMethodEnum.php) | `is_multi_pickup_available` | [`$client->orders()->isMultiPickupAvailable()`](../src/Api/Service/OrdersServiceInterface.php) | `is_multi_pickup_available.php` |
| [`ApiMethodEnum::CreateMultiOrderPickup`](../src/Enum/Http/ApiMethodEnum.php) | `create_multi_order_pickup` | [`$client->orders()->createMultiOrderPickup()`](../src/Api/Service/OrdersServiceInterface.php) | `create_multi_order_pickup.php` |
| [`ApiMethodEnum::GetOrderFile`](../src/Enum/Http/ApiMethodEnum.php) | `get_order_file` | [`$client->orders()->getOrderFile()`](../src/Api/Service/OrdersServiceInterface.php) | `get_order_file.php` |
| [`ApiMethodEnum::GetAccountInfo`](../src/Enum/Http/ApiMethodEnum.php) | `get_account_info` | [`$client->account()->getAccountInfo()`](../src/Api/Service/AccountServiceInterface.php) | `get_account_info.php` |
| [`ApiMethodEnum::SaveAccountSettings`](../src/Enum/Http/ApiMethodEnum.php) | `save_account_settings` | [`$client->account()->saveAccountSettings()`](../src/Api/Service/AccountServiceInterface.php) | `save_account_settings.php` |
| [`ApiMethodEnum::SavePayerAddress`](../src/Enum/Http/ApiMethodEnum.php) | `save_payer_address` | [`$client->account()->savePayerAddress()`](../src/Api/Service/AccountServiceInterface.php) | `save_payer_address.php` |
| [`ApiMethodEnum::ChangePassword`](../src/Enum/Http/ApiMethodEnum.php) | `change_password` | [`$client->account()->changePassword()`](../src/Api/Service/AccountServiceInterface.php) | `change_password.php` |
| [`ApiMethodEnum::ChangePhonePin`](../src/Enum/Http/ApiMethodEnum.php) | `change_phone_pin` | [`$client->account()->changePhonePin()`](../src/Api/Service/AccountServiceInterface.php) | `change_phone_pin.php` |
| [`ApiMethodEnum::ValidateUserPin`](../src/Enum/Http/ApiMethodEnum.php) | `validate_user_pin` | [`$client->account()->validateUserPin()`](../src/Api/Service/AccountServiceInterface.php) | `validate_user_pin.php` |
| [`ApiMethodEnum::ChangeDefaultCourier`](../src/Enum/Http/ApiMethodEnum.php) | `change_default_courier` | [`$client->account()->changeDefaultCourier()`](../src/Api/Service/AccountServiceInterface.php) | `change_default_courier.php` |
| [`ApiMethodEnum::ChangeDefaultSenderPoint`](../src/Enum/Http/ApiMethodEnum.php) | `change_default_sender_point` | [`$client->account()->changeDefaultSenderPoint()`](../src/Api/Service/AccountServiceInterface.php) | `change_default_sender_point.php` |
| [`ApiMethodEnum::GetRegulations`](../src/Enum/Http/ApiMethodEnum.php) | `get_regulations` | [`$client->account()->getRegulations()`](../src/Api/Service/AccountServiceInterface.php) | `get_regulations.php` |
| [`ApiMethodEnum::AcceptRegulations`](../src/Enum/Http/ApiMethodEnum.php) | `accept_regulations` | [`$client->account()->acceptRegulations()`](../src/Api/Service/AccountServiceInterface.php) | `accept_regulations.php` |
| [`ApiMethodEnum::GetPhoneNumbers`](../src/Enum/Http/ApiMethodEnum.php) | `get_phone_numbers` | [`$client->account()->getPhoneNumbers()`](../src/Api/Service/AccountServiceInterface.php) | `get_phone_numbers.php` |
| [`ApiMethodEnum::SavePhoneNumber`](../src/Enum/Http/ApiMethodEnum.php) | `save_phone_number` | [`$client->account()->savePhoneNumber()`](../src/Api/Service/AccountServiceInterface.php) | `save_phone_number.php` |
| [`ApiMethodEnum::DeletePhoneNumber`](../src/Enum/Http/ApiMethodEnum.php) | `delete_phone_number` | [`$client->account()->deletePhoneNumber()`](../src/Api/Service/AccountServiceInterface.php) | `delete_phone_number.php` |
| [`ApiMethodEnum::GetAddresses`](../src/Enum/Http/ApiMethodEnum.php) | `get_addresses` | [`$client->addresses()->getAddresses()`](../src/Api/Service/AddressesServiceInterface.php) | `get_addresses.php` |
| [`ApiMethodEnum::SaveAddress`](../src/Enum/Http/ApiMethodEnum.php) | `save_address` | [`$client->addresses()->saveAddress()`](../src/Api/Service/AddressesServiceInterface.php) | `save_address.php` |
| [`ApiMethodEnum::DeleteAddress`](../src/Enum/Http/ApiMethodEnum.php) | `delete_address` | [`$client->addresses()->deleteAddress()`](../src/Api/Service/AddressesServiceInterface.php) | `delete_address.php` |
| [`ApiMethodEnum::GetBankAccounts`](../src/Enum/Http/ApiMethodEnum.php) | `get_bank_accounts` | [`$client->bankAccounts()->getBankAccounts()`](../src/Api/Service/BankAccountsServiceInterface.php) | `get_bank_accounts.php` |
| [`ApiMethodEnum::SaveBankAccount`](../src/Enum/Http/ApiMethodEnum.php) | `save_bank_account` | [`$client->bankAccounts()->saveBankAccount()`](../src/Api/Service/BankAccountsServiceInterface.php) | `save_bank_account.php` |
| [`ApiMethodEnum::DeleteBankAccount`](../src/Enum/Http/ApiMethodEnum.php) | `delete_bank_account` | [`$client->bankAccounts()->deleteBankAccount()`](../src/Api/Service/BankAccountsServiceInterface.php) | `delete_bank_account.php` |
| [`ApiMethodEnum::GetPackTemplates`](../src/Enum/Http/ApiMethodEnum.php) | `get_pack_templates` | [`$client->packTemplates()->getPackTemplates()`](../src/Api/Service/PackTemplatesServiceInterface.php) | `get_pack_templates.php` |
| [`ApiMethodEnum::SavePackTemplate`](../src/Enum/Http/ApiMethodEnum.php) | `save_pack_template` | [`$client->packTemplates()->savePackTemplates()`](../src/Api/Service/PackTemplatesServiceInterface.php) | `save_pack_template.php` |
| [`ApiMethodEnum::DeletePackTemplate`](../src/Enum/Http/ApiMethodEnum.php) | `delete_pack_template` | [`$client->packTemplates()->deletePackTemplates()`](../src/Api/Service/PackTemplatesServiceInterface.php) | `delete_pack_template.php` |
| [`ApiMethodEnum::FindCity`](../src/Enum/Http/ApiMethodEnum.php) | `find_city` | [`$client->geo()->findCity()`](../src/Api/Service/GeoServiceInterface.php) | `find_city.php` |
| [`ApiMethodEnum::GetMapToken`](../src/Enum/Http/ApiMethodEnum.php) | `get_map_token` | [`$client->geo()->getMapToken()`](../src/Api/Service/GeoServiceInterface.php) | `get_map_token.php` |
| [`ApiMethodEnum::TwoFactorTokenRegister`](../src/Enum/Http/ApiMethodEnum.php) | `two_factor_token_register` | [`$client->twoFactorToken()->register()`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | `two_factor_token_register.php` |
| [`ApiMethodEnum::TwoFactorTokenResend`](../src/Enum/Http/ApiMethodEnum.php) | `two_factor_token_resend` | [`$client->twoFactorToken()->resend()`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | `two_factor_token_resend.php` |
| [`ApiMethodEnum::TwoFactorTokenVerify`](../src/Enum/Http/ApiMethodEnum.php) | `two_factor_token_verify` | [`$client->twoFactorToken()->verify()`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | `two_factor_token_verify.php` |
| [`ApiMethodEnum::TwoFactorTokenChangeTransportMethod`](../src/Enum/Http/ApiMethodEnum.php) | `two_factor_token_change_transport_method` | [`$client->twoFactorToken()->changeTransportMethod()`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | `two_factor_token_change_transport_method.php` |
| [`ApiMethodEnum::GetSystemInfo`](../src/Enum/Http/ApiMethodEnum.php) | `get_system_info` | [`$client->system()->getSystemInfo()`](../src/Api/Service/SystemServiceInterface.php) | `get_system_info.php` |
| [`ApiMethodEnum::GetCouriersMessages`](../src/Enum/Http/ApiMethodEnum.php) | `get_couriers_messages` | [`$client->system()->getCouriersMessages()`](../src/Api/Service/SystemServiceInterface.php) | `get_couriers_messages.php` |
| [`ApiMethodEnum::ValidateCouponCode`](../src/Enum/Http/ApiMethodEnum.php) | `validate_coupon_code` | [`$client->system()->validateCouponCode()`](../src/Api/Service/SystemServiceInterface.php) | `validate_coupon_code.php` |
| [`ApiMethodEnum::Heartbeat`](../src/Enum/Http/ApiMethodEnum.php) | `heartbeat` | [`$client->utils()->heartbeat()`](../src/Api/Service/UtilsServiceInterface.php) | `heartbeat.php` |
| [`ApiMethodEnum::TestAuthApi`](../src/Enum/Http/ApiMethodEnum.php) | `test_auth_api` | [`$client->utils()->testAuthApi()`](../src/Api/Service/UtilsServiceInterface.php) | `test_auth_api.php` |
| [`ApiMethodEnum::RegisterAccountVerification`](../src/Enum/Http/ApiMethodEnum.php) | `register_account_verification` | [`$client->accountVerification()->register()`](../src/Api/Service/AccountVerificationServiceInterface.php) | `register_account_verification.php` |
| [`ApiMethodEnum::GetAccountVerificationInfo`](../src/Enum/Http/ApiMethodEnum.php) | `get_account_verification_info` | [`$client->accountVerification()->getInfo()`](../src/Api/Service/AccountVerificationServiceInterface.php) | `get_account_verification_info.php` |
| [`ApiMethodEnum::GetInvoices`](../src/Enum/Http/ApiMethodEnum.php) | `get_invoices` | [`$client->invoices()->getInvoices()`](../src/Api/Service/InvoicesServiceInterface.php) | `get_invoices.php` |
| [`ApiMethodEnum::GetInvoicePdf`](../src/Enum/Http/ApiMethodEnum.php) | `get_invoice_pdf` | [`$client->invoices()->getInvoicePdf()`](../src/Api/Service/InvoicesServiceInterface.php) | `get_invoice_pdf.php` |
| [`ApiMethodEnum::GetInvoiceSummaryList`](../src/Enum/Http/ApiMethodEnum.php) | `get_invoice_summary_list` | [`$client->invoices()->getInvoiceSummaryList()`](../src/Api/Service/InvoicesServiceInterface.php) | `get_invoice_summary_list.php` |
| [`ApiMethodEnum::RegisterPayment`](../src/Enum/Http/ApiMethodEnum.php) | `register_payment` | [`$client->payments()->registerPayment()`](../src/Api/Service/PaymentsServiceInterface.php) | `register_payment.php` |
| [`ApiMethodEnum::GetPaymentInfo`](../src/Enum/Http/ApiMethodEnum.php) | `get_payment_info` | [`$client->payments()->getPaymentInfo()`](../src/Api/Service/PaymentsServiceInterface.php) | `get_payment_info.php` |
| [`ApiMethodEnum::GetPrepaidHistory`](../src/Enum/Http/ApiMethodEnum.php) | `get_prepaid_history` | [`$client->payments()->getPrepaidHistory()`](../src/Api/Service/PaymentsServiceInterface.php) | `get_prepaid_history.php` |
| [`ApiMethodEnum::CreateTicket`](../src/Enum/Http/ApiMethodEnum.php) | `create_ticket` | [`$client->tickets()->createTicket()`](../src/Api/Service/TicketsServiceInterface.php) | `create_ticket.php` |
| [`ApiMethodEnum::CreateTicketMessage`](../src/Enum/Http/ApiMethodEnum.php) | `create_ticket_message` | [`$client->tickets()->createTicketMessage()`](../src/Api/Service/TicketsServiceInterface.php) | `create_ticket_message.php` |
| [`ApiMethodEnum::SetTicketStatus`](../src/Enum/Http/ApiMethodEnum.php) | `set_ticket_status` | [`$client->tickets()->setTicketStatus()`](../src/Api/Service/TicketsServiceInterface.php) | `set_ticket_status.php` |
| [`ApiMethodEnum::GetTickets`](../src/Enum/Http/ApiMethodEnum.php) | `get_tickets` | [`$client->tickets()->getTickets()`](../src/Api/Service/TicketsServiceInterface.php) | `get_tickets.php` |
| [`ApiMethodEnum::GetTicketMessages`](../src/Enum/Http/ApiMethodEnum.php) | `get_ticket_messages` | [`$client->tickets()->getTicketMessages()`](../src/Api/Service/TicketsServiceInterface.php) | `get_ticket_messages.php` |
| [`ApiMethodEnum::GetTicketMessageFile`](../src/Enum/Http/ApiMethodEnum.php) | `get_ticket_message_file` | [`$client->tickets()->getTicketMessageFile()`](../src/Api/Service/TicketsServiceInterface.php) | `get_ticket_message_file.php` |
| [`ApiMethodEnum::GetContactTopics`](../src/Enum/Http/ApiMethodEnum.php) | `get_contact_topics` | [`$client->tickets()->getContactTopics()`](../src/Api/Service/TicketsServiceInterface.php) | `get_contact_topics.php` |
| [`ApiMethodEnum::GetContactForm`](../src/Enum/Http/ApiMethodEnum.php) | `get_contact_form` | [`$client->tickets()->getContactForm()`](../src/Api/Service/TicketsServiceInterface.php) | `get_contact_form.php` |
| [`ApiMethodEnum::SaveContactForm`](../src/Enum/Http/ApiMethodEnum.php) | `save_contact_form` | [`$client->tickets()->saveContactForm()`](../src/Api/Service/TicketsServiceInterface.php) | `save_contact_form.php` |
| [`ApiMethodEnum::GetKnowledgeBaseArticle`](../src/Enum/Http/ApiMethodEnum.php) | `get_knowledge_base_article` | [`$client->knowledgeBase()->getArticle()`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | `get_knowledge_base_article.php` |
| [`ApiMethodEnum::GetKnowledgeBaseIndex`](../src/Enum/Http/ApiMethodEnum.php) | `get_knowledge_base_index` | [`$client->knowledgeBase()->getIndex()`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | `get_knowledge_base_index.php` |
| [`ApiMethodEnum::SearchKnowledgeBase`](../src/Enum/Http/ApiMethodEnum.php) | `search_knowledge_base` | [`$client->knowledgeBase()->search()`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | `search_knowledge_base.php` |
| [`ApiMethodEnum::CreateMoneyBoxPayout`](../src/Enum/Http/ApiMethodEnum.php) | `create_money_box_payout` | [`$client->moneyBox()->createPayout()`](../src/Api/Service/MoneyBoxServiceInterface.php) | `create_money_box_payout.php` |
| [`ApiMethodEnum::CancelMoneyBoxPayout`](../src/Enum/Http/ApiMethodEnum.php) | `cancel_money_box_payout` | [`$client->moneyBox()->cancelPayout()`](../src/Api/Service/MoneyBoxServiceInterface.php) | `cancel_money_box_payout.php` |
| [`ApiMethodEnum::CalculateMoneyBoxPayoutFee`](../src/Enum/Http/ApiMethodEnum.php) | `calculate_money_box_payout_fee` | [`$client->moneyBox()->calculatePayoutFee()`](../src/Api/Service/MoneyBoxServiceInterface.php) | `calculate_money_box_payout_fee.php` |
| [`ApiMethodEnum::GetMoneyBoxConfig`](../src/Enum/Http/ApiMethodEnum.php) | `get_money_box_config` | [`$client->moneyBox()->getMoneyBoxConfig()`](../src/Api/Service/MoneyBoxServiceInterface.php) | `get_money_box_config.php` |
| [`ApiMethodEnum::GetMoneyBoxPayouts`](../src/Enum/Http/ApiMethodEnum.php) | `get_money_box_payouts` | [`$client->moneyBox()->getPayouts()`](../src/Api/Service/MoneyBoxServiceInterface.php) | `get_money_box_payouts.php` |

[`CourierServiceInterface::findCourierPoints()`](../src/Api/Service/CourierServiceInterface.php) and [`CourierServiceInterface::getCourierPoint()`](../src/Api/Service/CourierServiceInterface.php) both send `get_courier_point`: one
searches for points and the other looks one up by id.

[Services](services.md) describes what each method does, grouped by task. The docblock on each
interface method has the details: the payload it sends, the success responses and their variants,
and the most common errors.
