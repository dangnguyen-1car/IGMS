<?php

namespace App\Plugin\GarageManagementBundle\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use App\Utils\MenuItemModel;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GarageMenuSubscriber implements EventSubscriberInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMainMenuEvent::class => ['onMainMenuConfigure', 50],
        ];
    }

    public function onMainMenuConfigure(ConfigureMainMenuEvent $event): void
    {
        $auth = $this->security;

        if (!$auth->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return;
        }

        // Add to admin menu
        $menu = $event->getAdminMenu();

        // Add garage management section
        if ($auth->isGranted('ROLE_ADMIN') || $auth->isGranted('ROLE_SUPER_ADMIN')) {
            $garageMenu = new MenuItemModel('garage_management', 'Quản lý Garage', null, [], 'fas fa-car');
            
            // Cost management
            $costMenu = new MenuItemModel('garage_costs', 'Quản lý Chi phí', 'garage_costs_index', [], 'fas fa-money-bill-wave');
            $costMenu->setChildRoutes(['garage_costs_new', 'garage_costs_edit']);
            $garageMenu->addChild($costMenu);

            // Reports menu
            $reportsMenu = new MenuItemModel('garage_reports', 'Báo cáo P&L', 'garage_reports_dashboard', [], 'fas fa-chart-line');
            $reportsMenu->setChildRoutes(['garage_reports_projects', 'garage_reports_users', 'garage_reports_teams']);
            $garageMenu->addChild($reportsMenu);

            $menu->addChild($garageMenu);
        }
    }
}
