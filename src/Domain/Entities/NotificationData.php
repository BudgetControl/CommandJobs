<?php declare(strict_types= 1);

namespace Budgetcontrol\jobs\Domain\Entities;

use Ramsey\Uuid\Uuid;
use Budgetcontrol\jobs\Domain\Entities\EntityInterface;

final class NotificationData implements EntityInterface
{
    private string $to;
    private string $message;
    private string $dateTimeToSend;
    private bool $active;

    public function toArray(): array
    {
        return [
            'to' => $this->to,
            'message' => $this->message,
            'dateTimeToSend' => $this->dateTimeToSend,
            'active' => $this->active,
        ];
    }

    private string $id;

    public function __construct(
        string $to,
        string $message,
        string $dateTimeToSend,
        bool $active
    ) {
        $this->to = $to;
        $this->message = $message;
        $this->dateTimeToSend = $dateTimeToSend;
        $this->active = $active;
        $this->id = Uuid::uuid4()->toString();
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDateTimeToSend(): string
    {
        return $this->dateTimeToSend;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getId(): string
    {
        return $this->id;
    }
}