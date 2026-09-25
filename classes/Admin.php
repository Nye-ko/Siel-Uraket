<?php

final class Admin
{
    public function __construct(private mysqli $db)
    {
    }

    public function stats(): array
    {
        return [
            'totalUsers' => $this->count('users'),
            'totalFreelancers' => $this->count('users', 'role = "freelancer"'),
            'totalClients' => $this->count('users', 'role = "client"'),
            'totalJobPosts' => $this->count('jobs'),
            'openJobPosts' => $this->count('jobs', 'status = "open"'),
            'closedJobPosts' => $this->count('jobs', 'status IN ("closed", "completed")'),
            'totalApplications' => $this->count('applications'),
            'pendingApplications' => $this->count('applications', 'status = "pending"'),
            'acceptedApplications' => $this->count('applications', 'status = "accepted"'),
        ];
    }

    public function getJobReports(): array
    {
        $result = $this->db->query(
            'SELECT reports.id, reports.job_id AS jobId, jobs.title AS jobTitle,
                    reports.reason AS reasonLabel, users.full_name AS reporterName,
                    users.role AS reporterRole, reports.note,
                    reports.reported_at AS reportedAt, reports.status
             FROM job_reports AS reports
             INNER JOIN jobs ON jobs.id = reports.job_id
             INNER JOIN users ON users.id = reports.reporter_id
             ORDER BY reports.reported_at DESC'
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function resolveReport(string $reportId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE job_reports SET status = "resolved", resolved_at = CURRENT_TIMESTAMP
             WHERE id = ? AND status = "pending"'
        );
        $statement->bind_param('s', $reportId);
        $statement->execute();
        $updated = $statement->affected_rows > 0;
        $statement->close();
        return $updated;
    }

    private function count(string $table, string $condition = ''): int
    {
        $query = "SELECT COUNT(*) AS total FROM {$table}";
        if ($condition !== '') {
            $query .= " WHERE {$condition}";
        }
        return (int)$this->db->query($query)->fetch_assoc()['total'];
    }

    public function getPendingReportsCount(): int
    {
        $result = $this->db->query('SELECT COUNT(*) AS total FROM job_reports WHERE status = "pending"');
        return (int)$result->fetch_assoc()['total'];
    }
}
