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

/**
 * Model class for the category assigned to one AT service.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ServicioCategoriaAT extends ModelClass
{
    use ModelTrait;

    /**
     * Primary key of the table.
     *
     * @var int
     */
    public $id;

    /**
     * Link to the Category model.
     *
     * @var int
     */
    public $idcategory;

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
        return 'serviciosat_serviciocategorias';
    }

    /**
     * Returns true if there are no errors in the values of the model properties.
     * It runs inside the save method.
     *   - Check obligatory fields.
     *   - Check no duplicate category + service.
     *
     * @return bool
     */
    public function test(): bool
    {
        if (false === parent::test()) {
            return false;
        }

        $where = [
            Where::column('id', $this->id, '!='),
            Where::column('idcategory', $this->idcategory),
            Where::column('idservice', $this->idservice),
        ];
        $serviceCategory = new ServicioCategoriaAT();
        if ($serviceCategory->loadWhere($where)) {
            Tools::log()->warning('duplicate-service-category');
            return false;
        }

        return true;
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'EditServicioAT'): string
    {
        $list .= '?code=' . $this->idservice . '&activetab=List';
        return parent::url($type, $list);
    }

    protected function saveInsert(): bool
    {
        if (false === parent::saveInsert()) {
            return false;
        }

        $serviceCheck = new ServicioCheckAT();
        $whereCheck = [Where::column('idcategory', $this->idcategory)];
        foreach (CheckAT::all($whereCheck) as $check) {
            $where = [
                Where::column('idcheck', $check->id),
                Where::column('idservice', $this->idservice),
            ];

            if ($serviceCheck->loadWhere($where)) {
                continue;
            }
            $serviceCheck->idcheck = $check->id;
            $serviceCheck->idservice = $this->idservice;
            $serviceCheck->checked = false;
            $serviceCheck->save();
        }

        return true;
    }
}
