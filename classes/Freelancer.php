<?php

final class Freelancer
{
    public function __construct(private mysqli $db)
    {
    }

    public function stats(string $freelancerId): array
    {
        $counts = [];
        foreach (['total' => null, 'pending' => 'pending', 'accepted' => 'accepted', 'rejected' => 'rejected'] as $key => $status) {
            $query = 'SELECT COUNT(*) AS total FROM applications WHERE freelancer_id = ?';
            if ($status !== null) {
                $query .= ' AND status = ?';
            }
            $statement = $this->db->prepare($query);
            if ($status === null) {
                $statement->bind_param('s', $freelancerId);
            } else {
                $statement->bind_param('ss', $freelancerId, $status);
            }
            $statement->execute();
            $counts[$key] = (int)$statement->get_result()->fetch_assoc()['total'];
            $statement->close();
        }
        return [
            'totalApplications' => $counts['total'],
            'totalPendingApplications' => $counts['pending'],
            'totalAcceptedApplications' => $counts['accepted'],
            'totalRejectedApplications' => $counts['rejected'],
        ];
    }

    public function applications(string $freelancerId): array
    {
        $statement = $this->db->prepare(
            'SELECT applications.id, applications.job_id AS jobId, jobs.title AS jobTitle,
                    applications.status, applications.applied_at AS appliedAt, users.full_name AS clientName
             FROM applications INNER JOIN jobs ON jobs.id = applications.job_id
             INNER JOIN users ON users.id = jobs.client_id
             WHERE applications.freelancer_id = ? ORDER BY applications.applied_at DESC'
        );
        $statement->bind_param('s', $freelancerId);
        $statement->execute();
        $applications = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $applications;
    }

    public function profile(string $freelancerId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT users.id, users.full_name AS fullName,
                    user_profiles.organization AS college, user_profiles.about
             FROM users LEFT JOIN user_profiles ON user_profiles.user_id = users.id
             WHERE users.id = ? AND users.role = "freelancer"'
        );
        $statement->bind_param('s', $freelancerId);
        $statement->execute();
        $profile = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        if (!$profile) {
            return null;
        }

        $statement = $this->db->prepare(
            'SELECT skills.id, skills.name FROM user_skills
             INNER JOIN skills ON skills.id = user_skills.skill_id
             WHERE user_skills.user_id = ? ORDER BY skills.name'
        );
        $statement->bind_param('s', $freelancerId);
        $statement->execute();
        $profile['skills'] = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $statement = $this->db->prepare(
            'SELECT reviewers.full_name AS clientName, reviews.rating, reviews.comment
             FROM reviews INNER JOIN users AS reviewers ON reviewers.id = reviews.reviewer_id
             WHERE reviews.reviewee_id = ? ORDER BY reviews.created_at DESC'
        );
        $statement->bind_param('s', $freelancerId);
        $statement->execute();
        $profile['reviews'] = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $profile;
    }

    public function openJobs(): array
    {
        $result = $this->db->query(
            'SELECT jobs.id, jobs.title, jobs.description, jobs.posted_at AS postedAt,
                    users.full_name AS clientName, user_profiles.organization AS clientOrgUnit
             FROM jobs INNER JOIN users ON users.id = jobs.client_id
             LEFT JOIN user_profiles ON user_profiles.user_id = users.id
             WHERE jobs.status = "open" ORDER BY jobs.posted_at DESC'
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function job(string $jobId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT jobs.id, jobs.title, jobs.job_type AS jobType, jobs.budget,
                    jobs.client_id AS clientId, users.full_name AS clientName,
                    jobs.status, jobs.description, jobs.deadline
             FROM jobs INNER JOIN users ON users.id = jobs.client_id WHERE jobs.id = ?'
        );
        $statement->bind_param('s', $jobId);
        $statement->execute();
        $job = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        if ($job) {
            $statement = $this->db->prepare(
                'SELECT skills.id, skills.name FROM job_skills
                 INNER JOIN skills ON skills.id = job_skills.skill_id
                 WHERE job_skills.job_id = ? ORDER BY skills.name'
            );
            $statement->bind_param('s', $jobId);
            $statement->execute();
            $job['skills'] = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
            $statement->close();
        }
        return $job;
    }

    public function application(string $freelancerId, string $jobId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT applications.id, applications.job_id AS jobId, applications.title,
                    applications.message, applications.status, applications.applied_at AS appliedAt,
                    jobs.title AS jobTitle, jobs.client_id AS clientId, users.full_name AS clientName
             FROM applications INNER JOIN jobs ON jobs.id = applications.job_id
             INNER JOIN users ON users.id = jobs.client_id
             WHERE applications.freelancer_id = ? AND applications.job_id = ? LIMIT 1'
        );
        $statement->bind_param('ss', $freelancerId, $jobId);
        $statement->execute();
        $application = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $application;
    }

    public function apply(string $freelancerId, string $jobId, string $title, string $message): array
    {
        $applicationId = 'app-' . bin2hex(random_bytes(12));
        $statement = $this->db->prepare(
            'INSERT INTO applications (id, job_id, freelancer_id, title, message) VALUES (?, ?, ?, ?, ?)'
        );
        $statement->bind_param('sssss', $applicationId, $jobId, $freelancerId, $title, $message);
        $statement->execute();
        $statement->close();
        return $this->application($freelancerId, $jobId) ?? [];
    }

    public function report(string $reporterId, string $jobId, string $reason, string $note): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO job_reports (id, job_id, reporter_id, reason, note) VALUES (?, ?, ?, ?, ?)'
        );
        $reportId = 'report-' . bin2hex(random_bytes(12));
        $statement->bind_param('sssss', $reportId, $jobId, $reporterId, $reason, $note);
        $statement->execute();
        $statement->close();
    }

    public function review(string $applicationId, string $reviewerId, string $revieweeId, int $rating, string $comment): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO reviews (id, application_id, reviewer_id, reviewee_id, rating, comment)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $reviewId = 'review-' . bin2hex(random_bytes(12));
        $statement->bind_param('ssssis', $reviewId, $applicationId, $reviewerId, $revieweeId, $rating, $comment);
        $statement->execute();
        $statement->close();
    }

    public function completeApplication(string $applicationId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE applications SET status = "completed" WHERE id = ? AND status = "accepted"'
        );
        $statement->bind_param('s', $applicationId);
        $statement->execute();
        $updated = $statement->affected_rows > 0;
        $statement->close();
        return $updated;
    }
}
