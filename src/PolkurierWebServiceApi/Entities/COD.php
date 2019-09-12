<?php
namespace PolkurierWebServiceApi\Entities;

use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Type\ReturnCodType;
use PolkurierWebServiceApi\Type\ReturnTimeCodType;

/**
 * Class COD
 * @package PolkurierWebServiceApi\Entities
 *
 */
class COD
{
    /**
     * @var string
     */
    private $type = ReturnTimeCodType::TYPE_ST;

    /**
     * @var float
     */
    private $amount = 0;
    
    /**
     * @var string
     */
    private $bankAccount = null;

    /**
     * @var string
     */
    private $returnCod = ReturnCodType::BA;
    
    /**
     * @param mixed $type
     * @return COD
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $returnCod
     * @return COD
     */
    public function setReturnCod($returnCod)
    {
        $this->returnCod = $returnCod;
        return $this;
    }

    /**
     * @param float $amount
     * @return COD
     */
    public function setAmount($amount)
    {
        $this->amount = (float)$amount;
        return $this;
    }

    /**
     * @param $bankAccount
     * @return $this
     * @throws \PolkurierWebServiceApi\Exception\ErrorException
     */
    public function setBankAccount($bankAccount)
    {
        if (!$bankAccount) {
            throw new ErrorException('Numer konta bankowego nie może być pusty');
        }
        $this->bankAccount = $bankAccount;
        return $this;
    }

    /**
     * @return string
     */
    public function getReturnCod()
    {
        return $this->returnCod;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getBankAccount()
    {
        return $this->bankAccount;
    }

    public function toArray()
    {
        return [
            'codtype' => $this->type,
            'codamount' => $this->amount,
            'codbankaccount' => $this->bankAccount,
            'returncod' => $this->returnCod,
        ];
    }

}