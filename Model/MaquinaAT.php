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

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Fabricante;

/**
 * Description of MaquinaAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class MaquinaAT extends ModelClass
{
    use ModelTrait;

    /** @var string Código del agente relacionado con la máquina. */
    public $codagente;

    /** @var string Código del cliente propietario de la máquina. */
    public $codcliente;

    /** @var string Código del fabricante de la máquina. */
    public $codfabricante;

    /** @var string Descripción de la máquina. */
    public $descripcion;

    /** @var string Fecha de alta de la máquina. */
    public $fecha;

    /** @var int Clave primaria. Identificador de la máquina. */
    public $idmaquina;

    /** @var string Nombre de la máquina. */
    public $nombre;

    /** @var string Número de serie de la máquina. */
    public $numserie;

    /** @var string Referencia del producto relacionado con la máquina. */
    public $referencia;

    public function clear(): void
    {
        parent::clear();
        $this->fecha = Tools::date();
    }

    public function getFabricante(): Fabricante
    {
        $fabricante = new Fabricante();
        $fabricante->load($this->codfabricante);
        return $fabricante;
    }

    public function install(): string
    {
        new Cliente();
        new Fabricante();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idmaquina';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'idmaquina';
    }

    public static function tableName(): string
    {
        return 'serviciosat_maquinas';
    }

    public function test(): bool
    {
        $fields = ['descripcion', 'nombre', 'numserie', 'referencia'];
        foreach ($fields as $key) {
            $this->{$key} = Tools::noHtml($this->{$key});
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListServicioAT?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
