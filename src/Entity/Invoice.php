<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['order:read'])]
    private ?string $invoiceNumber = null;

    // Montants exposés en number via les getters *AsNumber (le front attend des number)
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amountHt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $vatRate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $vatAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amountTtc = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = null;

    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $issuedAt = null;

    #[ORM\OneToOne(inversedBy: 'invoice', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $customerOrder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    public function getAmountHt(): ?string
    {
        return $this->amountHt;
    }

    public function setAmountHt(string $amountHt): static
    {
        $this->amountHt = $amountHt;

        return $this;
    }

    public function getVatRate(): ?string
    {
        return $this->vatRate;
    }

    public function setVatRate(string $vatRate): static
    {
        $this->vatRate = $vatRate;

        return $this;
    }

    public function getVatAmount(): ?string
    {
        return $this->vatAmount;
    }

    public function setVatAmount(string $vatAmount): static
    {
        $this->vatAmount = $vatAmount;

        return $this;
    }

    public function getAmountTtc(): ?string
    {
        return $this->amountTtc;
    }

    public function setAmountTtc(string $amountTtc): static
    {
        $this->amountTtc = $amountTtc;

        return $this;
    }

    #[Groups(['order:read'])]
    #[SerializedName('amountHt')]
    public function getAmountHtAsNumber(): ?float
    {
        return $this->amountHt !== null ? (float) $this->amountHt : null;
    }

    #[Groups(['order:read'])]
    #[SerializedName('vatRate')]
    public function getVatRateAsNumber(): ?float
    {
        return $this->vatRate !== null ? (float) $this->vatRate : null;
    }

    #[Groups(['order:read'])]
    #[SerializedName('vatAmount')]
    public function getVatAmountAsNumber(): ?float
    {
        return $this->vatAmount !== null ? (float) $this->vatAmount : null;
    }

    #[Groups(['order:read'])]
    #[SerializedName('amountTtc')]
    public function getAmountTtcAsNumber(): ?float
    {
        return $this->amountTtc !== null ? (float) $this->amountTtc : null;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getIssuedAt(): ?\DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(\DateTimeImmutable $issuedAt): static
    {
        $this->issuedAt = $issuedAt;

        return $this;
    }

    public function getCustomerOrder(): ?Order
    {
        return $this->customerOrder;
    }

    public function setCustomerOrder(Order $customerOrder): static
    {
        $this->customerOrder = $customerOrder;

        return $this;
    }
}
