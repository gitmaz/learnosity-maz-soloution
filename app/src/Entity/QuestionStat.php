<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * QuestionStat
 *
 * @ORM\Table(name="question_stat",
 *      indexes={
 *     @ORM\Index(name="question_stat_by_activity_id_index", columns={"activity_id"}),
 *     @ORM\Index(name="question_stat_by_user_id_index", columns={"user_id"}),
 *     @ORM\Index(name="question_stat_by_started_at_index", columns={"started_at"}),
 *     @ORM\Index(name="question_stat_by_started_at_timestamp_index", columns={"started_at_timestamp"}),
 *     @ORM\Index(name="question_stat_by_stopped_at_index", columns={"stopped_at"}),
 *     @ORM\Index(name="question_stat_by_stopped_at_timestamp_index", columns={"stopped_at_timestamp"}),
 *     @ORM\Index(name="question_stat_by_tot_durations_index", columns={"tot_durations"})
 *     })
 * @ORM\Entity
 */
class QuestionStat
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="activity_id", type="string", length=20, nullable=true, options={"default"=NULL})
     */
    private $activityId = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="user_id", type="string", length=20, nullable=true, options={"default"=NULL})
     */
    private $userId = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="latest_question_nr", type="integer", nullable=true, options={"default"=NULL})
     */
    private $latestQuestionNr = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="status", type="string", length=20, nullable=true, options={"default"=NULL})
     */
    private $status = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="tot_durations", type="integer", nullable=true, options={"default"=NULL})
     */
    private $totDurations = null;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="started_at", type="datetime", nullable=true)
     */
    private $startedAt;

    /**
     * @var int|null
     *
     * @ORM\Column(name="started_at_timestamp", type="integer", nullable=true)
     */
    private $startedAtTimestamp;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="stopped_at", type="datetime", nullable=false)
     */
    private $stoppedAt;

    /**
     * @var int|null
     *
     * @ORM\Column(name="stopped_at_timestamp", type="integer", nullable=true)
     */
    private $stoppedAtTimestamp;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime",  nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime",  nullable=true)
     */
    private $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActivityId(): ?string
    {
        return $this->activityId;
    }

    public function setActivityId(?string $activityId): self
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getLatestQuestionNr(): ?int
    {
        return $this->latestQuestionNr;
    }

    public function setLatestQuestionNr(?int $latestQuestionNr): self
    {
        $this->latestQuestionNr = $latestQuestionNr;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTotDurations(): ?int
    {
        return $this->totDurations;
    }

    public function setTotDurations(?int $totDurations): self
    {
        $this->totDurations = $totDurations;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getStartedAtTimestamp(): ?int
    {
        return $this->startedAtTimestamp;
    }

    public function setStartedAtTimestamp(?int $startedAtTimestamp): self
    {
        $this->startedAtTimestamp = $startedAtTimestamp;

        return $this;
    }



    public function getStoppedAt(): ?\DateTimeInterface
    {
        return $this->stoppedAt;
    }

    public function setStoppedAt(\DateTimeInterface $stoppedAt): self
    {
        $this->stoppedAt = $stoppedAt;

        return $this;
    }

    public function getStoppedAtTimestamp(): ?int
    {
        return $this->stoppedAtTimestamp;
    }

    public function setStoppedAtTimestamp(?int $stoppedAtTimestamp): self
    {
        $this->stoppedAtTimestamp = $stoppedAtTimestamp;

        return $this;
    }


    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }


}
