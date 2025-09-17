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

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

/**
 * Description of TipoAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class TipoAT extends ModelClass
{
    use ModelTrait;

    /** @var int */
    public $id;

    /** @var bool */
    public $default;

    /** @var string */
    public $name;

    public function clear(): void
    {
        parent::clear();
        $this->default = true;
    }

    public function save(): bool
    {
        if (false === parent::save()) {
            return false;
        }

        if ($this->default) {
            $where = [
                Where::column('default', true),
                Where::column('id', $this->id, '!=')
            ];
            foreach ($this->all($where) as $type) {
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
