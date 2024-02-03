<?php
/*
 *   __  __             _       _____           _
 *  |  \/  |           (_)     / ____|         | |
 *  | \  / | __ _  __ _ _  ___| |     _ __ __ _| |_ ___  ___
 *  | |\/| |/ _` |/ _` | |/ __| |    | '__/ _` | __/ _ \/ __|
 *  | |  | | (_| | (_| | | (__| |____| | | (_| | ||  __/\__ \
 *  |_|  |_|\__,_|\__, |_|\___|\_____|_|  \__,_|\__\___||___/
 *                 __/ |
 *                |___/
 *
 * Copyright (c) 2024 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\MagicCrates\commands\args;

use CortexPE\Commando\args\StringEnumArgument;
use Hebbinkpro\MagicCrates\crate\CrateType;
use pocketmine\command\CommandSender;

class CrateTypeArgument extends StringEnumArgument
{
    public function __construct(string $name, bool $optional = false)
    {
        parent::__construct($name, $optional);
    }

    public function parse(string $argument, CommandSender $sender): string
    {
        return $argument;
    }

    public function getTypeName(): string
    {
        return "crate_type";
    }

    public function getEnumName(): string
    {
        return "Crate Type";
    }

    public function getEnumValues(): array
    {
        $values = [];
        foreach (CrateType::getAllTypeIds() as $type) {
            $values[$type] = $type;
        }
        return $values;
    }
}