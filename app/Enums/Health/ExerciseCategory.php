<?php

namespace App\Enums\Health;

enum ExerciseCategory: string
{
    case Chest = 'Chest';
    case Shoulders = 'Shoulders';
    case Legs = 'Legs';
    case Back = 'Back';
    case Core = 'Core';
    case Cardio = 'Cardio';
    case Arms = 'Arms';
}
