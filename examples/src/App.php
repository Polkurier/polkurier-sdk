<?php

require_once 'MethodExecutor.php';

class App
{
    const GET_AVAILABLE_CARRIERS_METHOD = 'getAvailableCarriers';
    const HEALTH_BEAT_METHOD = 'healthBeat';
    const TEST_AUTH_API_METHOD = 'testAuthApi';
    const GET_SUPPORTED_COUNTRIES_V2_METHOD = 'supportedCountriesV2';
    const GET_COURIER_PICKUP_TIME_METHOD = 'courierPickupTime';
    const GET_COURIER_POINTS_METHOD = 'courierPoints';
    const IS_MULTI_PACK_AVAILABLE_METHOD = 'isMultiPackAvailable';
    const CREATE_MULTI_ORDER_PICKUP_METHOD = 'createMultiOrderPickup';
    const GET_ADDRESSES_METHOD = 'getAddresses';
    const DELETE_ADDRESS_METHOD = 'deleteAddresses';
    const GET_BANK_ACCOUNTS_METHOD = 'getBankAccounts';
    const SAVE_BANK_ACCOUNT_METHOD = 'saveBankAccount';
    const DELETE_BANK_ACCOUNT_METHOD = 'deleteBankAccount';
    const GET_PACK_TEMPLATES_METHOD = 'getPackTemplates';
    const SAVE_PACK_TEMPLATES_METHOD = 'savePackTemplate';
    const DELETE_PACK_TEMPLATES_METHOD = 'deletePackTemplate';
    const GET_MAP_TOKEN_METHOD = 'getMapToken';
    const ORDER_VALUATION_METHOD = 'getOrderValuation';
    const GET_ORDERS_METHOD = 'getOrders';
    const CREATE_ORDER_METHOD = 'createOrder';
    const GET_SINGLE_ORDER = 'getOrderDetails';
    const SAVE_ADDRESS_METHOD = 'saveAddress';

    /**
     * @param string $methodName
     * @param array $params
     * @return array
     */
    public function run($methodName)
    {
        $methodExecutor = new MethodExecutor();

        switch ($methodName) {
            case self::GET_AVAILABLE_CARRIERS_METHOD:
                return $methodExecutor->getAvailableCarriers();
            case self::HEALTH_BEAT_METHOD:
                return $methodExecutor->healthBeat();
            case self::TEST_AUTH_API_METHOD:
                return $methodExecutor->testAuthApi();
            case self::GET_SUPPORTED_COUNTRIES_V2_METHOD:
                return $methodExecutor->getSupportedCountriesV2();
            case self::GET_COURIER_PICKUP_TIME_METHOD:
                return $methodExecutor->getCourierPickupTime();
            case self::GET_COURIER_POINTS_METHOD:
                return $methodExecutor->getCourierPoints();
            case self::IS_MULTI_PACK_AVAILABLE_METHOD:
                return $methodExecutor->isMultiPickupAvailable();
            case self::CREATE_MULTI_ORDER_PICKUP_METHOD:
                return $methodExecutor->createMultiOrderPickup();
            case self::GET_ADDRESSES_METHOD:
                return $methodExecutor->getAddresses();
            case self::DELETE_ADDRESS_METHOD:
                return $methodExecutor->deleteAddress();
            case self::GET_BANK_ACCOUNTS_METHOD:
                return $methodExecutor->getBankAccounts();
            case self::SAVE_BANK_ACCOUNT_METHOD:
                return $methodExecutor->saveBankAccount();
            case self::DELETE_BANK_ACCOUNT_METHOD:
                return $methodExecutor->deleteBankAccount();
            case self::GET_PACK_TEMPLATES_METHOD:
                return $methodExecutor->getPackTemplates();
            case self::SAVE_PACK_TEMPLATES_METHOD:
                return $methodExecutor->savePackTemplate();
            case self::DELETE_PACK_TEMPLATES_METHOD:
                return $methodExecutor->deletePackTemplate();
            case self::GET_MAP_TOKEN_METHOD:
                return $methodExecutor->getMapToken();
            case self::ORDER_VALUATION_METHOD:
                return $methodExecutor->getOrderValuation();
            case self::GET_ORDERS_METHOD:
                return $methodExecutor->getOrders();
            case self::CREATE_ORDER_METHOD:
                return $methodExecutor->createOrder();
            case self::GET_SINGLE_ORDER:
                return $methodExecutor->getOrderDetails();
            case self::SAVE_ADDRESS_METHOD:
                return $methodExecutor->saveAddress();
            default:
                throw new \RuntimeException("Method $methodName not implemented");
        }
    }
}