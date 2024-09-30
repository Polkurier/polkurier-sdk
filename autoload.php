<?php
$basePath = __DIR__ . '/src/PolkurierWebServiceApi/';
require_once($basePath . 'Exception/FatalException.php');
require_once($basePath . 'Exception/ErrorException.php');
require_once($basePath . 'Config.php');
require_once($basePath . 'PolkurierWebService.php');
require_once($basePath . 'Auth.php');
require_once($basePath . 'HTTPClient.php');
require_once($basePath . 'Response.php');
require_once($basePath . 'Request.php');
require_once($basePath . 'Type/PackType.php');
require_once($basePath . 'Type/ResponseStatus.php');
require_once($basePath . 'Type/ReturnCodType.php');
require_once($basePath . 'Type/ReturnTimeCodType.php');
require_once($basePath . 'Type/RodType.php');
require_once($basePath . 'Type/ShipmentType.php');
require_once($basePath . 'Type/CourierMessageSeverity.php');
require_once($basePath . 'Type/CourierMessageType.php');
require_once($basePath . 'Type/OrderItemType.php');
require_once($basePath . 'File.php');
require_once($basePath . 'Util/Arr.php');
require_once($basePath . 'Util/Dates.php');
require_once($basePath . 'Util/Validators.php');
require_once($basePath . 'Entities/CourierServiceInterface.php');
require_once($basePath . 'Entities/RodCourierService.php');
require_once($basePath . 'Entities/Carrier.php');
require_once($basePath . 'Entities/COD.php');
require_once($basePath . 'Entities/Pack.php');
require_once($basePath . 'Entities/Pickup.php');
require_once($basePath . 'Entities/Recipient.php');
require_once($basePath . 'Entities/Sender.php');
require_once($basePath . 'Entities/OrderValuation.php');
require_once($basePath . 'Entities/CourierWithLabelCourierService.php');
require_once($basePath . 'Entities/SmsNotificationRecipientCourierService.php');
require_once($basePath . 'Entities/WeekCollectionCourierService.php');
require_once($basePath . 'Entities/SmsNotificationRecipientWithNameCourierService.php');
require_once($basePath . 'Entities/PhoneNotificationRecipientCourierService.php');
require_once($basePath . 'Entities/CourierMessage.php');
require_once($basePath . 'Methods/MethodInterface.php');
require_once($basePath . 'Methods/AbstractMethod.php');
require_once($basePath . 'Methods/AvailableCarriers.php');
require_once($basePath . 'Methods/CreateOrder.php');
require_once($basePath . 'Methods/OrderValuation.php');
require_once($basePath . 'Methods/GetLabel.php');
require_once($basePath . 'Methods/GetProtocol.php');
require_once($basePath . 'Methods/GetStatus.php');
require_once($basePath . 'Methods/CancelOrder.php');
require_once($basePath . 'Methods/PickupCourier.php');
require_once($basePath . 'Methods/InpostParcelMachines.php');
require_once($basePath . 'Methods/GetCountries.php');
require_once($basePath . 'Methods/PocztexPostOffices.php');
require_once($basePath . 'Methods/GetCouriersMessages.php');
require_once($basePath . 'Methods/TestAuthApi.php');
require_once($basePath . 'Methods/SavePackTemplate.php');
require_once($basePath . 'Methods/SaveBankAccount.php');
require_once($basePath . 'Methods/SaveAddress.php');
require_once($basePath . 'Methods/Kurier48PostOffices.php');
require_once($basePath . 'Methods/IsMultiPickupAvailable.php');
require_once($basePath . 'Methods/InpostPointsMachines.php');
require_once($basePath . 'Methods/Heartbeat.php');
require_once($basePath . 'Methods/GetSupportedCountriesV2.php');
require_once($basePath . 'Methods/GetPackTemplates.php');
require_once($basePath . 'Methods/GetOrders.php');
require_once($basePath . 'Methods/GetMapToken.php');
require_once($basePath . 'Methods/GetCourierPoint.php');
require_once($basePath . 'Methods/GetCourierPickupTime.php');
require_once($basePath . 'Methods/GetBankAccounts.php');
require_once($basePath . 'Methods/GetAddresses.php');
require_once($basePath . 'Methods/DeletePackTemplate.php');
require_once($basePath . 'Methods/DeleteBankAccount.php');
require_once($basePath . 'Methods/DeleteAddress.php');
require_once($basePath . 'Methods/CreateMultiOrderPickup.php');
require_once($basePath . 'Entities/Province.php');
require_once($basePath . 'Entities/PackTemplate.php');
require_once($basePath . 'Entities/Order.php');
require_once($basePath . 'Entities/OrderItem.php');
require_once($basePath . 'Entities/OrderItem.php');
require_once($basePath . 'Entities/LabelLessCourierService.php');
require_once($basePath . 'Entities/HandleWithCareCourierService.php');
require_once($basePath . 'Entities/DeliveryToOwnHandsCourierService.php');
require_once($basePath . 'Entities/CourierWithLabelCourierService.php');
require_once($basePath . 'Entities/CourierPoint.php');
require_once($basePath . 'Entities/CourierPickupTimeRange.php');
require_once($basePath . 'Entities/CourierPickupDate.php');
require_once($basePath . 'Entities/Country.php');
require_once($basePath . 'Entities/Address.php');
require_once($basePath . 'Entities/MapToken.php');
require_once($basePath . 'Entities/BankAccount.php');
require_once($basePath . 'Entities/OrderWaybill.php');
