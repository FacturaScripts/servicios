<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Fabricante;

/**
 * Description of MaquinaAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class MaquinaAT extends Base\ModelClass
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
    public $codcliente;

    /**
     *
     * @var string
     */
    public $codfabricante;

    /**
     *
     * @var string
     */
    public $descripcion;

    /**
     *
     * @var string
     */
    public $fecha;

    /**
     *
     * @var int
     */
    public $idmaquina;

    /**
     *
     * @var string
     */
    public $nombre;

    /**
     *
     * @var string
     */
    public $numserie;

    /**
     *
     * @var string
     */
    public $referencia;

    public function clear()
    {
        parent::clear();
        $this->fecha = \date(self::DATE_STYLE);
    }

    /**
     * 
     * @return Fabricante
     */
    public function getFabricante()
    {
        $fabricante = new Fabricante();
        $fabricante->loadFromCode($this->codfabricante);
        return $fabricante;
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'idmaquina';
    }

    /**
     * 
     * @return string
     */
    public function primaryDescriptionColumn(): string
    {
        return 'idmaquina';
    }

    /**
     * 
     * @return string
     */
    public static function tableName(): string
    {
        return 'serviciosat_maquinas';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $utils = $this->toolBox()->utils();
        $fields = ['descripcion', 'nombre', 'numserie', 'referencia'];
        foreach ($fields as $key) {
            $this->{$key} = $utils->noHtml($this->{$key});
        }

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
