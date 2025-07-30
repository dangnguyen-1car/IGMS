<?php

namespace App\Plugin\GarageManagementBundle\Entity;

use App\Entity\Team;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'garage_cost_allocation')]
#[ORM\Entity]
class GarageCostAllocation
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GarageCostItem::class, inversedBy: 'allocations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?GarageCostItem $costItem = null;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Team $team = null;

    #[ORM\Column(name: 'percentage', type: Types::DECIMAL, precision: 5, scale: 2, nullable: false)]
    private ?string $percentage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCostItem(): ?GarageCostItem
    {
        return $this->costItem;
    }

    public function setCostItem(?GarageCostItem $costItem): self
    {
        $this->costItem = $costItem;
        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;
        return $this;
    }

    public function getPercentage(): ?string
    {
        return $this->percentage;
    }

    public function setPercentage(?string $percentage): self
    {
        $this->percentage = $percentage;
        return $this;
    }

    /**
     * Get the allocated amount for this team
     */
    public function getAllocatedAmount(): float
    {
        if (!$this->costItem || !$this->percentage) {
            return 0.0;
        }

        $totalAmount = (float) $this->costItem->getAmount();
        $percentage = (float) $this->percentage;

        return $totalAmount * ($percentage / 100);
    }
}
