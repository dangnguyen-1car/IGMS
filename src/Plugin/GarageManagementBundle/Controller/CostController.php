<?php

namespace App\Plugin\GarageManagementBundle\Controller;

use App\Plugin\GarageManagementBundle\Entity\GarageCostItem;
use App\Plugin\GarageManagementBundle\Entity\GarageCostAllocation;
use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/admin/garage/costs', name: 'garage_costs_')]
class CostController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $costItems = $this->entityManager->getRepository(GarageCostItem::class)
            ->findBy([], ['entryDate' => 'DESC']);

        return $this->render('bundles/GarageManagementBundle/cost/index.html.twig', [
            'costItems' => $costItems,
            'categories' => GarageCostItem::getAvailableCategories(),
            'statuses' => GarageCostItem::getAvailableStatuses(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $costItem = new GarageCostItem();
        
        if ($request->isMethod('POST')) {
            return $this->handleCostItemForm($request, $costItem);
        }

        $teams = $this->entityManager->getRepository(Team::class)->findAll();

        return $this->render('bundles/GarageManagementBundle/cost/form.html.twig', [
            'costItem' => $costItem,
            'teams' => $teams,
            'categories' => GarageCostItem::getAvailableCategories(),
            'statuses' => GarageCostItem::getAvailableStatuses(),
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $costItem = $this->entityManager->getRepository(GarageCostItem::class)
            ->find($id);

        if (!$costItem) {
            throw $this->createNotFoundException('Cost item not found');
        }

        if ($request->isMethod('POST')) {
            return $this->handleCostItemForm($request, $costItem);
        }

        $teams = $this->entityManager->getRepository(Team::class)->findAll();

        // Prepare allocation data for the form
        $allocations = [];
        foreach ($costItem->getAllocations() as $allocation) {
            $allocations[$allocation->getTeam()->getId()] = $allocation->getPercentage();
        }

        return $this->render('bundles/GarageManagementBundle/cost/form.html.twig', [
            'costItem' => $costItem,
            'teams' => $teams,
            'categories' => GarageCostItem::getAvailableCategories(),
            'statuses' => GarageCostItem::getAvailableStatuses(),
            'allocations' => $allocations,
            'isEdit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $costItem = $this->entityManager->getRepository(GarageCostItem::class)
            ->find($id);

        if (!$costItem) {
            throw $this->createNotFoundException('Cost item not found');
        }

        // Check CSRF token
        if (!$this->isCsrfTokenValid('delete' . $costItem->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('garage_costs_index');
        }

        $this->entityManager->remove($costItem);
        $this->entityManager->flush();

        $this->addFlash('success', 'Chi phí đã được xóa thành công');

        return $this->redirectToRoute('garage_costs_index');
    }

    private function handleCostItemForm(Request $request, GarageCostItem $costItem): Response
    {
        // Get form data
        $name = $request->request->get('name');
        $amount = $request->request->get('amount');
        $category = $request->request->get('category');
        $status = $request->request->get('status');
        $entryDate = $request->request->get('entry_date');
        $allocations = $request->request->all('allocations'); // Array of team_id => percentage

        // Validate required fields
        if (!$name || !$amount || !$category || !$status || !$entryDate) {
            $this->addFlash('error', 'Vui lòng điền đầy đủ thông tin bắt buộc');
            return $this->redirectToRoute('garage_costs_new');
        }

        // Validate allocations sum to 100%
        $totalPercentage = 0;
        if ($allocations) {
            foreach ($allocations as $teamId => $percentage) {
                if (!empty($percentage)) {
                    $totalPercentage += (float) $percentage;
                }
            }
        }

        if ($totalPercentage > 0 && abs($totalPercentage - 100) > 0.01) {
            $this->addFlash('error', 'Tổng tỷ lệ phân bổ phải bằng 100%');
            return $this->redirectToRoute($costItem->getId() ? 'garage_costs_edit' : 'garage_costs_new', 
                $costItem->getId() ? ['id' => $costItem->getId()] : []);
        }

        try {
            // Set cost item data
            $costItem->setName($name);
            $costItem->setAmount($amount);
            $costItem->setCategory($category);
            $costItem->setStatus($status);
            $costItem->setEntryDate(new \DateTime($entryDate));

            // Remove existing allocations for edit
            if ($costItem->getId()) {
                foreach ($costItem->getAllocations() as $allocation) {
                    $this->entityManager->remove($allocation);
                }
                $costItem->getAllocations()->clear();
            }

            // Add new allocations
            if ($allocations) {
                foreach ($allocations as $teamId => $percentage) {
                    if (!empty($percentage)) {
                        $team = $this->entityManager->getRepository(Team::class)->find($teamId);
                        if ($team) {
                            $allocation = new GarageCostAllocation();
                            $allocation->setCostItem($costItem);
                            $allocation->setTeam($team);
                            $allocation->setPercentage($percentage);
                            
                            $costItem->addAllocation($allocation);
                            $this->entityManager->persist($allocation);
                        }
                    }
                }
            }

            $this->entityManager->persist($costItem);
            $this->entityManager->flush();

            $this->addFlash('success', $costItem->getId() ? 'Chi phí đã được cập nhật thành công' : 'Chi phí đã được tạo thành công');

            return $this->redirectToRoute('garage_costs_index');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Có lỗi xảy ra: ' . $e->getMessage());
            return $this->redirectToRoute($costItem->getId() ? 'garage_costs_edit' : 'garage_costs_new', 
                $costItem->getId() ? ['id' => $costItem->getId()] : []);
        }
    }
}
