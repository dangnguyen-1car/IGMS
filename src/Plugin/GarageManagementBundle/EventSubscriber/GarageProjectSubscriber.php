<?php

namespace App\Plugin\GarageManagementBundle\EventSubscriber;

use App\Event\ProjectDetailControllerEvent;
use App\Plugin\GarageManagementBundle\Service\GarageWorkflowService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GarageProjectSubscriber implements EventSubscriberInterface
{
    private GarageWorkflowService $workflowService;

    public function __construct(GarageWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProjectDetailControllerEvent::class => ['onProjectDetailController', 100],
        ];
    }

    public function onProjectDetailController(ProjectDetailControllerEvent $event): void
    {
        // Initialize workflow for project if not exists
        $project = $event->getProject();
        $this->workflowService->initializeWorkflowForProject($project);

        // Add garage management tabs to project details
        $event->addController('App\\Plugin\\GarageManagementBundle\\Controller\\ProjectGarageController::workflowAction');
        $event->addController('App\\Plugin\\GarageManagementBundle\\Controller\\ProjectGarageController::cogsAction');
    }
}
