<?php

namespace App\Plugin\GarageManagementBundle\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Plugin\GarageManagementBundle\Entity\GarageCostItem;
use App\Plugin\GarageManagementBundle\Entity\GarageCostAllocation;
use App\Plugin\GarageManagementBundle\Entity\GarageProjectCogs;
use Doctrine\ORM\EntityManagerInterface;

class GarageReportService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Tính Lợi nhuận Gộp cho một Project (Đơn hàng)
     * 
     * Lợi nhuận Gộp = Doanh thu - Chi phí COGS
     * Chi phí COGS = Chi phí Nhân công + Chi phí Vật tư
     */
    public function calculateGrossPnlForProject(Project $project): array
    {
        // 1. Tính Doanh thu từ Timesheet
        $revenue = $this->calculateProjectRevenue($project);

        // 2. Tính Chi phí Nhân công từ Timesheet (COGS Labor)
        $laborCost = $this->calculateProjectLaborCost($project);

        // 3. Tính Chi phí Vật tư từ GarageProjectCogs
        $suppliesCost = $this->calculateProjectSuppliesCost($project);

        // 4. Tổng COGS
        $totalCogs = $laborCost + $suppliesCost;

        // 5. Lợi nhuận Gộp
        $grossProfit = $revenue - $totalCogs;

        return [
            'project_id' => $project->getId(),
            'project_name' => $project->getName(),
            'revenue' => $revenue,
            'labor_cost' => $laborCost,
            'supplies_cost' => $suppliesCost,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'gross_margin_percent' => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0,
        ];
    }

    /**
     * Tính Hiệu quả Ròng của một User (Nhân viên)
     * 
     * Lợi nhuận Ròng = Tổng Lợi nhuận Gộp Tạo ra - Chi phí Gián tiếp Phân bổ (Điểm hòa vốn Cá nhân)
     */
    public function calculateNetPnlForUser(User $user, \DateTime $from, \DateTime $to): array
    {
        // 1. Tính tổng Lợi nhuận Gộp mà nhân viên tạo ra
        $totalGrossProfit = $this->calculateUserTotalGrossProfit($user, $from, $to);

        // 2. Tính Chi phí Gián tiếp Phân bổ (Điểm hòa vốn Cá nhân)
        $personalBreakevenCost = $this->calculateUserPersonalBreakevenCost($user, $from, $to);

        // 3. Lợi nhuận Ròng
        $netProfit = $totalGrossProfit - $personalBreakevenCost;

        return [
            'user_id' => $user->getId(),
            'user_name' => $user->getDisplayName(),
            'period_from' => $from->format('Y-m-d'),
            'period_to' => $to->format('Y-m-d'),
            'total_gross_profit_generated' => $totalGrossProfit,
            'personal_breakeven_cost' => $personalBreakevenCost,
            'net_profit' => $netProfit,
            'net_efficiency' => $personalBreakevenCost > 0 ? ($totalGrossProfit / $personalBreakevenCost) * 100 : 0,
        ];
    }

    /**
     * Tính Hiệu quả của một Team
     */
    public function calculatePnlForTeam(Team $team, \DateTime $from, \DateTime $to): array
    {
        $teamMembers = $team->getUsers();
        
        $totalRevenue = 0;
        $totalGrossProfit = 0;
        $totalIndirectCosts = 0;
        $projectsCount = 0;
        $uniqueProjects = [];

        foreach ($teamMembers as $user) {
            // Tính cho từng thành viên
            $userPnl = $this->calculateNetPnlForUser($user, $from, $to);
            $totalGrossProfit += $userPnl['total_gross_profit_generated'];
            $totalIndirectCosts += $userPnl['personal_breakeven_cost'];

            // Tính doanh thu và đếm projects
            $userProjects = $this->getUserProjectsInPeriod($user, $from, $to);
            foreach ($userProjects as $project) {
                if (!in_array($project->getId(), $uniqueProjects)) {
                    $uniqueProjects[] = $project->getId();
                    $totalRevenue += $this->calculateProjectRevenue($project, $from, $to);
                }
            }
        }

        $projectsCount = count($uniqueProjects);
        $totalNetProfit = $totalGrossProfit - $totalIndirectCosts;

        return [
            'team_id' => $team->getId(),
            'team_name' => $team->getName(),
            'period_from' => $from->format('Y-m-d'),
            'period_to' => $to->format('Y-m-d'),
            'total_revenue' => $totalRevenue,
            'total_gross_profit' => $totalGrossProfit,
            'total_indirect_costs' => $totalIndirectCosts,
            'total_net_profit' => $totalNetProfit,
            'total_projects_count' => $projectsCount,
            'members_count' => count($teamMembers),
            'average_net_profit_per_member' => count($teamMembers) > 0 ? $totalNetProfit / count($teamMembers) : 0,
            'average_net_profit_per_project' => $projectsCount > 0 ? $totalNetProfit / $projectsCount : 0,
        ];
    }

    /**
     * Private helper methods
     */

    private function calculateProjectRevenue(Project $project, \DateTime $from = null, \DateTime $to = null): float
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('SUM(t.rate)')
           ->from(Timesheet::class, 't')
           ->where('t.project = :project')
           ->setParameter('project', $project);

        if ($from && $to) {
            $qb->andWhere('t.begin >= :from AND t.begin <= :to')
               ->setParameter('from', $from)
               ->setParameter('to', $to);
        }

        return (float) $qb->getQuery()->getSingleScalarResult() ?: 0.0;
    }

    private function calculateProjectLaborCost(Project $project, \DateTime $from = null, \DateTime $to = null): float
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('SUM(t.internalRate)')
           ->from(Timesheet::class, 't')
           ->where('t.project = :project')
           ->setParameter('project', $project);

        if ($from && $to) {
            $qb->andWhere('t.begin >= :from AND t.begin <= :to')
               ->setParameter('from', $from)
               ->setParameter('to', $to);
        }

        return (float) $qb->getQuery()->getSingleScalarResult() ?: 0.0;
    }

    private function calculateProjectSuppliesCost(Project $project): float
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('SUM(c.amount)')
           ->from(GarageProjectCogs::class, 'c')
           ->where('c.project = :project')
           ->setParameter('project', $project);

        return (float) $qb->getQuery()->getSingleScalarResult() ?: 0.0;
    }

    private function calculateUserTotalGrossProfit(User $user, \DateTime $from, \DateTime $to): float
    {
        $projects = $this->getUserProjectsInPeriod($user, $from, $to);
        $totalGrossProfit = 0;

        foreach ($projects as $project) {
            $projectPnl = $this->calculateGrossPnlForProject($project);
            
            // Tính tỷ lệ đóng góp của user trong project này
            $userContributionRatio = $this->calculateUserContributionRatio($user, $project, $from, $to);
            $totalGrossProfit += $projectPnl['gross_profit'] * $userContributionRatio;
        }

        return $totalGrossProfit;
    }

    private function calculateUserPersonalBreakevenCost(User $user, \DateTime $from, \DateTime $to): float
    {
        $userTeams = $user->getTeams();
        $totalPersonalCost = 0;

        foreach ($userTeams as $team) {
            // Lấy tổng chi phí gián tiếp được phân bổ cho team này trong kỳ
            $teamAllocatedCosts = $this->getTeamAllocatedCosts($team, $from, $to);
            
            // Chia đều cho số thành viên trong team
            $teamMembersCount = count($team->getUsers());
            if ($teamMembersCount > 0) {
                $totalPersonalCost += $teamAllocatedCosts / $teamMembersCount;
            }
        }

        return $totalPersonalCost;
    }

    private function getUserProjectsInPeriod(User $user, \DateTime $from, \DateTime $to): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('DISTINCT p')
           ->from(Project::class, 'p')
           ->join(Timesheet::class, 't', 'WITH', 't.project = p.id')
           ->where('t.user = :user')
           ->andWhere('t.begin >= :from AND t.begin <= :to')
           ->setParameter('user', $user)
           ->setParameter('from', $from)
           ->setParameter('to', $to);

        return $qb->getQuery()->getResult();
    }

    private function calculateUserContributionRatio(User $user, Project $project, \DateTime $from, \DateTime $to): float
    {
        // Tính tổng thời gian của user trong project
        $qb1 = $this->entityManager->createQueryBuilder();
        $qb1->select('SUM(t.duration)')
            ->from(Timesheet::class, 't')
            ->where('t.user = :user AND t.project = :project')
            ->andWhere('t.begin >= :from AND t.begin <= :to')
            ->setParameter('user', $user)
            ->setParameter('project', $project)
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        $userDuration = (int) $qb1->getQuery()->getSingleScalarResult() ?: 0;

        // Tính tổng thời gian của tất cả users trong project
        $qb2 = $this->entityManager->createQueryBuilder();
        $qb2->select('SUM(t.duration)')
            ->from(Timesheet::class, 't')
            ->where('t.project = :project')
            ->andWhere('t.begin >= :from AND t.begin <= :to')
            ->setParameter('project', $project)
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        $totalDuration = (int) $qb2->getQuery()->getSingleScalarResult() ?: 0;

        return $totalDuration > 0 ? ($userDuration / $totalDuration) : 0;
    }

    private function getTeamAllocatedCosts(Team $team, \DateTime $from, \DateTime $to): float
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('SUM(ci.amount * (ca.percentage / 100))')
           ->from(GarageCostItem::class, 'ci')
           ->join('ci.allocations', 'ca')
           ->where('ca.team = :team')
           ->andWhere('ci.entryDate >= :from AND ci.entryDate <= :to')
           ->setParameter('team', $team)
           ->setParameter('from', $from)
           ->setParameter('to', $to);

        // Ưu tiên dữ liệu 'actual', nếu không có thì dùng 'forecast'
        $qb->orderBy('ci.status', 'DESC'); // 'actual' sẽ được ưu tiên trước 'forecast'

        return (float) $qb->getQuery()->getSingleScalarResult() ?: 0.0;
    }

    /**
     * Lấy danh sách tất cả Projects với Lợi nhuận Gộp trong kỳ
     */
    public function getAllProjectsGrossPnl(\DateTime $from, \DateTime $to): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('DISTINCT p')
           ->from(Project::class, 'p')
           ->join(Timesheet::class, 't', 'WITH', 't.project = p.id')
           ->where('t.begin >= :from AND t.begin <= :to')
           ->setParameter('from', $from)
           ->setParameter('to', $to)
           ->orderBy('p.name', 'ASC');

        $projects = $qb->getQuery()->getResult();
        $results = [];

        foreach ($projects as $project) {
            $results[] = $this->calculateGrossPnlForProject($project);
        }

        return $results;
    }

    /**
     * Lấy danh sách tất cả Users với Hiệu quả Ròng trong kỳ
     */
    public function getAllUsersNetPnl(\DateTime $from, \DateTime $to): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('DISTINCT u')
           ->from(User::class, 'u')
           ->join(Timesheet::class, 't', 'WITH', 't.user = u.id')
           ->where('t.begin >= :from AND t.begin <= :to')
           ->andWhere('u.enabled = true')
           ->setParameter('from', $from)
           ->setParameter('to', $to)
           ->orderBy('u.alias', 'ASC');

        $users = $qb->getQuery()->getResult();
        $results = [];

        foreach ($users as $user) {
            $results[] = $this->calculateNetPnlForUser($user, $from, $to);
        }

        return $results;
    }

    /**
     * Lấy danh sách tất cả Teams với Hiệu quả trong kỳ
     */
    public function getAllTeamsPnl(\DateTime $from, \DateTime $to): array
    {
        $teams = $this->entityManager->getRepository(Team::class)->findAll();
        $results = [];

        foreach ($teams as $team) {
            $results[] = $this->calculatePnlForTeam($team, $from, $to);
        }

        return $results;
    }
}
