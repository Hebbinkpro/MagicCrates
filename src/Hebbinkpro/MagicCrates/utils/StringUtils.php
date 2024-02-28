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

namespace Hebbinkpro\MagicCrates\utils;

class StringUtils
{
    public const PARAM_OPEN = "{";
    public const PARAM_CLOSE = "}";

    /**
     * Replace all params inside the string with values of the given params
     * @param string $str the string with parameters
     * @param array $params the parameters to replace in the string
     * @return string the string with the parameter values
     */
    public static function prepare(string $str, array $params): string
    {
        foreach ($params as $name => $v) {
            $param = self::PARAM_OPEN . $name . self::PARAM_CLOSE;
            $str = str_replace($param, $v, $str);
        }

        return $str;
    }
}