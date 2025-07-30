<?php

namespace App\Plugin\GarageManagementBundle\Entity;

use App\Entity\Project;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'garage_project_cogs')]
#[ORM\Entity]
class GarageProjectCogs
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\Column(name: 'cogs_type', type: Types::STRING, length: 50, nullable: false)]
    private ?string $cogsType = null;

    #[ORM\Column(name: 'description', type: Types::STRING, length: 255, nullable: false)]
    private ?string $description = null;

    #[ORM\Column(name: 'amount', type: Types::DECIMAL, precision: 15, scale: 2, nullable: false)]
    private ?string $amount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function getCogsType(): ?string
    {
        return $this->cogsType;
    }

    public function setCogsType(?string $cogsType): self
    {
        $this->cogsType = $cogsType;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
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

    /**
     * Get available COGS types
     */
    public static function getAvailableCogsTypes(): array
    {
        return [
            'SUPPLIES' => 'Chi phí Vật tư & Phụ tùng'
        ];
    }
}
