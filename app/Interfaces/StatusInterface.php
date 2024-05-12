<?php

namespace App\Interfaces;

interface StatusInterface
{
    public function process($text): void;
}