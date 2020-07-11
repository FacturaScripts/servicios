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
 * Description of ServicioCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ServicioCliente extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var string
     */
    public $accesorios;

    /**
     *
     * @var string
     */
    public $apartado;

    /**
     *
     * @var string
     */
    public $cifnif;

    /**
     *
     * @var string
     */
    public $ciudad;

    /**
     *
     * @var string
     */
    public $codagente;

    /**
     *
     * @var string
     */
    public $codalmacen;

    /**
     *
     * @var string
     */
    public $codcliente;

    /**
     *
     * @var string
     */
    public $coddivisa;

    /**
     *
     * @var string
     */
    public $codigo;

    /**
     *
     * @var string
     */
    public $codpago;

    /**
     *
     * @var string
     */
    public $codpais;

    /**
     *
     * @var string
     */
    public $codpostal;

    /**
     *
     * @var string
     */
    public $descripcion;

    /**
     *
     * @var string
     */
    public $direccion;

    /**
     *
     * @var bool
     */
    public $editable;

    /**
     *
     * @var string
     */
    public $fecha;

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
    public $femail;

    /**
     *
     * @var bool
     */
    public $garantia;

    /**
     *
     * @var string
     */
    public $hora;

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
    public $idempresa;

    /**
     *
     * @var int
     */
    public $idestado;

    /**
     *
     * @var int
     */
    public $idservicio;

    /**
     *
     * @var string
     */
    public $material;

    /**
     *
     * @var string
     */
    public $material_estado;

    /**
     *
     * @var string
     */
    public $nombrecliente;

    /**
     *
     * @var string
     */
    public $observaciones;

    /**
     *
     * @var int
     */
    public $prioridad;

    /**
     *
     * @var string
     */
    public $provincia;

    /**
     *
     * @var string
     */
    public $solucion;

    /**
     *
     * @var float
     */
    public $tasaconv;

    public function clear()
    {
        parent::clear();
        $this->fecha = \date(self::DATE_STYLE);
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// neede dependencies
        new EstadoServicio();

        return parent::install();
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'idservicio';
    }

    /**
     * 
     * @return string
     */
    public static function tableName(): string
    {
        return 'servicioscli';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $utils = $this->toolBox()->utils();
        $fields = ['accesorios', 'apartado', 'cifnif', 'ciudad', 'codigo', 'codpostal'
            , 'descripcion', 'direccion', 'material', 'material_estado', 'nombrecliente'
            , 'observaciones', 'provincia', 'solucion'];
        foreach ($fields as $key) {
            $this->{$key} = $utils->noHtml($this->{$key});
        }

        return parent::test();
    }
}
