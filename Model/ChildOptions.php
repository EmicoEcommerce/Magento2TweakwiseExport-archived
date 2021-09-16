<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */

namespace Emico\TweakwiseExport\Model;

/**
 * Used solely for stock calculation for bundle products
 *
 * Class ChildOptions
 * @package Emico\TweakwiseExport\Model
 */
class ChildOptions
{
    /**
     * @var int
     */
    protected ?int $optionId = null;

    /**
     * @var bool
     */
    protected ?bool $isRequired = null;

    /**
     * ChildOptions constructor.
     * @param int|null $optionId
     * @param null $isRequired
     */
    public function __construct(int $optionId = null, $isRequired = null)
    {
        $this->optionId = $optionId;
        $this->isRequired = $isRequired;
    }

    /**
     * @return int
     */
    public function getOptionId(): ?int
    {
        return $this->optionId;
    }

    /**
     * @param int $optionId
     */
    public function setOptionId(int $optionId): void
    {
        $this->optionId = $optionId;
    }

    /**
     * @return bool|null
     */
    public function isRequired(): ?bool
    {
        return $this->isRequired;
    }

    /**
     * @param bool $isRequired
     */
    public function setIsRequired(bool $isRequired): void
    {
        $this->isRequired = $isRequired;
    }
}
