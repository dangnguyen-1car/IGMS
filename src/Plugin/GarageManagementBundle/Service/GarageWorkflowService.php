<?php

namespace App\Plugin\GarageManagementBundle\Service;

use App\Entity\Project;
use App\Plugin\GarageManagementBundle\Entity\GarageProjectWorkflow;
use Doctrine\ORM\EntityManagerInterface;

class GarageWorkflowService
{
    private EntityManagerInterface $entityManager;

    /**
     * Stage definitions from Project Brief Appendix A.1
     */
    private const STAGE_DEFINITIONS = [
        'reception' => 'Tiếp nhận',
        'diagnosis' => 'Chẩn đoán',
        'estimate' => 'Dự toán và xác nhận',
        'parts_prep' => 'Chuẩn bị phụ tùng',
        'repair_maintenance' => 'Sửa chữa/Bảo dưỡng',
        'painting' => 'Sơn đồng (nếu có)',
        'detailing' => 'Detailing (nếu có)',
        'quality_check' => 'Kiểm tra chất lượng',
        'cleaning' => 'Vệ sinh xe',
        'handover' => 'Bàn giao'
    ];

    /**
     * Available workflow statuses
     */
    private const AVAILABLE_STATUSES = [
        'Chưa bắt đầu',
        'Đang thực hiện',
        'Hoàn thành',
        'Tạm dừng'
    ];

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Initialize workflow stages for a new project
     */
    public function initializeWorkflowForProject(Project $project): void
    {
        // Check if workflow already exists for this project
        $existingWorkflow = $this->entityManager->getRepository(GarageProjectWorkflow::class)
            ->findBy(['project' => $project]);

        if (!empty($existingWorkflow)) {
            return; // Workflow already exists
        }

        // Create workflow stages
        foreach (self::STAGE_DEFINITIONS as $stageKey => $stageName) {
            $workflow = new GarageProjectWorkflow();
            $workflow->setProject($project);
            $workflow->setStageKey($stageKey);
            $workflow->setStageName($stageName);
            $workflow->setStatus('Chưa bắt đầu');

            $this->entityManager->persist($workflow);
        }

        $this->entityManager->flush();
    }

    /**
     * Get workflow stages for a project
     */
    public function getWorkflowForProject(Project $project): array
    {
        return $this->entityManager->getRepository(GarageProjectWorkflow::class)
            ->findBy(['project' => $project], ['id' => 'ASC']);
    }

    /**
     * Update workflow stage
     */
    public function updateWorkflowStage(
        int $workflowId,
        string $status,
        ?\DateTime $startTime = null,
        ?\DateTime $endTime = null,
        ?int $responsibleUserId = null,
        ?string $notes = null
    ): ?GarageProjectWorkflow {
        $workflow = $this->entityManager->getRepository(GarageProjectWorkflow::class)
            ->find($workflowId);

        if (!$workflow) {
            return null;
        }

        $workflow->setStatus($status);
        
        if ($startTime !== null) {
            $workflow->setStartTime($startTime);
        }
        
        if ($endTime !== null) {
            $workflow->setEndTime($endTime);
        }

        if ($responsibleUserId !== null) {
            $user = $this->entityManager->getRepository(\App\Entity\User::class)
                ->find($responsibleUserId);
            $workflow->setResponsibleUser($user);
        }

        if ($notes !== null) {
            $workflow->setNotes($notes);
        }

        $this->entityManager->flush();

        return $workflow;
    }

    /**
     * Get available workflow statuses
     */
    public function getAvailableStatuses(): array
    {
        return self::AVAILABLE_STATUSES;
    }

    /**
     * Get stage definitions
     */
    public function getStageDefinitions(): array
    {
        return self::STAGE_DEFINITIONS;
    }

    /**
     * Get project completion percentage
     */
    public function getProjectCompletionPercentage(Project $project): float
    {
        $workflows = $this->getWorkflowForProject($project);
        
        if (empty($workflows)) {
            return 0.0;
        }

        $completedCount = 0;
        foreach ($workflows as $workflow) {
            if ($workflow->getStatus() === 'Hoàn thành') {
                $completedCount++;
            }
        }

        return (float) ($completedCount / count($workflows)) * 100;
    }
}
