<?php

namespace Scrippy\Interfaces;

interface Runnable
{
    public function run(): void;

    public function proof(): bool;
}
