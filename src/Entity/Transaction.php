<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TransactionRepository")
 */
class Transaction
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
}
