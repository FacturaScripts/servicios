<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\ServicioAT;

/**
 * Model class for the checks compled for one service.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ServicioCheckAT extends ModelClass
{
    use ModelTrait;

    /**
     * Indicates if the check is completed.
     *
     * @var boolean
     */
    public $checked;

    /**
     * Date of the completion.
     *
     * @var string
     */
    public $completed;

    /**
     * Primary key of the table.
     *
     * @var int
     */
    public $id;

    /**
     * Link to the Category Check model.
     *
     * @var int
     */
    public $idcheck;

    /**
     * Link to the Service model.
     *
     * @var int
     */
    public $idservice;

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install(): string
    {
        new ServicioAT();
        new CheckAT();

        return parent::install();
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName(): string
    {
        return 'serviciosat_serviciochecks';
    }

    /**
     * Returns true if there are no errors in the values of the model properties.
     * It runs inside the save method.
     *
     * @return bool
     */
    public function test(): bool
    {
        if ($this->checked) {
            if (empty($this->completed)) {
                $this->completed = Tools::dateTime();
            }
        } else {
            $this->completed = null;
        }

        return parent::test();
    }

    protected function saveInsert(): bool
    {
        $check = new CheckAT();
        if (false === $check->load($this->idcheck)) {
            $this->idcheck = null;
            return false;
        }

        $where = [
            Where::column('idservice', $this->idservice),
            Where::column('idcategory', $check->idcategory)
        ];
        $serviceCategory = new ServicioCategoriaAT();
        if (false === $serviceCategory->loadWhere($where)) {
            $this->idcheck = null;
            Tools::log()->warning('check-no-in-service-category');
            return false;
        }

        return parent::saveInsert();
    }
}
