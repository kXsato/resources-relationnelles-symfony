<?php

namespace App\Enum;

enum ResourceStatus: string
{
    case DRAFT     = 'draft';
    case PENDING   = 'pending';
    case PUBLISHED = 'published';
    case ARCHIVED  = 'archived';
    case REJECTED  = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::DRAFT     => 'Brouillon',
            self::PENDING   => 'En attente',
            self::PUBLISHED => 'Publié',
            self::ARCHIVED  => 'Archivé',
            self::REJECTED  => 'Rejeté',
        };
    }

    /** @return array<string, string> pour les ChoiceField EasyAdmin */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->label()] = $case->value;
        }
        return $choices;
    }
}
