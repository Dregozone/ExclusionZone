<?php

namespace App;

/**
 * How one round of a fight ended.
 */
enum FightOutcome: string
{
    case Won = 'won';
    case Ongoing = 'ongoing';
    case Fled = 'fled';
    case Died = 'died';
}
