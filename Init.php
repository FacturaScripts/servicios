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

namespace FacturaScripts\Plugins\Servicios;

use FacturaScripts\Core\Base\AjaxForms\SalesHeaderHTML;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\InitClass;
use FacturaScripts\Core\Model\Role;
use FacturaScripts\Core\Model\RoleAccess;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Lib\StockMovementManager;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;

/**
 * Description of Init
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Init extends InitClass
{
    const ROLE_NAME = 'Servicios';

    public function init()
    {
        // extensions
        $this->loadExtension(new Extension\Controller\EditCliente());

        if (class_exists('FacturaScripts\\Dinamic\\Controller\\Randomizer')) {
            $this->loadExtension(new Extension\Controller\Randomizer());
        }

        // export manager
        ExportManager::addOptionModel('PDFserviciosExport', 'PDF', 'ServicioAT');

        // mod para los documentos de venta
        SalesHeaderHTML::addMod(new Mod\SalesHeaderHTMLMod());

        // mod y extensión para StockAvanzado
        $stockMovementClass = 'FacturaScripts\\Dinamic\\Lib\\StockMovementManager';
        if (class_exists($stockMovementClass) && method_exists($stockMovementClass, 'addMod')) {
            StockMovementManager::addMod(new Mod\StockMovementMod());
            $this->loadExtension(new Extension\Model\TrabajoAT());
        }
    }

    public function update()
    {
        new Model\EstadoAT();
        new Model\MaquinaAT();
        new Model\PrioridadAT();
        new Model\ServicioAT();
        new PresupuestoCliente();
        new AlbaranCliente();
        new FacturaCliente();

        $this->setupSettings();
        $this->createRoleForPlugin();
        $this->fixMissingCustomers();
    }

    private function createRoleForPlugin()
    {
        $dataBase = new DataBase();
        $dataBase->beginTransaction();

        // creates the role if not exists
        $role = new Role();
        if (false === $role->loadFromCode(self::ROLE_NAME)) {
            $role->codrole = $role->descripcion = self::ROLE_NAME;
            if (false === $role->save()) {
                // rollback and exit on fail
                $dataBase->rollback();
                return;
            }
        }

        // checks the role permissions
        $nameControllers = ['EditMaquinaAT', 'EditServicioAT', 'ListServicioAT', 'NewServicioAT'];
        foreach ($nameControllers as $nameController) {
            $roleAccess = new RoleAccess();
            $where = [
                new DataBaseWhere('codrole', self::ROLE_NAME),
                new DataBaseWhere('pagename', $nameController)
            ];
            if ($roleAccess->loadFromCode('', $where)) {
                // permission exists? Then skip
                continue;
            }

            // creates the permission if not exists
            $roleAccess->allowdelete = true;
            $roleAccess->allowupdate = true;
            $roleAccess->codrole = self::ROLE_NAME;
            $roleAccess->pagename = $nameController;
            $roleAccess->onlyownerdata = false;
            if (false === $roleAccess->save()) {
                // rollback and exit on fail
                $dataBase->rollback();
                return;
            }
        }

        // without problems = Commit
        $dataBase->commit();
    }

    private function fixMissingCustomers()
    {
        $db = new DataBase();
        $sql = 'UPDATE serviciosat SET codcliente = NULL WHERE codcliente IS NOT NULL AND codcliente NOT IN (SELECT codcliente FROM clientes);';
        $db->exec($sql);
    }

    private function setupSettings()
    {
        $appSettings = $this->toolBox()->appSettings();
        $footerText = $appSettings->get('servicios', 'footertext', '');
        $appSettings->set('servicios', 'footertext', $footerText);
        $appSettings->set('servicios', 'workstatus', 1);
        $appSettings->save();
    }
}
