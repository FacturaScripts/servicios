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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;

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
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'id';
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
            new DataBaseWhere('id', $this->id, '!='),
            new DataBaseWhere('idcategory', $this->idcategory),
            new DataBaseWhere('idservice', $this->idservice),
        ];
        $serviceCategory = new ServicioCategoriaAT();
        if ($serviceCategory->loadFromCode('', $where)) {
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

    /**
     * Insert the model data in the database.
     * Add the service checklists to the service.
     *
     * @param array $values
     * @return bool
     */
    protected function saveInsert(array $values = []): bool
    {
        if (false === parent::saveInsert($values)) {
            return false;
        }

        $serviceCheck = new ServicioCheckAT();
        $whereCheck = [ new DataBaseWhere('idcategory', $this->idcategory) ];
        foreach (CheckAT::all($whereCheck) as $check) {
            $where = [
                new DataBaseWhere('idcheck', $check->id),
                new DataBaseWhere('idservice', $this->idservice),
            ];

            if ($serviceCheck->loadFromCode('', $where)) {
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
