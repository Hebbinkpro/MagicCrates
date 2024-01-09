<?php

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
        return "enum";
    }

    public function getEnumName(): string
    {
        return "crate_type";
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