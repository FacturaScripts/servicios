<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Role;
use FacturaScripts\Core\Model\RoleAccess;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Controller\SendTicket;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Lib\StockMovementManager;
use FacturaScripts\Dinamic\Lib\Tickets\Service;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\EmailNotification;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;

/**
 * Description of Init
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class Init extends InitClass
{
    const ROLE_NAME = 'Servicios';

    public function init(): void
    {
        // extensions
        $this->loadExtension(new Extension\Controller\CopyModel());
        $this->loadExtension(new Extension\Controller\EditCliente());

        // tickets
        if (Plugins::isEnabled('Tickets')) {
            SendTicket::addFormat(Service::class, 'ServicioAT', 'service');
        }

        if (Plugins::isEnabled('Randomizer')) {
            $this->loadExtension(new Extension\Controller\Randomizer());
        }

        // export manager
        if (Plugins::isEnabled('PlantillasPDF')) {
            ExportManager::addOptionModel('PlantillasPDFserviciosExport', 'PDF', 'ServicioAT');
        } else {
            ExportManager::addOptionModel('PDFserviciosExport', 'PDF', 'ServicioAT');
        }

        // mod para los documentos de venta
        SalesHeaderHTML::addMod(new Mod\SalesHeaderHTMLMod());

        // mod y extensión para StockAvanzado
        if (Plugins::isEnabled('StockAvanzado')) {
            StockMovementManager::addMod(new Mod\StockMovementMod());
            $this->loadExtension(new Extension\Model\TrabajoAT());
        }
    }

    public function uninstall(): void
    {
    }

    public function update(): void
    {
        $this->fixMissingAgents();
        $this->fixMissingCustomers();

        new Model\EstadoAT();
        new Model\MaquinaAT();
        new Model\PrioridadAT();
        new Model\TipoAT();
        new Model\ServicioAT();
        new PresupuestoCliente();
        new AlbaranCliente();
        new FacturaCliente();

        $this->setupSettings();
        $this->createRoleForPlugin();
        $this->updateEmailNotifications();
    }

    private function createRoleForPlugin(): void
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

    private function fixMissingAgents(): void
    {
        // si no existe la tabla, no hacemos nada
        $db = new DataBase();
        foreach (['serviciosat', 'serviciosat_trabajos'] as $table) {
            if (false === $db->tableExists($table)) {
                break;
            }

            $sql = 'UPDATE ' . $table . ' SET codagente = NULL WHERE codagente IS NOT NULL AND codagente NOT IN (SELECT codagente FROM agentes);';
            $db->exec($sql);
        }
    }

    private function fixMissingCustomers(): void
    {
        // si no existe la tabla, no hacemos nada
        $db = new DataBase();
        if (false === $db->tableExists('serviciosat')) {
            return;
        }

        $db = new DataBase();
        $sql = 'UPDATE serviciosat SET codcliente = NULL WHERE codcliente IS NOT NULL AND codcliente NOT IN (SELECT codcliente FROM clientes);';
        $db->exec($sql);
    }

    private function setupSettings(): void
    {
        $defaults = [
            'print_ticket_footer_text' => '',
            'print_pdf_footer_text' => '',
            'longnumero' => 6,
            'patron' => 'SER{ANYO}-{NUM}',
            'workstatus' => 1
        ];

        foreach ($defaults as $key => $value) {
            Tools::settings('servicios', $key, $value);
        }
        Tools::settingsSave();
    }

    private function updateEmailNotifications(): void
    {
        $notificationModel = new EmailNotification();
        $keys = [
            'new-service-assignee', 'new-service-agent', 'new-service-customer',
            'new-service-status', 'new-service-user'
        ];
        foreach ($keys as $key) {
            if ($notificationModel->loadFromCode($key)) {
                continue;
            }

            $notificationModel->name = $key;
            $notificationModel->body = Tools::lang()->trans($key . '-body');
            $notificationModel->subject = Tools::lang()->trans($key);
            $notificationModel->enabled = false;
            $notificationModel->save();
        }
    }
}
