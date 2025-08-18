<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of PrioridadAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class PrioridadAT extends Base\ModelClass
{
    use Base\ModelTrait;

    /** @var int */
    public $id;

    /** @var string */
    public $nombre;

    /** @var bool */
    public $predeterminado;

    public function clear(): void
    {
        parent::clear();
        $this->predeterminado = false;
    }

    public function delete(): bool
    {
        if (false === parent::delete()) {
            return false;
        }

        if ($this->predeterminado) {
            // ponemos alguna prioridad como predeterminada
            foreach ($this->all() as $item) {
                $item->predeterminado = true;
                if ($item->save()) {
                    break;
                }
            }
        }

        return true;
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

        if ($this->predeterminado) {
            $where = [
                new DataBaseWhere('predeterminado', true),
                new DataBaseWhere('id', $this->id, '!=')
            ];
            foreach ($this->all($where, [], 0, 0) as $priority) {
                $priority->predeterminado = false;
                $priority->save();
            }
        }

        return true;
    }

    public static function tableName(): string
    {
        return 'serviciosat_prioridades';
    }

    public function test(): bool
    {
        $this->nombre = Tools::noHtml($this->nombre);

        if (empty($this->id)) {
            $this->id = $this->newCode();
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListServicioAT?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
