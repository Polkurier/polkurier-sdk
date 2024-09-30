<?php
namespace PolkurierWebServiceApi\Entities;

use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Type\ReturnCodType;
use PolkurierWebServiceApi\Type\ReturnTimeCodType;


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
    private $bankAccount = '';

    /**
     * @var string
     */
    private $returnCod = ReturnCodType::BA;

    /**
     * @param string $type
     * @return COD
     */
    public function setType($type)
    {
        $this->type = (string)$type;
        return $this;
    }

    /**
     * @param string $returnCod
     * @return COD
     */
    public function setReturnCod($returnCod)
    {
        $this->returnCod = (string)$returnCod;
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
     * @param string $bankAccount
     * @return COD
     * @throws ErrorException
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

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'codtype' => $this->type,
            'codamount' => $this->amount,
            'codbankaccount' => $this->bankAccount,
            'return_cod' => $this->returnCod,
        ];
    }

}
