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
namespace FacturaScripts\Plugins\Servicios\Lib\Random;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Randomizer\Lib\Random\NewItems;
use FacturaScripts\Plugins\Servicios\Model\EstadoAT;
use FacturaScripts\Plugins\Servicios\Model\MaquinaAT;
use FacturaScripts\Plugins\Servicios\Model\PrioridadAT;
use FacturaScripts\Plugins\Servicios\Model\ServicioAT;
use FacturaScripts\Plugins\Servicios\Model\TrabajoAT;
use Faker;

/**
 * Description of Servicios
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Jose Antonio Cuello <yopli2000@gmail.com>
 */
class Servicios extends NewItems
{

    /**
     *
     * @var PrioridadAT[]
     */
    private static $priorities = null;

    /**
     *
     * @var EstadoAT[]
     */
    private static $status = null;

    /**
     *
     * @param int $number
     *
     * @return int
     */
    public static function create(int $number = 50): int
    {
        $faker = Faker\Factory::create('es_ES');

        static::dataBase()->beginTransaction();
        for ($generated = 0; $generated < $number; $generated++) {
            $service = new ServicioAT();
            $service->idempresa = static::idempresa();
            $service->codcliente = static::cliente()->codcliente;
            $service->codagente = static::codagente();
            $service->codalmacen = static::codalmacen();
            $service->idestado = static::idestado();
            $service->idprioridad = static::idprioridad();
            $service->fecha = static::fecha();
            $service->hora = static::hora();
            $service->descripcion = $faker->text;
            $service->material = $faker->optional()->text;
            $service->solucion = $faker->optional()->text;
            $service->observaciones = $faker->optional()->text;
            static::setMachines($faker, $service);

            if ($service->exists()) {
                continue;
            }

            if (false === $service->save()) {
                break;
            }

            static::createWorks($faker, $service->idservicio);
        }

        static::dataBase()->commit();
        return $generated;
    }

    /**
     *
     * @param Faker\Generator $faker
     * @param MaquinaAT[]     $machineList
     * @param string          $code
     */
    protected static function createMachinesForCustomer(&$faker, &$machineList, $code)
    {
        $max = $faker->optional(0.1, 1)->numberBetween(2, 5);
        for ($index = 1; $index <= $max; $index++) {
            $machine = new MaquinaAT();
            $machine->codagente = static::codagente();
            $machine->codcliente = $code;
            $machine->codfabricante = static::codfabricante();
            $machine->descripcion = $faker->text;
            $machine->fecha = static::fecha();
            $machine->nombre = $faker->text(100);
            $machine->numserie = $faker->isbn13;
            $machine->referencia = static::referencia();

            if (false === $machine->save()) {
                break;
            }

            $machineList[] = $machine;
        }
    }

    /**
     *
     * @param Faker\Generator $faker
     * @param int             $code
     */
    protected static function createWorks(&$faker, $code)
    {
        $max = $faker->numberBetween(-1, 10);
        for ($index = 1; $index <= $max; $index++) {
            $work = new TrabajoAT();
            $work->idservicio = $code;
            $work->nick = static::nick();
            $work->codagente = static::codagente();
            $work->fechainicio = $faker->date();
            $work->horainicio = $faker->time();
            $work->fechafin = $faker->date();
            $work->horafin = $faker->time();
            $work->observaciones = $faker->text;

            $work->referencia = static::referencia();
            $work->cantidad = $faker->optional(0.2)->numberBetween(1, 15);
            $work->precio = $faker->optional()->numberBetween(1, 200);
            $work->descripcion = $faker->optional()->text;

            if (false === $work->save()) {
                break;
            }
        }
    }

    /**
     *
     * @param MaquinaAT[] $machines
     *
     * @return int|null
     */
    protected static function idmaquina(&$machines)
    {
        foreach ($machines as $key => $value) {
            unset($machines[$key]);
            return $value->idmaquina;
        }

        return null;
    }

    /**
     * Returns a random status for a service.
     *
     * @return int
     */
    protected static function idestado()
    {
        if (null === self::$status) {
            $seviceStatus = new EstadoAT();
            self::$status = $seviceStatus->all();
        }

        \shuffle(self::$status);
        return self::$status[0]->id;
    }

    /**
     * Returns a random priority for a service.
     *
     * @return int
     */
    protected static function idprioridad()
    {
        if (null === self::$priorities) {
            $priority = new PrioridadAT();
            self::$priorities = $priority->all();
        }

        \shuffle(self::$priorities);
        return self::$priorities[0]->id;
    }

    /**
     * Establish 0 to 4 machines to service.
     * 
     * @param Faker\Generator $faker
     * @param ServicioAT      $service
     */
    protected static function setMachines(&$faker, &$service)
    {
        $model = new MaquinaAT();
        $where = [new DataBaseWhere('codcliente', $service->codcliente)];
        $machines = $model->all($where);
        if (empty($machines)) {
            static::createMachinesForCustomer($faker, $machines, $service->codcliente);
        }

        $service->idmaquina = static::idmaquina($machines);
        $service->idmaquina2 = static::idmaquina($machines);
        $service->idmaquina3 = static::idmaquina($machines);
        $service->idmaquina4 = static::idmaquina($machines);
    }
}
