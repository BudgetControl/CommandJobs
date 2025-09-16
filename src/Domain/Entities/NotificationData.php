<?php declare(strict_types= 1);

namespace Budgetcontrol\jobs\Domain\Entities;

use Ramsey\Uuid\Uuid;
use Budgetcontrol\jobs\Domain\Entities\EntityInterface;

final class NotificationData implements EntityInterface
{
    private string $userUuid;
    private string $body;
    private ?string $title;

    public function toArray(): array
    {
        return [
            'userUuid' => $this->userUuid,
            'body' => $this->body,
            'title' => $this->title,
        ];
    }

    private string $id;

    public function __construct(
        string $userUuid,
        string $body,
        ?string $title = null
    ) {
        $this->userUuid = $userUuid;
        $this->body = $body;
        $this->title = $title;
        $this->id = Uuid::uuid4()->toString();
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getId(): string
    {
        return $this->id;
    }
}