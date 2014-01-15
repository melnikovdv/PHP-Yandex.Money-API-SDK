<?php

namespace Yandex\YandexMoney\Operation;

/**
 * 
 */
class Operation
{
    /**
     * @var string
     */
    protected $operationId;

    /**
     * @var string
     */
    protected $patternId;

    /**
     * @var string
     */
    protected $direction;

    /**
     * @var string
     */
    protected $amount;

    /**
     * @var integer
     */
    protected $datetime;

    /**
     * @var string
     */
    protected $title;

    /**
     * @param array $operation
     */
    public function __construct(array $operation)
    {
        if (isset($operation['operation_id'])) {
            $this->operationId = $operation['operation_id'];
        }
        if (isset($operation['pattern_id'])) {
            $this->patternId = $operation['pattern_id'];
        }
        if (isset($operation['title'])) {
            $this->title = $operation['title'];
        }
        if (isset($operation['direction'])) {
            $this->direction = $operation['direction'];
        }
        if (isset($operation['amount'])) {
            $this->amount = $operation['amount'];
        }
        if (isset($operation['datetime'])) {
            $this->datetime = strtotime($operation['datetime']);
        }
    }

    /**
     * @return string возвращает идентификатор операции
     */
    public function getOperationId()
    {
        return $this->operationId;
    }

    /**
     * @return string возвращает идентификатор шаблона платежа,
     * по которому совершен платеж. Присутствует только для платежей.
     * Для перевода между счетами пользователей значение: p2p.
     * В остальных случая это операции с магазинами.
     */
    public function getPatternId()
    {
        return $this->patternId;
    }

    /**
     * @return string возвращает направление движения средств.
     * Может принимать значения:
     * in (приход);
     * out (расход).
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * @return string возвращает сумму операции
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return integer возвращает дату и время совершения операции.
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @return string возвращает краткое описание операции (название
     * магазина или источник пополнения).
     */
    public function getTitle()
    {
        return $this->title;
    }
}
