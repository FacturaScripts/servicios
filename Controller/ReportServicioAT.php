<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Servicios\Controller;

use DateTime;
use FacturaScripts\Core\Base\Controller;

/**
 * Informe de servicios: abiertos totales, del último mes, del último año y desglose por estado.
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */
class ReportServicioAT extends Controller
{
    /** @var array */
    public $servicesByAgent = [];

    /** @var array */
    public $servicesByAssigned = [];

    /** @var array */
    public $servicesByClient = [];

    /** @var array */
    public $servicesByMonth = [];

    /** @var array */
    public $servicesByNick = [];

    /** @var array */
    public $servicesByStatus;

    /** @var array */
    public $servicesByYear = [];

    /** @var int */
    public $openServices;

    /** @var int */
    public $openServicesLastMonth;

    /** @var int */
    public $openServicesLastYear;

    /** @var int */
    public $totalServices;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'services';
        $data['icon'] = 'fa-solid fa-headset';
        return $data;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $this->loadTotalServices();
        $this->loadOpenServices();
        $this->loadOpenServicesLastMonth();
        $this->loadOpenServicesLastYear();
        $this->loadServicesByStatus();
        $this->loadServicesByMonth();
        $this->loadServicesByYear();
        $this->loadServicesByNick();
        $this->loadServicesByAgent();
        $this->loadServicesByAssigned();
        $this->loadServicesByClient();
    }

    protected function loadTotalServices(): void
    {
        $sql = 'SELECT COUNT(*) as total FROM serviciosat';
        $result = $this->dataBase->select($sql);
        $this->totalServices = (int)($result[0]['total'] ?? 0);
    }

    protected function loadServicesByNick(): void
    {
        $sql = 'SELECT nick, COUNT(*) as total'
            . ' FROM serviciosat'
            . ' GROUP BY nick'
            . ' ORDER BY total DESC';
        $this->servicesByNick = $this->dataBase->select($sql);
    }

    protected function loadServicesByAgent(): void
    {
        $sql = 'SELECT s.codagente, a.nombre, COUNT(s.idservicio) as total'
            . ' FROM serviciosat s'
            . ' LEFT JOIN agentes a ON a.codagente = s.codagente'
            . ' GROUP BY s.codagente, a.nombre'
            . ' ORDER BY total DESC';
        $this->servicesByAgent = $this->dataBase->select($sql);
    }

    protected function loadServicesByAssigned(): void
    {
        $sql = 'SELECT asignado, COUNT(*) as total'
            . ' FROM serviciosat'
            . ' GROUP BY asignado'
            . ' ORDER BY total DESC';
        $this->servicesByAssigned = $this->dataBase->select($sql);
    }

    protected function loadServicesByClient(): void
    {
        $sql = 'SELECT s.codcliente, c.nombre, COUNT(s.idservicio) as total'
            . ' FROM serviciosat s'
            . ' LEFT JOIN clientes c ON c.codcliente = s.codcliente'
            . ' GROUP BY s.codcliente, c.nombre'
            . ' ORDER BY total DESC';
        $this->servicesByClient = $this->dataBase->select($sql);
    }

    protected function loadOpenServices(): void
    {
        $sql = 'SELECT COUNT(*) as total FROM serviciosat'
            . ' WHERE editable = ' . $this->dataBase->var2str(true);
        $result = $this->dataBase->select($sql);
        $this->openServices = (int)($result[0]['total'] ?? 0);
    }

    protected function loadOpenServicesLastMonth(): void
    {
        $since = date('Y-m-d', strtotime('-1 month'));
        $sql = "SELECT COUNT(*) as total FROM serviciosat WHERE fecha >= '" . $since . "'";
        $result = $this->dataBase->select($sql);
        $this->openServicesLastMonth = (int)($result[0]['total'] ?? 0);
    }

    protected function loadOpenServicesLastYear(): void
    {
        $since = date('Y-m-d', strtotime('-1 year'));
        $sql = "SELECT COUNT(*) as total FROM serviciosat WHERE fecha >= '" . $since . "'";
        $result = $this->dataBase->select($sql);
        $this->openServicesLastYear = (int)($result[0]['total'] ?? 0);
    }

    protected function loadServicesByMonth(): void
    {
        // genera los 12 meses completos con valor 0 para no dejar huecos en el gráfico
        $now = new DateTime();
        for ($i = 11; $i >= 0; $i--) {
            $date = clone $now;
            $date->modify("-$i months");
            $this->servicesByMonth[$date->format('Y-m')] = 0;
        }

        $since = (clone $now)->modify('-11 months')->format('Y-m-01');
        $sql = "SELECT DATE_FORMAT(fecha, '%Y-%m') as periodo, COUNT(*) as total"
            . ' FROM serviciosat'
            . " WHERE fecha >= '" . $since . "'"
            . " GROUP BY DATE_FORMAT(fecha, '%Y-%m')"
            . ' ORDER BY periodo ASC';
        foreach ($this->dataBase->select($sql) as $row) {
            if (isset($this->servicesByMonth[$row['periodo']])) {
                $this->servicesByMonth[$row['periodo']] = (int)$row['total'];
            }
        }
    }

    protected function loadServicesByYear(): void
    {
        $sql = 'SELECT YEAR(fecha) as periodo, COUNT(*) as total'
            . ' FROM serviciosat'
            . ' GROUP BY YEAR(fecha)'
            . ' ORDER BY periodo ASC';
        foreach ($this->dataBase->select($sql) as $row) {
            $this->servicesByYear[(string)$row['periodo']] = (int)$row['total'];
        }
    }

    protected function loadServicesByStatus(): void
    {
        $sql = 'SELECT e.id, e.nombre, e.color, e.editable, COUNT(s.idservicio) as total'
            . ' FROM serviciosat_estados e'
            . ' LEFT JOIN serviciosat s ON s.idestado = e.id'
            . ' GROUP BY e.id, e.nombre, e.color, e.editable'
            . ' ORDER BY total DESC';
        $this->servicesByStatus = $this->dataBase->select($sql);
    }
}
