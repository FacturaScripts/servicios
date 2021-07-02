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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Model\Cliente;

/**
 * Description of ServicioAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ServicioAT extends Base\ModelOnChangeClass
{

    use Base\ModelTrait;
    use Base\CompanyRelationTrait;

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
    public $descripcion;

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
    public $hora;

    /**
     *
     * @var int
     */
    public $idestado;

    /**
     *
     * @var int
     */
    public $idmaquina;

    /**
     *
     * @var int
     */
    public $idmaquina2;

    /**
     *
     * @var int
     */
    public $idmaquina3;

    /**
     *
     * @var int
     */
    public $idmaquina4;

    /**
     *
     * @var int
     */
    public $idprioridad;

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
    public $nick;

    /**
     *
     * @var string
     */
    public $observaciones;

    /**
     *
     * @var string
     */
    public $solucion;

    public function clear()
    {
        parent::clear();
        $this->fecha = \date(self::DATE_STYLE);
        $this->hora = \date(self::HOUR_STYLE);

        /// set default status
        foreach ($this->getAvailableStatus() as $status) {
            if ($status->predeterminado) {
                $this->idestado = $status->id;
                $this->editable = $status->editable;
                break;
            }
        }

        /// set default priority
        foreach ($this->getAvailablePriority() as $priority) {
            if ($priority->predeterminado) {
                $this->idprioridad = $priority->id;
                break;
            }
        }
    }

    /**
     *
     * @return EstadoAT[]
     */
    public function getAvailableStatus()
    {
        $status = new EstadoAT();
        return $status->all([], [], 0, 0);
    }

    /**
     *
     * @return MaquinaAT[]
     */
    public function getMachines()
    {
        $result = [];
        $machines = [ $this->idmaquina, $this->idmaquina2, $this->idmaquina3, $this->idmaquina4 ];
        foreach ($machines as $code) {
            if (empty($code)) {
                continue;
            }

            $machine = new MaquinaAT();
            $machine->loadFromCode($code);
            $result[] = $machine;
        }

        return $result;
    }

    /**
     *
     * @return EstadoAT
     */
    public function getStatus()
    {
        $status = new EstadoAT();
        $status->loadFromCode($this->idestado);
        return $status;
    }

    /**
     *
     * @return PrioridadAT
     */
    public function getAvailablePriority()
    {
        $priority = new PrioridadAT();
        return $priority->all([], [], 0, 0);
    }

    /**
     *
     * @return PrioridadAT
     */
    public function getPriority()
    {
        $priority = new PrioridadAT();
        $priority->loadFromCode($this->idprioridad);
        return $priority;
    }

    /**
     *
     * @return Cliente
     */
    public function getSubject()
    {
        $cliente = new Cliente();
        $cliente->loadFromCode($this->codcliente);
        return $cliente;
    }

    /**
     *
     * @return TrabajoAT[]
     */
    public function getTrabajos()
    {
        $trabajo = new TrabajoAT();
        $where = [new DataBaseWhere('idservicio', $this->idservicio)];
        $order = ['fechainicio' => 'ASC', 'horainicio' => 'ASC'];
        return $trabajo->all($where, $order, 0, 0);
    }

    /**
     *
     * @return string
     */
    public function install()
    {
        /// neede dependencies
        new MaquinaAT();
        new EstadoAT();
        new PrioridadAT();

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
    public function primaryDescriptionColumn(): string
    {
        return 'idservicio';
    }

    /**
     *
     * @return string
     */
    public static function tableName(): string
    {
        return 'serviciosat';
    }

    /**
     *
     * @return bool
     */
    public function test()
    {
        $utils = $this->toolBox()->utils();
        $fields = ['descripcion', 'material', 'observaciones', 'solucion'];
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
    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return $type === 'new' ? 'NewServicioAT' : parent::url($type, $list);
    }

    /**
     *
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'idestado':
                $this->editable = $this->getStatus()->editable;
                return true;
        }

        return parent::onChange($field);
    }

    /**
     *
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = ['idestado'];
        parent::setPreviousData(\array_merge($more, $fields));
    }
}
