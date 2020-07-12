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

use FacturaScripts\Core\Model\Base;

/**
 * Description of TrabajoServicioAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class TrabajoServicioAT extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var string
     */
    public $codagente;

    /**
     *
     * @var string
     */
    public $descripcion;

    /**
     *
     * @var string
     */
    public $fechafin;

    /**
     *
     * @var string
     */
    public $fechainicio;

    /**
     *
     * @var string
     */
    public $horafin;

    /**
     *
     * @var string
     */
    public $horainicio;

    /**
     *
     * @var int
     */
    public $idservicio;

    /**
     *
     * @var int
     */
    public $idtrabajo;

    /**
     *
     * @var float
     */
    public $numhoras;

    public function clear()
    {
        parent::clear();
        $this->fechainicio = \date(self::DATE_STYLE);
        $this->horainicio = \date(self::HOUR_STYLE);
        $this->numhoras = 0.0;
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'idtrabajo';
    }

    /**
     * 
     * @return string
     */
    public static function tableName(): string
    {
        return 'serviciosat_trabajos';
    }
}
