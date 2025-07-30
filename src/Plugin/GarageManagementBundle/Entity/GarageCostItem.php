<?php

namespace App\Plugin\GarageManagementBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'garage_cost_item')]
#[ORM\Entity]
class GarageCostItem
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: false)]
    private ?string $name = null;

    #[ORM\Column(name: 'amount', type: Types::DECIMAL, precision: 15, scale: 2, nullable: false)]
    private ?string $amount = null;

    #[ORM\Column(name: 'category', type: Types::STRING, length: 50, nullable: false)]
    private ?string $category = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 20, nullable: false, options: ['default' => 'forecast'])]
    private string $status = 'forecast';

    #[ORM\Column(name: 'entry_date', type: Types::DATE_MUTABLE, nullable: false)]
    private ?\DateTime $entryDate = null;

    /**
     * Cost allocations to teams
     *
     * @var Collection<GarageCostAllocation>
     */
    #[ORM\OneToMany(mappedBy: 'costItem', targetEntity: GarageCostAllocation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $allocations;

    public function __construct()
    {
        $this->allocations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getEntryDate(): ?\DateTime
    {
        return $this->entryDate;
    }

    public function setEntryDate(?\DateTime $entryDate): self
    {
        $this->entryDate = $entryDate;
        return $this;
    }

    /**
     * @return Collection<GarageCostAllocation>
     */
    public function getAllocations(): Collection
    {
        return $this->allocations;
    }

    public function addAllocation(GarageCostAllocation $allocation): self
    {
        if (!$this->allocations->contains($allocation)) {
            $this->allocations->add($allocation);
            $allocation->setCostItem($this);
        }

        return $this;
    }

    public function removeAllocation(GarageCostAllocation $allocation): self
    {
        if ($this->allocations->removeElement($allocation)) {
            if ($allocation->getCostItem() === $this) {
                $allocation->setCostItem(null);
            }
        }

        return $this;
    }

    /**
     * Get available cost categories
     */
    public static function getAvailableCategories(): array
    {
        return [
            'OPEX_SELLING' => 'Chi phí Bán hàng',
            'OPEX_GA' => 'Chi phí Quản lý Doanh nghiệp',
            'FINANCIAL' => 'Chi phí Tài chính',
            'TAX' => 'Thuế Thu nhập Doanh nghiệp'
        ];
    }

    /**
     * Get available statuses
     */
    public static function getAvailableStatuses(): array
    {
        return [
            'forecast' => 'Dự toán',
            'actual' => 'Thực tế'
        ];
    }
}
