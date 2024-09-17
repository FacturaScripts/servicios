<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\Servicios\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Tools;

/**
 * Description of TipoAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class TipoAT extends Base\ModelClass
{

    use Base\ModelTrait;

    public $id;

    /** @var bool */
    public $default;

    /** @var string */
    public $name;

    public function clear()
    {
        parent::clear();
        $this->default = true;
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public function save(): bool
    {
        if (false === parent::save()) {
            return false;
        }

        if ($this->default) {
            $where = [
                new DataBaseWhere('default', true),
                new DataBaseWhere('id', $this->id, '!=')
            ];
            foreach ($this->all($where, [], 0, 0) as $type) {
                $type->default = false;
                $type->save();
            }
        }

        return true;
    }

    public static function tableName(): string
    {
        return 'serviciosat_tipos';
    }

    public function test(): bool
    {
        $this->name = Tools::noHtml($this->name);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListServicioAT?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}