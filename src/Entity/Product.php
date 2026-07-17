<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Groups(['product:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 150)]
    #[Groups(['product:read'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $shortDescription = null;

    // Prix exposés via les getters *AsNumber (le front attend des number,
    // Doctrine DECIMAL est un string PHP) — pas de groupe sur les propriétés
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $priceMonthly = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $priceYearly = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $imageUrl = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['product:read'])]
    private ?array $features = null;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $displayPriority = null;

    #[ORM\Column]
    #[Groups(['product:read'])]
    #[SerializedName('active')]
    private ?bool $isActive = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[Groups(['product:read'])]
    private ?Category $category = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'product')]
    private Collection $orderItems;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getPriceMonthly(): ?string
    {
        return $this->priceMonthly;
    }

    public function setPriceMonthly(string $priceMonthly): static
    {
        $this->priceMonthly = $priceMonthly;

        return $this;
    }

    public function getPriceYearly(): ?string
    {
        return $this->priceYearly;
    }

    #[Groups(['product:read'])]
    #[SerializedName('priceMonthly')]
    public function getPriceMonthlyAsNumber(): ?float
    {
        return $this->priceMonthly !== null ? (float) $this->priceMonthly : null;
    }

    #[Groups(['product:read'])]
    #[SerializedName('priceYearly')]
    public function getPriceYearlyAsNumber(): ?float
    {
        return $this->priceYearly !== null ? (float) $this->priceYearly : null;
    }

    public function setPriceYearly(string $priceYearly): static
    {
        $this->priceYearly = $priceYearly;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getFeatures(): ?array
    {
        return $this->features;
    }

    public function setFeatures(?array $features): static
    {
        $this->features = $features;

        return $this;
    }

    public function getDisplayPriority(): ?int
    {
        return $this->displayPriority;
    }

    public function setDisplayPriority(int $displayPriority): static
    {
        $this->displayPriority = $displayPriority;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setProduct($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getProduct() === $this) {
                $orderItem->setProduct(null);
            }
        }

        return $this;
    }
}
