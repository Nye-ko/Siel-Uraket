<?php

final class Client
{
    public function __construct(private mysqli $db)
    {
    }

    public function jobs(string $clientId): array
    {
        $statement = $this->db->prepare(
            'SELECT jobs.id, jobs.title, jobs.job_type AS category, jobs.budget,
                    jobs.status, jobs.deadline, jobs.posted_at AS postedAt,
                    COUNT(applications.id) AS applicationsCount,
                    SUM(applications.status = "pending") AS pendingCount
             FROM jobs LEFT JOIN applications ON applications.job_id = jobs.id
             WHERE jobs.client_id = ? GROUP BY jobs.id ORDER BY jobs.posted_at DESC'
        );
        $statement->bind_param('s', $clientId);
        $statement->execute();
        $jobs = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $jobs;
    }

    public function stats(string $clientId): array
    {
        $statement = $this->db->prepare('SELECT COUNT(*) AS total FROM jobs WHERE client_id = ?');
        $statement->bind_param('s', $clientId);
        $statement->execute();
        $totalJobs = (int)$statement->get_result()->fetch_assoc()['total'];
        $statement->close();

        $statement = $this->db->prepare(
            'SELECT COUNT(*) AS total FROM applications
             WHERE job_id IN (SELECT id FROM jobs WHERE client_id = ?)'
        );
        $statement->bind_param('s', $clientId);
        $statement->execute();
        $totalApplications = (int)$statement->get_result()->fetch_assoc()['total'];
        $statement->close();
        return ['totalJobs' => $totalJobs, 'totalApplications' => $totalApplications];
    }

    public function createJob(string $clientId, array $jobData, array $skillIds): string
    {
        $jobId = 'job-' . bin2hex(random_bytes(12));
        $this->db->begin_transaction();
        try {
            $statement = $this->db->prepare(
                'INSERT INTO jobs (id, client_id, title, job_type, budget, description)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $budget = (float)$jobData['budget'];
            $statement->bind_param('ssssds', $jobId, $clientId, $jobData['title'], $jobData['type'], $budget, $jobData['description']);
            $statement->execute();
            $statement->close();

            $skillStatement = $this->db->prepare('INSERT INTO job_skills (job_id, skill_id) VALUES (?, ?)');
            foreach ($skillIds as $skillId) {
                $skillStatement->bind_param('ss', $jobId, $skillId);
                $skillStatement->execute();
            }
            $skillStatement->close();
            $this->db->commit();
        } catch (mysqli_sql_exception $error) {
            $this->db->rollback();
            throw $error;
        }
        return $jobId;
    }

    public function closeJob(string $clientId, string $jobId): bool
    {
        $statement = $this->db->prepare('UPDATE jobs SET status = "closed" WHERE id = ? AND client_id = ? AND status = "open"');
        $statement->bind_param('ss', $jobId, $clientId);
        $statement->execute();
        $updated = $statement->affected_rows > 0;
        $statement->close();
        return $updated;
    }

    public function profile(string $clientId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT users.full_name AS name, user_profiles.organization AS collegeOrOffice, user_profiles.about
             FROM users LEFT JOIN user_profiles ON user_profiles.user_id = users.id
             WHERE users.id = ? AND users.role = "client"'
        );
        $statement->bind_param('s', $clientId);
        $statement->execute();
        $profile = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $profile;
    }

    public function job(string $clientId, string $jobId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT jobs.id, jobs.client_id AS clientId, users.full_name AS clientName,
                    jobs.title, jobs.job_type AS jobType, jobs.budget, jobs.description,
                    jobs.status, jobs.deadline
             FROM jobs INNER JOIN users ON users.id = jobs.client_id
             WHERE jobs.id = ? AND jobs.client_id = ?'
        );
        $statement->bind_param('ss', $jobId, $clientId);
        $statement->execute();
        $job = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        if ($job) {
            $job['skills'] = $this->skillsForJob($jobId);
        }
        return $job;
    }

    public function applications(string $jobId): array
    {
        $statement = $this->db->prepare(
            'SELECT applications.id, applications.job_id AS jobId, applications.freelancer_id AS freelancerId,
                    users.full_name AS freelancerName, applications.title, applications.message, applications.status,
                    applications.applied_at AS appliedAt
             FROM applications INNER JOIN users ON users.id = applications.freelancer_id
             WHERE applications.job_id = ? ORDER BY applications.applied_at DESC'
        );
        $statement->bind_param('s', $jobId);
        $statement->execute();
        $applications = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $applications;
    }

    private function skillsForJob(string $jobId): array
    {
        $statement = $this->db->prepare(
            'SELECT skills.id, skills.name FROM job_skills
             INNER JOIN skills ON skills.id = job_skills.skill_id
             WHERE job_skills.job_id = ? ORDER BY skills.name'
        );
        $statement->bind_param('s', $jobId);
        $statement->execute();
        $skills = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $skills;
    }

    public function updateApplicationStatus(string $applicationId, string $status): bool
    {
        $statement = $this->db->prepare(
            'UPDATE applications SET status = ? WHERE id = ?'
        );
        $statement->bind_param('ss', $status, $applicationId);
        $statement->execute();
        $updated = $statement->affected_rows > 0;
        $statement->close();
        return $updated;
    }

    public function updateJobStatus(string $jobId, string $status): bool
    {
        $statement = $this->db->prepare(
            'UPDATE jobs SET status = ? WHERE id = ?'
        );
        $statement->bind_param('ss', $status, $jobId);
        $statement->execute();
        $updated = $statement->affected_rows > 0;
        $statement->close();
        return $updated;
    }

    public function submitReview(string $applicationId, string $reviewerId, string $revieweeId, int $rating, string $comment): bool
    {
        $reviewId = 'review-' . bin2hex(random_bytes(12));
        $statement = $this->db->prepare(
            'INSERT INTO reviews (id, application_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param('ssssis', $reviewId, $applicationId, $reviewerId, $revieweeId, $rating, $comment);
        $statement->execute();
        $success = $statement->affected_rows > 0;
        $statement->close();
        return $success;
    }
}
