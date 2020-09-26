<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Plugins\Servicios\Model\TrabajoAT;

/**
 * Description of EditServicioAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditServicioAT extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'ServicioAT';
    }

    /**
     * 
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'service';
        $data['icon'] = 'fas fa-edit';
        $data['showonmenu'] = false;

        return $data;
    }

    /**
     * Create the view to display.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('top');

        $this->createViewsWorks();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsWorks(string $viewName = 'EditTrabajoAT')
    {
        $this->addEditListView($viewName, 'TrabajoAT', 'work', 'fas fa-stethoscope');
        $this->views[$viewName]->disableColumn('service');
        
        
    }

    /**
     * 
     * @param BaseView $view
     */
    protected function disableServiceColumns(&$view)
    {
        foreach ($view->getColumns() as $group) {
            foreach ($group->columns as $col) {
                if ($col->name !== 'status') {
                    $view->disableColumn($col->name, false, 'true');
                }
            }
        }
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action) {
        switch ($action) {
            case 'auto-quantity':
                $this->calculateQuantity();
                return true;
                
            default:
                return parent::execPreviousAction($action);
        }        
    }
    
    
    /**
     * Loads the data to display.
     * 
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();

        switch ($viewName) {
            case $mainViewName:
                parent::loadData($viewName, $view);
                if (false === $view->model->exists()) {
                    $view->model->codalmacen = $this->user->codalmacen;
                    $view->model->idempresa = $this->user->idempresa;
                    $view->model->nick = $this->user->nick;
                } elseif (false === $view->model->editable) {
                    $this->disableServiceColumns($view);
                }
                break;

            case 'EditTrabajoAT':
                $idservicio = $this->getViewModelValue($mainViewName, 'idservicio');
                $this->loadEditTrabajoAT($view, $idservicio);
                break;
        }
    }
    
    /**
     * 
     * @param BaseView $view
     * @param int $idservicio
     */
    protected function loadEditTrabajoAT(&$view, $idservicio) {
        $where = [new DataBaseWhere('idservicio', $idservicio)];
        $view->loadData('', $where);
        if ($view->count > 0) {
            $this->addButton('EditTrabajoAT', [
                'action' => 'auto-quantity',
                'icon' => 'fas fa-calculator',
                'label' => 'calculate-hours',
                'type' => 'action',
                'color' => 'info',
            ]);
        }        
    }
    
    /**
     * Calculate the number of hours worked.
     * 
     * @return float
     */
    private function calculateQuantity()
    {
        if (!$this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return;
        }
        
        $code = $this->request->request->get('code', '');
        $model = new TrabajoAT();
        if ($model->loadFromCode($code)) {
            $days = $this->daysBetween($model->fechainicio, $model->fechafin);
            $hours = $this->TimeDifferenceInHours($model->horainicio, $model->horafin);
            $model->cantidad = ($days * 24) + $hours;
            if ($model->save()) {
                $this->toolBox()->i18nLog()->notice('record-updated-correctly');                
            }
        }        
    }
    
    /**
     * Calculate number days between two dates
     *
     * @param string $start
     * @param string $end
     * @param boolean $increment
     * @return integer
     */
    private function daysBetween($start, $end, $increment = false): int
    {
        if (empty($start) || empty($end)) {
            return 0;
        }

        $diff = strtotime($end) - strtotime($start);
        $result = ceil($diff / 86400);
        if ($increment) {
            ++$result;
        }
        return $result;
    }

    /**
     * Calculate hours number between two times
     *
     * @param string $start
     * @param string $end
     * @return float
     */
    private function TimeDifferenceInHours(string $start, string $end): float
    {
        if (empty($start) || empty($end)) {
            return 0;
        }

        $startHour = date_parse_from_format('H:i:s', $start);
        $endHour = date_parse_from_format('H:i:s', $end);

        $ini = ($startHour['hour'] * 3600) + ($startHour['minute'] * 60) + $startHour['second'];
        $fin = ($endHour['hour'] * 3600) + ($endHour['minute'] * 60) + $endHour['second'];

        $dif = ($fin - $ini) / 3600;
        return round($dif, 4);
    }    
}
