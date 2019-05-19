<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SalesRepository")
 */
class Sales
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    private $cost;

    /**
     * @ORM\Column(type="float")
     */
    private $fee;

    /**
     * @ORM\Column(type="float")
     */
    private $gbp;

    /**
     * @ORM\Column(type="float")
     */
    private $btc;

    /**
     * @ORM\Column(type="float")
     */
    private $buy_rate;

    /**
     * @ORM\Column(type="float")
     */
    private $sell_rate;

    /**
     * @ORM\Column(type="float")
     */
    private $value_gbp;

    /**
     * @ORM\Column(type="float")
     */
    private $fee_sell;

    /**
     * @ORM\Column(type="float")
     */
    private $subtotal;

    /**
     * @ORM\Column(type="float")
     */
    private $profit_loss;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(float $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    public function getFee(): ?float
    {
        return $this->fee;
    }

    public function setFee(float $fee): self
    {
        $this->fee = $fee;

        return $this;
    }

    public function getGbp(): ?float
    {
        return $this->gbp;
    }

    public function setGbp(float $gbp): self
    {
        $this->gbp = $gbp;

        return $this;
    }

    public function getBtc(): ?float
    {
        return $this->btc;
    }

    public function setBtc(float $btc): self
    {
        $this->btc = $btc;

        return $this;
    }

    public function getBuyRate(): ?float
    {
        return $this->buy_rate;
    }

    public function setBuyRate(float $buy_rate): self
    {
        $this->buy_rate = $buy_rate;

        return $this;
    }

    public function getSellRate(): ?float
    {
        return $this->sell_rate;
    }

    public function setSellRate(float $sell_rate): self
    {
        $this->sell_rate = $sell_rate;

        return $this;
    }

    public function getValueGbp(): ?float
    {
        return $this->value_gbp;
    }

    public function setValueGbp(float $value_gbp): self
    {
        $this->value_gbp = $value_gbp;

        return $this;
    }

    public function getFeeSell(): ?float
    {
        return $this->fee_sell;
    }

    public function setFeeSell(float $fee_sell): self
    {
        $this->fee_sell = $fee_sell;

        return $this;
    }

    public function getSubtotal(): ?float
    {
        return $this->subtotal;
    }

    public function setSubtotal(float $subtotal): self
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    public function getProfitLoss(): ?float
    {
        return $this->profit_loss;
    }

    public function setProfitLoss(float $profit_loss): self
    {
        $this->profit_loss = $profit_loss;

        return $this;
    }
}
