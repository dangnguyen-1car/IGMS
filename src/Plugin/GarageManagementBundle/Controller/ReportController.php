<?php

namespace App\Plugin\GarageManagementBundle\Controller;

use App\Plugin\GarageManagementBundle\Service\GarageReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/garage/reports')]
#[IsGranted('view_reporting')]
class ReportController extends AbstractController
{
    private GarageReportService $reportService;

    public function __construct(GarageReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Báo cáo Lợi nhuận Gộp theo Đơn hàng (Project)
     */
    #[Route('/projects', name: 'garage_reports_projects', methods: ['GET'])]
    public function projectsGrossPnl(Request $request): Response
    {
        // Lấy tham số thời gian từ request, mặc định là tháng hiện tại
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        if (!$from || !$to) {
            $from = new \DateTime('first day of this month');
            $to = new \DateTime('last day of this month');
        } else {
            $from = new \DateTime($from);
            $to = new \DateTime($to);
        }

        // Đảm bảo kết thúc ngày là 23:59:59
        $to->setTime(23, 59, 59);

        $projectsData = $this->reportService->getAllProjectsGrossPnl($from, $to);

        // Tính tổng
        $totalRevenue = array_sum(array_column($projectsData, 'revenue'));
        $totalCogs = array_sum(array_column($projectsData, 'total_cogs'));
        $totalGrossProfit = array_sum(array_column($projectsData, 'gross_profit'));
        $overallGrossMargin = $totalRevenue > 0 ? ($totalGrossProfit / $totalRevenue) * 100 : 0;

        return $this->render('bundles/GarageManagementBundle/reports/projects.html.twig', [
            'projects_data' => $projectsData,
            'period_from' => $from,
            'period_to' => $to,
            'total_revenue' => $totalRevenue,
            'total_cogs' => $totalCogs,
            'total_gross_profit' => $totalGrossProfit,
            'overall_gross_margin' => $overallGrossMargin,
        ]);
    }

    /**
     * Báo cáo Hiệu quả Nhân viên
     */
    #[Route('/users', name: 'garage_reports_users', methods: ['GET'])]
    public function usersNetPnl(Request $request): Response
    {
        // Lấy tham số thời gian từ request, mặc định là tháng hiện tại
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        if (!$from || !$to) {
            $from = new \DateTime('first day of this month');
            $to = new \DateTime('last day of this month');
        } else {
            $from = new \DateTime($from);
            $to = new \DateTime($to);
        }

        // Đảm bảo kết thúc ngày là 23:59:59
        $to->setTime(23, 59, 59);

        $usersData = $this->reportService->getAllUsersNetPnl($from, $to);

        // Tính tổng
        $totalGrossProfitGenerated = array_sum(array_column($usersData, 'total_gross_profit_generated'));
        $totalPersonalBreakevenCost = array_sum(array_column($usersData, 'personal_breakeven_cost'));
        $totalNetProfit = array_sum(array_column($usersData, 'net_profit'));
        $overallEfficiency = $totalPersonalBreakevenCost > 0 ? ($totalGrossProfitGenerated / $totalPersonalBreakevenCost) * 100 : 0;

        // Sắp xếp theo lợi nhuận ròng giảm dần
        usort($usersData, function($a, $b) {
            return $b['net_profit'] <=> $a['net_profit'];
        });

        return $this->render('bundles/GarageManagementBundle/reports/users.html.twig', [
            'users_data' => $usersData,
            'period_from' => $from,
            'period_to' => $to,
            'total_gross_profit_generated' => $totalGrossProfitGenerated,
            'total_personal_breakeven_cost' => $totalPersonalBreakevenCost,
            'total_net_profit' => $totalNetProfit,
            'overall_efficiency' => $overallEfficiency,
        ]);
    }

    /**
     * Báo cáo Hiệu quả Team
     */
    #[Route('/teams', name: 'garage_reports_teams', methods: ['GET'])]
    public function teamsPnl(Request $request): Response
    {
        // Lấy tham số thời gian từ request, mặc định là tháng hiện tại
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        if (!$from || !$to) {
            $from = new \DateTime('first day of this month');
            $to = new \DateTime('last day of this month');
        } else {
            $from = new \DateTime($from);
            $to = new \DateTime($to);
        }

        // Đảm bảo kết thúc ngày là 23:59:59
        $to->setTime(23, 59, 59);

        $teamsData = $this->reportService->getAllTeamsPnl($from, $to);

        // Tính tổng
        $totalRevenue = array_sum(array_column($teamsData, 'total_revenue'));
        $totalGrossProfit = array_sum(array_column($teamsData, 'total_gross_profit'));
        $totalIndirectCosts = array_sum(array_column($teamsData, 'total_indirect_costs'));
        $totalNetProfit = array_sum(array_column($teamsData, 'total_net_profit'));
        $totalProjectsCount = array_sum(array_column($teamsData, 'total_projects_count'));

        // Sắp xếp theo lợi nhuận ròng giảm dần
        usort($teamsData, function($a, $b) {
            return $b['total_net_profit'] <=> $a['total_net_profit'];
        });

        return $this->render('bundles/GarageManagementBundle/reports/teams.html.twig', [
            'teams_data' => $teamsData,
            'period_from' => $from,
            'period_to' => $to,
            'total_revenue' => $totalRevenue,
            'total_gross_profit' => $totalGrossProfit,
            'total_indirect_costs' => $totalIndirectCosts,
            'total_net_profit' => $totalNetProfit,
            'total_projects_count' => $totalProjectsCount,
        ]);
    }

    /**
     * Dashboard tổng quan P&L
     */
    #[Route('/', name: 'garage_reports_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): Response
    {
        // Lấy tham số thời gian từ request, mặc định là tháng hiện tại
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        if (!$from || !$to) {
            $from = new \DateTime('first day of this month');
            $to = new \DateTime('last day of this month');
        } else {
            $from = new \DateTime($from);
            $to = new \DateTime($to);
        }

        // Đảm bảo kết thúc ngày là 23:59:59
        $to->setTime(23, 59, 59);

        // Lấy dữ liệu tổng quan
        $projectsData = $this->reportService->getAllProjectsGrossPnl($from, $to);
        $usersData = $this->reportService->getAllUsersNetPnl($from, $to);
        $teamsData = $this->reportService->getAllTeamsPnl($from, $to);

        // Tính tổng projects
        $totalRevenue = array_sum(array_column($projectsData, 'revenue'));
        $totalCogs = array_sum(array_column($projectsData, 'total_cogs'));
        $totalGrossProfit = array_sum(array_column($projectsData, 'gross_profit'));
        $overallGrossMargin = $totalRevenue > 0 ? ($totalGrossProfit / $totalRevenue) * 100 : 0;

        // Tính tổng users
        $totalPersonalBreakevenCost = array_sum(array_column($usersData, 'personal_breakeven_cost'));
        $totalNetProfit = array_sum(array_column($usersData, 'net_profit'));
        $overallEfficiency = $totalPersonalBreakevenCost > 0 ? ($totalGrossProfit / $totalPersonalBreakevenCost) * 100 : 0;

        // Top performers
        usort($usersData, function($a, $b) {
            return $b['net_profit'] <=> $a['net_profit'];
        });
        $topUsers = array_slice($usersData, 0, 5);

        usort($teamsData, function($a, $b) {
            return $b['total_net_profit'] <=> $a['total_net_profit'];
        });
        $topTeams = array_slice($teamsData, 0, 5);

        usort($projectsData, function($a, $b) {
            return $b['gross_profit'] <=> $a['gross_profit'];
        });
        $topProjects = array_slice($projectsData, 0, 5);

        return $this->render('bundles/GarageManagementBundle/reports/dashboard.html.twig', [
            'period_from' => $from,
            'period_to' => $to,
            'total_revenue' => $totalRevenue,
            'total_cogs' => $totalCogs,
            'total_gross_profit' => $totalGrossProfit,
            'overall_gross_margin' => $overallGrossMargin,
            'total_personal_breakeven_cost' => $totalPersonalBreakevenCost,
            'total_net_profit' => $totalNetProfit,
            'overall_efficiency' => $overallEfficiency,
            'projects_count' => count($projectsData),
            'users_count' => count($usersData),
            'teams_count' => count($teamsData),
            'top_users' => $topUsers,
            'top_teams' => $topTeams,
            'top_projects' => $topProjects,
        ]);
    }
}
