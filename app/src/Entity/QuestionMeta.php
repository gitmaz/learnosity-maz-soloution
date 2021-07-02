<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * QuestionMeta
 *
 * @ORM\Table(name="question_meta",
 *      indexes={@ORM\Index(name="question_meta_closed_at_index", columns={"closed_at"}),
 *      @ORM\Index(name="question_meta_by_activity_id_index", columns={"activity_id"}),
 *      @ORM\Index(name="question_meta_by_user_id_index", columns={"user_id"}),
 *      @ORM\Index(name="question_meta_by_question_nr_index", columns={"question_nr"}),
 *      @ORM\Index(name="question_meta_opened_at_index", columns={"opened_at"}),
 *      @ORM\Index(name="question_meta_closed_at_index", columns={"closed_at"})
 *      })
 * @ORM\Entity
 */
class QuestionMeta
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
     * @ORM\Column(name="question_nr", type="integer", nullable=true, options={"default"=NULL})
     */
    private $questionNr = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="opened_at", type="integer", nullable=true, options={"default"=NULL})
     */
    private $openedAt = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="closed_at", type="integer", nullable=true, options={"default"=NULL})
     */
    private $closedAt = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="duration", type="integer", nullable=true, options={"default"=NULL})
     */
      private $duration = null;

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

    public function getQuestionNr(): ?int
    {
        return $this->questionNr;
    }

    public function setQuestionNr(?int $questionNr): self
    {
        $this->questionNr = $questionNr;

        return $this;
    }

    public function getOpenedAt(): ?int
    {
        return $this->openedAt;
    }

    public function setOpenedAt(?int $openedAt): self
    {
        $this->openedAt = $openedAt;

        return $this;
    }

    public function getClosedAt(): ?int
    {
        return $this->closedAt;
    }

    public function setClosedAt(?int $closedAt): self
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }


    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }


}
