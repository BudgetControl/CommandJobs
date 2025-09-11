<?php declare(strict_types= 1);

namespace Budgetcontrol\jobs\Domain\Entities;

interface EntityInterface
{
    public function getId(): string;
    
    public function toArray(): array;

}