<?php

namespace App\Plugin\GarageManagementBundle\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Plugin\GarageManagementBundle\Entity\GarageProjectWorkflow;
use App\Plugin\GarageManagementBundle\Entity\GarageProjectCogs;
use App\Plugin\GarageManagementBundle\Service\GarageWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProjectGarageController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private GarageWorkflowService $workflowService;

    public function __construct(EntityManagerInterface $entityManager, GarageWorkflowService $workflowService)
    {
        $this->entityManager = $entityManager;
        $this->workflowService = $workflowService;
    }

    /**
     * Tab "Tiến độ Dịch vụ" - Shows workflow stages
     */
    public function workflowAction(Project $project): Response
    {
        $workflows = $this->workflowService->getWorkflowForProject($project);
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $completionPercentage = $this->workflowService->getProjectCompletionPercentage($project);

        return $this->render('bundles/GarageManagementBundle/project/workflow.html.twig', [
            'project' => $project,
            'workflows' => $workflows,
            'users' => $users,
            'statuses' => $this->workflowService->getAvailableStatuses(),
            'completion_percentage' => $completionPercentage,
        ]);
    }

    /**
     * Tab "Chi phí Vật tư (COGS)" - Shows project COGS
     */
    public function cogsAction(Project $project): Response
    {
        $cogsItems = $this->entityManager->getRepository(GarageProjectCogs::class)
            ->findBy(['project' => $project], ['id' => 'ASC']);

        $totalCogs = 0;
        foreach ($cogsItems as $item) {
            $totalCogs += (float) $item->getAmount();
        }

        return $this->render('bundles/GarageManagementBundle/project/cogs.html.twig', [
            'project' => $project,
            'cogs_items' => $cogsItems,
            'total_cogs' => $totalCogs,
            'cogs_types' => GarageProjectCogs::getAvailableCogsTypes(),
        ]);
    }

    /**
     * AJAX API to update workflow stage
     */
    #[Route('/ajax/garage/workflow/{id}/update', name: 'garage_workflow_update', methods: ['POST'])]
    #[IsGranted('edit', 'project')]
    public function updateWorkflow(Request $request, int $id): JsonResponse
    {
        $workflow = $this->entityManager->getRepository(GarageProjectWorkflow::class)->find($id);
        
        if (!$workflow) {
            return new JsonResponse(['success' => false, 'message' => 'Workflow not found'], 404);
        }

        // Check if user has permission to edit this project
        if (!$this->isGranted('edit', $workflow->getProject())) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }

        try {
            $status = $request->request->get('status');
            $startTime = $request->request->get('start_time');
            $endTime = $request->request->get('end_time');
            $responsibleUserId = $request->request->get('responsible_user_id');
            $notes = $request->request->get('notes');

            $startTimeObj = $startTime ? new \DateTime($startTime) : null;
            $endTimeObj = $endTime ? new \DateTime($endTime) : null;

            $updatedWorkflow = $this->workflowService->updateWorkflowStage(
                $id,
                $status,
                $startTimeObj,
                $endTimeObj,
                $responsibleUserId ? (int) $responsibleUserId : null,
                $notes
            );

            if (!$updatedWorkflow) {
                return new JsonResponse(['success' => false, 'message' => 'Failed to update workflow'], 500);
            }

            // Get updated completion percentage
            $completionPercentage = $this->workflowService->getProjectCompletionPercentage($workflow->getProject());

            return new JsonResponse([
                'success' => true,
                'message' => 'Workflow updated successfully',
                'completion_percentage' => $completionPercentage,
                'workflow' => [
                    'id' => $updatedWorkflow->getId(),
                    'status' => $updatedWorkflow->getStatus(),
                    'start_time' => $updatedWorkflow->getStartTime() ? $updatedWorkflow->getStartTime()->format('Y-m-d\TH:i') : null,
                    'end_time' => $updatedWorkflow->getEndTime() ? $updatedWorkflow->getEndTime()->format('Y-m-d\TH:i') : null,
                    'responsible_user_id' => $updatedWorkflow->getResponsibleUser() ? $updatedWorkflow->getResponsibleUser()->getId() : null,
                    'notes' => $updatedWorkflow->getNotes(),
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * AJAX API to add COGS item
     */
    #[Route('/ajax/garage/cogs/add', name: 'garage_cogs_add', methods: ['POST'])]
    public function addCogs(Request $request): JsonResponse
    {
        $projectId = $request->request->get('project_id');
        $project = $this->entityManager->getRepository(Project::class)->find($projectId);

        if (!$project || !$this->isGranted('edit', $project)) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }

        try {
            $cogsType = $request->request->get('cogs_type');
            $description = $request->request->get('description');
            $amount = $request->request->get('amount');

            if (!$cogsType || !$description || !$amount) {
                return new JsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
            }

            $cogs = new GarageProjectCogs();
            $cogs->setProject($project);
            $cogs->setCogsType($cogsType);
            $cogs->setDescription($description);
            $cogs->setAmount($amount);

            $this->entityManager->persist($cogs);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'COGS item added successfully',
                'cogs' => [
                    'id' => $cogs->getId(),
                    'cogs_type' => $cogs->getCogsType(),
                    'description' => $cogs->getDescription(),
                    'amount' => $cogs->getAmount(),
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * AJAX API to update COGS item
     */
    #[Route('/ajax/garage/cogs/{id}/update', name: 'garage_cogs_update', methods: ['POST'])]
    public function updateCogs(Request $request, int $id): JsonResponse
    {
        $cogs = $this->entityManager->getRepository(GarageProjectCogs::class)->find($id);

        if (!$cogs || !$this->isGranted('edit', $cogs->getProject())) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }

        try {
            $cogsType = $request->request->get('cogs_type');
            $description = $request->request->get('description');
            $amount = $request->request->get('amount');

            if ($cogsType) $cogs->setCogsType($cogsType);
            if ($description) $cogs->setDescription($description);
            if ($amount) $cogs->setAmount($amount);

            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'COGS item updated successfully',
                'cogs' => [
                    'id' => $cogs->getId(),
                    'cogs_type' => $cogs->getCogsType(),
                    'description' => $cogs->getDescription(),
                    'amount' => $cogs->getAmount(),
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * AJAX API to delete COGS item
     */
    #[Route('/ajax/garage/cogs/{id}/delete', name: 'garage_cogs_delete', methods: ['DELETE'])]
    public function deleteCogs(int $id): JsonResponse
    {
        $cogs = $this->entityManager->getRepository(GarageProjectCogs::class)->find($id);

        if (!$cogs || !$this->isGranted('edit', $cogs->getProject())) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }

        try {
            $this->entityManager->remove($cogs);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'COGS item deleted successfully'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
