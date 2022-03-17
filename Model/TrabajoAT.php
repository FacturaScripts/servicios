<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of TrabajoAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class TrabajoAT extends Base\ModelOnChangeClass
{

    use Base\ModelTrait;

    const STATUS_NONE = 0;
    const STATUS_MAKE_INVOICE = 1;
    const STATUS_INVOICED = 2;
    const STATUS_MAKE_DELIVERY_NOTE = 3;
    const STATUS_DELIVERY_NOTE = 4;
    const STATUS_MAKE_ESTIMATION = 5;
    const STATUS_ESTIMATION = 6;
    const STATUS_SUBTRACT_STOCK = -1;

    /**
     * @var float
     */
    public $cantidad;

    /**
     * @var string
     */
    public $codagente;

    /**
     * @var string
     */
    public $descripcion;

    /**
     * @var int
     */
    public $estado;

    /**
     * @var string
     */
    public $fechafin;

    /**
     * @var string
     */
    public $fechainicio;

    /**
     * @var string
     */
    public $horafin;

    /**
     * @var string
     */
    public $horainicio;

    /**
     * @var int
     */
    public $idservicio;

    /**
     * @var int
     */
    public $idtrabajo;

    /**
     * @var string
     */
    public $nick;

    /**
     * @var string
     */
    public $observaciones;

    /**
     * @var float
     */
    public $precio;

    /**
     * @var string
     */
    public $referencia;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->cantidad = 1.0;
        $this->estado = (int)self::toolBox()::appSettings()::get('servicios', 'workstatus');
        $this->fechainicio = date(self::DATE_STYLE);
        $this->horainicio = date(self::HOUR_STYLE);
        $this->precio = 0.0;
    }

    /**
     * @return ServicioAT
     */
    public function getServicio()
    {
        $servicio = new ServicioAT();
        $servicio->loadFromCode($this->idservicio);
        return $servicio;
    }

    /**
     * @return Variante
     */
    public function getVariante()
    {
        $variante = new Variante();
        $where = [new DataBaseWhere('referencia', $this->referencia)];
        $variante->loadFromCode('', $where);
        return $variante;
    }

    /**
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'idtrabajo';
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'serviciosat_trabajos';
    }

    /**
     * @return bool
     */
    public function test()
    {
        foreach (['descripcion', 'observaciones', 'referencia'] as $field) {
            $this->{$field} = $this->toolBox()->utils()->noHtml($this->{$field});
        }

        if ($this->referencia) {
            $variante = $this->getVariante();
            $this->descripcion = empty($this->descripcion) ? $variante->description() : $this->descripcion;
            $this->precio = empty($this->precio) ? $variante->precio : $this->precio;
        }

        return parent::test();
    }

    /**
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListServicioAT')
    {
        return empty($this->idservicio) ? parent::url($type, $list) : $this->getServicio()->url();
    }
}
