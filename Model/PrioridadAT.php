<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Description of PrioridadAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class PrioridadAT extends Base\ModelClass
{

    use Base\ModelTrait;

    public $id;

    /**
     *
     * @var string
     */
    public $nombre;

    /**
     *
     * @var bool
     */
    public $predeterminado;

    public function clear()
    {
        parent::clear();
        $this->predeterminado = true;
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * 
     * @return bool
     */
    public function save()
    {
        if (false === parent::save()) {
            return false;
        }

        if ($this->predeterminado) {
            $where = [
                new DataBaseWhere('predeterminado', true),
                new DataBaseWhere('id', $this->id, '!=')
            ];
            foreach ($this->all($where) as $priority) {
                $priority->predeterminado = false;
                $priority->save();
            }
        }

        return true;
    }

    /**
     * 
     * @return string
     */
    public static function tableName(): string
    {
        return 'serviciosat_prioridades';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->nombre = $this->toolBox()->utils()->noHtml($this->nombre);
        return parent::test();
    }

    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListServicioAT?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
